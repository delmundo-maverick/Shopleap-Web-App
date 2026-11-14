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
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Outbound transfer: the sending hub groups parcels for another hub of the
 * same company into a bag, seals it, prints the manifest, and dispatches it.
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

        $destinationProvince = trim((string) ($bag->toHub?->province ?? ''));

        $available = Order::with(['items', 'seller.sellerProfile'])
            ->heldBy($hub->id)
            ->where('status', Order::STATUS_AWAITING_TRANSFER)
            ->where('destination_hub_id', $bag->to_hub_id)
            ->whereDoesntHave('shipmentBagItems', fn ($query) => $query
                ->whereHas('bag', fn ($b) => $b->whereIn('status', ShipmentBag::ACTIVE_STATUSES)))
            ->orderBy('id')
            ->get()
            ->filter(function (Order $order) use ($destinationProvince) {
                if ($destinationProvince === '') {
                    return false;
                }

                $orderProvince = trim((string) ($order->ship_to['province'] ?? ''));

                return $orderProvince !== '' && strcasecmp($orderProvince, $destinationProvince) === 0;
            });

        return view('logistics.outbound.show', compact('hub', 'bag', 'available'));
    }

    public function addOrders(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);
        $this->authorizeOpen($bag);

        $validated = $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['string', 'max:32'],
        ]);

        $result = $this->attachOrders($bag, $hub->id, $validated['order_ids']);

        return back()->with('status', $this->addedMessage($result));
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

            $deleted = $lockedBag->items()->where('order_id', $order->id)->delete() > 0;

            if ($deleted && $lockedBag->manifest_printed_at) {
                $lockedBag->update([
                    'manifest_printed_at' => null,
                    'manifest_printed_by' => null,
                ]);
            }

            return $deleted;
        });

        if ($removed === null) {
            return back()->withErrors(['orders' => 'This bag is no longer open.']);
        }

        if (! $removed) {
            return back()->withErrors(['orders' => "Order #{$order->order_number} is not in this bag."]);
        }

        return back()->with('status', "Order #{$order->order_number} removed from bag {$bag->code}.");
    }

    public function seal(Request $request, ShipmentBag $bag): RedirectResponse
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['items.order', 'fromHub', 'toHub']);

        if ($bag->items->isEmpty()) {
            return back()->withErrors(['orders' => 'Add at least one parcel before sealing the bag.']);
        }

        // Re-check under a row lock so parcels cannot change while sealing.
        $result = DB::transaction(function () use ($bag, $request) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->first();

            if (! $lockedBag || ! $lockedBag->isOpen()) {
                return 'not_open';
            }

            if ($lockedBag->items()->count() === 0) {
                return 'empty';
            }

            $lockedBag->update([
                'status' => ShipmentBag::STATUS_SEALED,
                'sealed_by' => $request->user()->id,
                'sealed_at' => now(),
                'manifest_printed_at' => null,
                'manifest_printed_by' => null,
                'updated_at' => now(),
            ]);

            $lockedBag->load('items.order', 'toHub');

            foreach ($lockedBag->items as $item) {
                if (! $item->order) {
                    continue;
                }

                $this->lifecycle->record($item->order, ParcelEvent::TYPE_BAG_SEALED, [
                    'hub_id' => $lockedBag->from_hub_id,
                    'note' => "Sealed in bag {$lockedBag->code} for {$lockedBag->toHub?->business_name}.",
                    'actor_user_id' => $request->user()->id,
                ]);
            }

            return $lockedBag;
        });

        if (is_string($result)) {
            return back()->withErrors(['orders' => match ($result) {
                'empty' => 'Add at least one parcel before sealing the bag.',
                default => 'Only an open bag can be sealed.',
            }]);
        }

        return back()->with('status', "Bag {$result->code} sealed with {$result->items->count()} parcel(s).");
    }

    public function dispatch(Request $request, ShipmentBag $bag, Notifier $notifier): RedirectResponse
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $bag->load(['items.order', 'fromHub', 'toHub']);

        $moved = DB::transaction(function () use ($bag, $request) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->first();

            if (! $lockedBag || ! $lockedBag->isSealed()) {
                return 'not_sealed';
            }

            if (! $lockedBag->manifest_printed_at) {
                return 'not_printed';
            }

            $lockedBag->update([
                'status' => ShipmentBag::STATUS_IN_TRANSIT,
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

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

        if ($moved !== true) {
            return back()->withErrors(['orders' => $moved === 'not_printed'
                ? 'Print the manifest first.'
                : 'Only a sealed bag can be put in transit.']);
        }

        $notifier->bagDispatched($bag->fresh(['fromHub', 'toHub.user']), $request->user()->id);

        return back()->with('status', "Bag {$bag->code} is now in transit.");
    }

    public function manifest(Request $request, ShipmentBag $bag): Response
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $html = DB::transaction(function () use ($bag, $hub) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->firstOrFail();

            abort_if($lockedBag->isOpen(), 422, 'Seal the bag before printing the manifest.');

            $lockedBag->load(['fromHub', 'toHub', 'items.order', 'sealer', 'creator']);

            $barcodePng = (new BarcodeGeneratorPNG)->getBarcode(
                $lockedBag->code,
                BarcodeGeneratorPNG::TYPE_CODE_128,
                2,
                44
            );

            $qrWriter = new Writer(new ImageRenderer(new RendererStyle(200, 2), new SvgImageBackEnd));

            $barcodeBase64 = base64_encode($barcodePng);
            $qrBase64 = base64_encode($qrWriter->writeString('SHOPLEAP:BAG:'.$lockedBag->code));

            return view(
                'logistics.outbound.manifest',
                ['hub' => $hub, 'bag' => $lockedBag, 'barcodeBase64' => $barcodeBase64, 'qrBase64' => $qrBase64]
            )->render();

        });

        return response($html);
    }

    public function markManifestPrinted(Request $request, ShipmentBag $bag): JsonResponse
    {
        $hub = HubContext::require($request);
        $this->authorizeSender($bag, $hub->id);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'string', 'max:32', 'distinct'],
        ]);

        $printedAt = DB::transaction(function () use ($bag, $request, $validated) {
            $lockedBag = ShipmentBag::whereKey($bag->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedBag->isOpen(), 422, 'Seal the bag before printing the manifest.');

            $printedOrders = collect($validated['order_ids'])->sort()->values()->all();
            $currentOrders = $lockedBag->items()->with('order')->get()->pluck('order.order_number')->sort()->values()->all();
            abort_unless($printedOrders === $currentOrders, 422, 'The bag contents changed. Reload the manifest and print it again.');

            $printedAt = now();
            $lockedBag->update([
                'manifest_printed_at' => $printedAt,
                'manifest_printed_by' => $request->user()->id,
            ]);

            return $printedAt;
        });

        return response()->json(['message' => 'Manifest print recorded.', 'printed_at' => $printedAt->toIso8601String()]);
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
                ->whereIn('order_number', $orderIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('order_number');

            $destinationProvince = trim((string) ($lockedBag->toHub?->province ?? ''));

            $added = 0;
            $skipped = 0;
            $reason = null;

            foreach ($orderIds as $orderId) {
                $order = $orders->get($orderId);

                if (! $order) {
                    $skipped++;
                    $reason ??= "Order #{$orderId} is not held by this hub.";

                    continue;
                }

                if ($order->status !== Order::STATUS_AWAITING_TRANSFER) {
                    $skipped++;
                    $reason ??= "Order #{$order->order_number} is not awaiting transfer.";

                    continue;
                }

                if ((int) $order->destination_hub_id !== (int) $bag->to_hub_id) {
                    $skipped++;
                    $reason ??= "Order #{$order->order_number} is addressed to another hub.";

                    continue;
                }

                $orderProvince = trim((string) ($order->ship_to['province'] ?? ''));

                if (
                    $destinationProvince === ''
                    || $orderProvince === ''
                    || strcasecmp($orderProvince, $destinationProvince) !== 0
                ) {
                    $skipped++;
                    $reason ??= "Order #{$order->order_number} is addressed to another province.";

                    continue;
                }

                $inActiveBag = $order->shipmentBagItems()
                    ->whereHas('bag', fn ($query) => $query->whereIn('status', ShipmentBag::ACTIVE_STATUSES))
                    ->exists();

                if ($inActiveBag) {
                    $skipped++;
                    $reason ??= "Order #{$order->order_number} is already in an active bag.";

                    continue;
                }

                $lockedBag->items()->create(['order_id' => $order->id]);
                $added++;
            }

            if ($added > 0 && $lockedBag->manifest_printed_at) {
                $lockedBag->update([
                    'manifest_printed_at' => null,
                    'manifest_printed_by' => null,
                ]);
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
