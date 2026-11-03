<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Services\HubContext;
use App\Services\Notifier;
use App\Services\ParcelLifecycle;
use App\Support\ScanCode;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Outbound transfer: the sending hub groups parcels for another hub of the
 * same company into a bag, seals it, dispatches it, and prints the manifest.
 */
class OutboundController extends Controller
{
    public function __construct(private ParcelLifecycle $lifecycle) {}

    public function index(Request $request)
    {
        $hub = HubContext::require($request);

        $awaiting = Order::with(['items', 'seller.sellerProfile', 'destinationHub'])
            ->heldBy($hub->id)
            ->where('status', Order::STATUS_AWAITING_TRANSFER)
            ->whereNotNull('destination_hub_id')
            ->orderBy('destination_hub_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (Order $order) => $order->destinationHub?->isSameCompanyAs($hub))
            ->groupBy('destination_hub_id');

        $bags = ShipmentBag::with(['toHub'])
            ->withCount([
                'items',
                'items as received_items_count' => fn ($query) => $query->whereNotNull('received_at'),
            ])
            ->sentBy($hub->id)
            ->latest()
            ->paginate(10);

        $counts = [
            'open' => ShipmentBag::sentBy($hub->id)->where('status', ShipmentBag::STATUS_OPEN)->count(),
            'in_transit' => ShipmentBag::sentBy($hub->id)->where('status', ShipmentBag::STATUS_IN_TRANSIT)->count(),
        ];

        return view('logistics.outbound.index', compact('hub', 'awaiting', 'bags', 'counts'));
    }

    public function store(Request $request)
    {
        $hub = HubContext::require($request);

        $validated = $request->validate([
            'to_hub_id' => ['required', 'integer', 'exists:logistics_profiles,id'],
        ]);

        $destination = LogisticsProfile::findOrFail($validated['to_hub_id']);

        abort_if((int) $destination->id === (int) $hub->id, 422, 'A bag must be sent to another hub.');
        abort_unless(
            $destination->isSameCompanyAs($hub),
            422,
            'Bags can only be sent to hubs of the same logistics company.'
        );

        $bag = ShipmentBag::create([
            'code' => $this->generateCode(),
            'from_hub_id' => $hub->id,
            'to_hub_id' => $destination->id,
            'status' => ShipmentBag::STATUS_OPEN,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('logistics.outbound.show', $bag)
            ->with('status', "Bag {$bag->code} opened for {$destination->business_name}.");
    }

    public function show(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['fromHub', 'toHub', 'items.order', 'creator', 'sealer', 'receiver']);

        $available = Order::with(['items', 'seller.sellerProfile'])
            ->heldBy($hub->id)
            ->where('status', Order::STATUS_AWAITING_TRANSFER)
            ->where('destination_hub_id', $bag->to_hub_id)
            ->whereDoesntHave('shipmentBagItems', fn ($query) => $query
                ->whereHas('bag', fn ($b) => $b->whereIn('status', ShipmentBag::ACTIVE_STATUSES)))
            ->orderBy('id')
            ->get();

        return view('logistics.outbound.show', compact('hub', 'bag', 'available'));
    }

    public function addOrders(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);
        $this->authorizeOpen($bag);

        $validated = $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['integer'],
        ]);

        $result = $this->attachOrders($bag, $hub->id, $validated['order_ids']);

        return back()->with('status', $this->addedMessage($result));
    }

    public function scan(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);
        $this->authorizeOpen($bag);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $orderId = ScanCode::orderId($validated['code']);

        if ($orderId === null) {
            return $this->scanResponse($request, false, 'That code is not an order barcode.');
        }

        $result = $this->attachOrders($bag, $hub->id, [$orderId]);

        if ($result['added'] === 0) {
            return $this->scanResponse($request, false, $result['reason'] ?? "Order #{$orderId} could not be added.");
        }

        return $this->scanResponse($request, true, "Order #{$orderId} added to bag {$bag->code}.", $orderId);
    }

    public function removeOrder(Request $request, ShipmentBag $bag, Order $order)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);
        $this->authorizeOpen($bag);

        $removed = DB::transaction(function () use ($bag, $order) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->first();

            if (! $lockedBag || ! $lockedBag->isOpen()) {
                return null;
            }

            return $lockedBag->items()->where('order_id', $order->id)->delete() > 0;
        });

        if ($removed === null) {
            return back()->withErrors(['orders' => 'This bag is no longer open.']);
        }

        if (! $removed) {
            return back()->withErrors(['orders' => "Order #{$order->id} is not in this bag."]);
        }

        return back()->with('status', "Order #{$order->id} removed from bag {$bag->code}.");
    }

    public function seal(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['items.order', 'fromHub', 'toHub']);

        if ($bag->items->isEmpty()) {
            return back()->withErrors(['orders' => 'Add at least one parcel before sealing the bag.']);
        }

        $moved = DB::transaction(function () use ($bag, $request) {
            $updated = ShipmentBag::whereKey($bag->id)
                ->where('status', ShipmentBag::STATUS_OPEN)
                ->update([
                    'status' => ShipmentBag::STATUS_SEALED,
                    'sealed_by' => $request->user()->id,
                    'sealed_at' => now(),
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                return false;
            }

            $bag->refresh()->load('items.order', 'toHub');

            foreach ($bag->items as $item) {
                $this->lifecycle->record($item->order, ParcelEvent::TYPE_BAG_SEALED, [
                    'hub_id' => $bag->from_hub_id,
                    'note' => "Sealed in bag {$bag->code} for {$bag->toHub?->business_name}.",
                    'actor_user_id' => $request->user()->id,
                ]);
            }

            return true;
        });

        if (! $moved) {
            return back()->withErrors(['orders' => 'Only an open bag can be sealed.']);
        }

        return back()->with('status', "Bag {$bag->code} sealed with {$bag->items->count()} parcel(s).");
    }

    public function dispatch(Request $request, ShipmentBag $bag, Notifier $notifier)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['items.order', 'fromHub', 'toHub']);

        $moved = DB::transaction(function () use ($bag, $request) {
            $updated = ShipmentBag::whereKey($bag->id)
                ->where('status', ShipmentBag::STATUS_SEALED)
                ->update([
                    'status' => ShipmentBag::STATUS_IN_TRANSIT,
                    'dispatched_at' => now(),
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                return false;
            }

            $bag->refresh()->load('items.order', 'toHub');

            foreach ($bag->items as $item) {
                $this->lifecycle->record($item->order, ParcelEvent::TYPE_BAG_DISPATCHED, [
                    'hub_id' => $bag->from_hub_id,
                    'note' => "Dispatched from {$bag->fromHub?->business_name} to {$bag->toHub?->business_name} in bag {$bag->code}.",
                    'actor_user_id' => $request->user()->id,
                ]);
            }

            return true;
        });

        if (! $moved) {
            return back()->withErrors(['orders' => 'Only a sealed bag can be dispatched.']);
        }

        $notifier->bagDispatched($bag->fresh(['fromHub', 'toHub.user']), $request->user()->id);

        return back()->with('status', "Bag {$bag->code} is now in transit.");
    }

    public function manifest(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['fromHub', 'toHub', 'items.order', 'sealer']);

        $barcodePng = (new BarcodeGeneratorPNG)->getBarcode(
            $bag->code,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            44
        );

        $qrWriter = new Writer(new ImageRenderer(new RendererStyle(200, 2), new SvgImageBackEnd));

        $barcodeBase64 = base64_encode($barcodePng);
        $qrBase64 = base64_encode($qrWriter->writeString('SHOPLEAP:BAG:'.$bag->code));

        return view('logistics.outbound.manifest', compact('hub', 'bag', 'barcodeBase64', 'qrBase64'));
    }

    /**
     * Add orders to an open bag, skipping anything that is not transferable.
     *
     * @param  array<int, int|string>  $orderIds
     * @return array{added: int, skipped: int, reason: string|null}
     */
    private function attachOrders(ShipmentBag $bag, int $hubId, array $orderIds): array
    {
        // Lock the bag and the parcels so two staff members cannot put the
        // same order into two open bags, or add parcels to a bag being sealed.
        return DB::transaction(function () use ($bag, $hubId, $orderIds) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->first();

            if (! $lockedBag || ! $lockedBag->isOpen()) {
                return ['added' => 0, 'skipped' => count($orderIds), 'reason' => 'This bag is no longer open.'];
            }

            $orders = Order::with('destinationHub')
                ->heldBy($hubId)
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $added = 0;
            $skipped = 0;
            $reason = null;

            foreach ($orderIds as $orderId) {
                $order = $orders->get((int) $orderId);

                if (! $order) {
                    $skipped++;
                    $reason ??= "Order #{$orderId} is not held by this hub.";

                    continue;
                }

                if ($order->status !== Order::STATUS_AWAITING_TRANSFER) {
                    $skipped++;
                    $reason ??= "Order #{$order->id} is not awaiting transfer.";

                    continue;
                }

                if ((int) $order->destination_hub_id !== (int) $bag->to_hub_id) {
                    $skipped++;
                    $reason ??= "Order #{$order->id} is addressed to another hub.";

                    continue;
                }

                $inActiveBag = $order->shipmentBagItems()
                    ->whereHas('bag', fn ($query) => $query->whereIn('status', ShipmentBag::ACTIVE_STATUSES))
                    ->exists();

                if ($inActiveBag) {
                    $skipped++;
                    $reason ??= "Order #{$order->id} is already in an active bag.";

                    continue;
                }

                $lockedBag->items()->create(['order_id' => $order->id]);
                $added++;
            }

            return ['added' => $added, 'skipped' => $skipped, 'reason' => $reason];
        });
    }

    /** @param  array{added: int, skipped: int, reason: string|null}  $result */
    private function addedMessage(array $result): string
    {
        $message = "{$result['added']} parcel(s) added to the bag.";

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped.";
        }

        return $message;
    }

    private function scanResponse(Request $request, bool $ok, string $message, ?int $orderId = null)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'order_id' => $orderId,
            ], $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('status', $message)
            : back()->withErrors(['code' => $message]);
    }

    private function authorizeSender(ShipmentBag $bag, int $hubId): void
    {
        abort_unless((int) $bag->from_hub_id === $hubId, 403, 'This bag belongs to another hub.');
    }

    private function authorizeOpen(ShipmentBag $bag): void
    {
        abort_unless($bag->isOpen(), 422, 'This bag is no longer open.');
    }

    private function generateCode(): string
    {
        do {
            $code = 'SB-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (ShipmentBag::where('code', $code)->exists());

        return $code;
    }
}
