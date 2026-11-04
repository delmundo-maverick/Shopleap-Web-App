<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\ShipmentBagItem;
use App\Services\HubContext;
use App\Services\Notifier;
use App\Services\ParcelLifecycle;
use App\Support\ScanCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Inbound transfer: the receiving hub scans the bag, confirms each parcel,
 * then closes the bag. Anything still unscanned is flagged as missing.
 */
class InboundController extends Controller
{
    public function __construct(private ParcelLifecycle $lifecycle) {}

    public function index(Request $request)
    {
        $hub = HubContext::require($request);

        $incoming = ShipmentBag::with(['fromHub'])
            ->withCount([
                'items',
                'items as received_items_count' => fn ($query) => $query->whereNotNull('received_at'),
            ])
            ->addressedTo($hub->id)
            ->whereIn('status', [ShipmentBag::STATUS_SEALED, ShipmentBag::STATUS_IN_TRANSIT])
            ->orderByRaw('dispatched_at nulls first')
            ->latest()
            ->get();

        $recent = ShipmentBag::with(['fromHub'])
            ->withCount([
                'items',
                'items as received_items_count' => fn ($query) => $query->whereNotNull('received_at'),
            ])
            ->addressedTo($hub->id)
            ->whereIn('status', [ShipmentBag::STATUS_RECEIVED, ShipmentBag::STATUS_DISCREPANCY])
            ->latest('received_at')
            ->paginate(10);

        return view('logistics.inbound.index', compact('hub', 'incoming', 'recent'));
    }

    public function show(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeReceiver($bag, $hub->id);

        $bag->load(['fromHub', 'toHub', 'items.order', 'receiver']);

        $verified = (bool) $request->session()->get($this->verifyKey($bag));

        return view('logistics.inbound.show', compact('hub', 'bag', 'verified'));
    }

    public function verify(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeReceiver($bag, $hub->id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $scanned = ScanCode::bagCode($validated['code']);

        if ($scanned === null || strcasecmp($scanned, $bag->code) !== 0) {
            return back()->withErrors(['code' => 'That code does not match this bag.']);
        }

        $request->session()->put($this->verifyKey($bag), true);

        return redirect()
            ->route('logistics.inbound.show', $bag)
            ->with('status', "Bag {$bag->code} verified. Scan each parcel, then close the bag.");
    }

    public function scan(Request $request, ShipmentBag $bag)
    {
        $hub = HubContext::require($request);
        $this->authorizeReceiver($bag, $hub->id);
        $this->authorizeVerification($request, $bag);
        $this->authorizeReceivable($bag);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $orderId = ScanCode::orderId($validated['code']);

        if ($orderId === null) {
            return $this->scanResponse($request, false, 'That code is not an order barcode.');
        }

        $item = $bag->items()->with('order')->where('order_id', $orderId)->first();

        if (! $item) {
            return $this->scanResponse($request, false, "Order #{$orderId} is not in this bag.");
        }

        if ($item->received_at) {
            return $this->scanResponse($request, true, "Order #{$orderId} was already scanned.", $orderId);
        }

        $message = $this->receiveItem($bag, $item, $hub->id, $request->user()->id);

        return $this->scanResponse($request, true, $message, $orderId);
    }

    public function confirmParcel(Request $request, ShipmentBag $bag, Order $order)
    {
        $hub = HubContext::require($request);
        $this->authorizeReceiver($bag, $hub->id);
        $this->authorizeVerification($request, $bag);
        $this->authorizeReceivable($bag);

        $item = $bag->items()->with('order')->where('order_id', $order->id)->first();

        if (! $item) {
            return back()->withErrors(['parcels' => "Order #{$order->id} is not in this bag."]);
        }

        if ($item->received_at) {
            return back()->with('status', "Order #{$order->id} was already confirmed.");
        }

        $message = $this->receiveItem($bag, $item, $hub->id, $request->user()->id);

        return back()->with('status', $message);
    }

    public function close(Request $request, ShipmentBag $bag, Notifier $notifier)
    {
        $hub = HubContext::require($request);
        $this->authorizeReceiver($bag, $hub->id);
        $this->authorizeVerification($request, $bag);

        $bag->load(['items.order', 'fromHub', 'toHub']);

        if ($bag->items->isEmpty()) {
            return back()->withErrors(['parcels' => 'This bag has no parcels to receive.']);
        }

        // A discrepancy bag can be closed again once a missing parcel shows up.
        // Only the first close records missing events and notifies both hubs.
        $wasDiscrepancy = $bag->status === ShipmentBag::STATUS_DISCREPANCY;

        $result = DB::transaction(function () use ($bag, $request, $wasDiscrepancy) {
            // Lock the items so a scan finishing at the same moment cannot be
            // mistaken for a missing parcel.
            $items = ShipmentBagItem::where('bag_id', $bag->id)
                ->with('order')
                ->lockForUpdate()
                ->get();

            $missing = $items->whereNull('received_at');
            $status = $missing->isEmpty()
                ? ShipmentBag::STATUS_RECEIVED
                : ShipmentBag::STATUS_DISCREPANCY;

            $updated = ShipmentBag::whereKey($bag->id)
                ->whereIn('status', [
                    ShipmentBag::STATUS_SEALED,
                    ShipmentBag::STATUS_IN_TRANSIT,
                    ShipmentBag::STATUS_DISCREPANCY,
                ])
                ->update([
                    'status' => $status,
                    'received_by' => $bag->received_by ?? $request->user()->id,
                    'received_at' => $bag->received_at ?? now(),
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                return null;
            }

            if (! $wasDiscrepancy) {
                foreach ($missing as $item) {
                    if (! $item->order) {
                        continue;
                    }

                    $this->lifecycle->record($item->order, ParcelEvent::TYPE_BAG_MISSING, [
                        'hub_id' => $bag->to_hub_id,
                        'note' => "Missing from bag {$bag->code} received at {$bag->toHub?->business_name}.",
                        'actor_user_id' => $request->user()->id,
                    ]);
                }
            }

            return ['missing' => $missing, 'count' => $items->count()];
        });

        if ($result === null) {
            return back()->withErrors(['parcels' => 'This bag has already been received.']);
        }

        $missing = $result['missing'];

        $bag->refresh()->load(['fromHub.user', 'toHub.user', 'items.order']);

        if ($missing->isNotEmpty()) {
            if (! $wasDiscrepancy) {
                $notifier->bagDiscrepancy($bag, $missing->pluck('order')->filter()->values(), $request->user()->id);
            }

            $message = $wasDiscrepancy
                ? "Bag {$bag->code} still has {$missing->count()} missing parcel(s)."
                : "Bag {$bag->code} closed with {$missing->count()} missing parcel(s). Both hubs were notified.";

            return redirect()
                ->route('logistics.inbound.index')
                ->with('status', $message);
        }

        $notifier->bagReceived($bag, $request->user()->id);

        return redirect()
            ->route('logistics.inbound.index')
            ->with('status', "Bag {$bag->code} received with all {$result['count']} parcel(s).");
    }

    /**
     * Quick scan from the inbound index: scan any parcel code to mark that
     * parcel received, or scan a bag code to receive every pending parcel in
     * the bag and close it at this hub.
     */
    public function scanIndex(Request $request, Notifier $notifier)
    {
        $hub = HubContext::require($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $orderId = ScanCode::orderId($validated['code']);

        if ($orderId !== null) {
            return $this->receiveScannedParcel($request, $hub->id, $orderId);
        }

        return $this->receiveScannedBag($request, $hub->id, $validated['code'], $notifier);
    }

    /**
     * Receive one parcel found by its order code.
     */
    private function receiveScannedParcel(Request $request, int $hubId, int $orderId)
    {
        $order = Order::find($orderId);

        if (! $order) {
            return $this->scanResponse($request, false, "Order #{$orderId} was not found.");
        }

        if ((int) $order->destination_hub_id !== $hubId) {
            return $this->scanResponse($request, false, "Order #{$orderId} is addressed to another hub.");
        }

        $bagItem = ShipmentBagItem::with(['bag.toHub'])
            ->where('order_id', $order->id)
            ->whereHas('bag', fn ($query) => $query
                ->where('to_hub_id', $hubId)
                ->whereIn('status', $this->receivableStatuses()))
            ->latest('id')
            ->first();

        if (! $bagItem) {
            $alreadyReceived = ShipmentBagItem::where('order_id', $order->id)
                ->whereNotNull('received_at')
                ->whereHas('bag', fn ($query) => $query->where('to_hub_id', $hubId))
                ->exists();

            if ($alreadyReceived) {
                return $this->scanResponse(
                    $request,
                    false,
                    "Order #{$orderId} was already received at this hub.",
                    reference: "#{$orderId}",
                );
            }

            return $this->scanResponse($request, false, "Order #{$orderId} is not in an incoming bag for this hub.");
        }

        if ($bagItem->received_at) {
            return $this->scanResponse(
                $request,
                false,
                "Order #{$orderId} was already received.",
                $orderId,
                reference: "#{$orderId}",
            );
        }

        $message = $this->receiveItem($bagItem->bag, $bagItem, $hubId, $request->user()->id);

        return $this->scanResponse($request, true, $message, $orderId, reference: "#{$orderId}");
    }

    /**
     * Receive every pending parcel in a bag found by its bag code.
     */
    private function receiveScannedBag(Request $request, int $hubId, string $rawCode, Notifier $notifier)
    {
        $code = ScanCode::bagCode($rawCode);

        if ($code === null) {
            return $this->scanResponse($request, false, 'That code is not a parcel or bag barcode.');
        }

        $bag = ShipmentBag::where('code', $code)->first();

        if (! $bag) {
            return $this->scanResponse(
                $request,
                false,
                "No bag found for code {$code}.",
                type: 'bag',
                reference: $code,
            );
        }

        if ((int) $bag->to_hub_id !== $hubId) {
            return $this->scanResponse(
                $request,
                false,
                "Bag {$bag->code} is addressed to another hub.",
                type: 'bag',
                reference: $bag->code,
            );
        }

        // Lock the bag so two simultaneous scans cannot both receive and
        // notify the same bag.
        $result = DB::transaction(function () use ($bag, $hubId, $request) {
            $lockedBag = ShipmentBag::with(['items.order', 'toHub'])
                ->whereKey($bag->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBag) {
                return ['state' => 'missing'];
            }

            if ($lockedBag->status === ShipmentBag::STATUS_RECEIVED) {
                return ['state' => 'received'];
            }

            if (! in_array($lockedBag->status, $this->receivableStatuses(), true)) {
                return ['state' => 'not_receivable'];
            }

            if ($lockedBag->items->isEmpty()) {
                return ['state' => 'empty'];
            }

            $pending = $lockedBag->items->whereNull('received_at');
            $total = $lockedBag->items->count();
            $pendingCount = $pending->count();

            foreach ($pending as $item) {
                $this->receiveItem($lockedBag, $item, $hubId, $request->user()->id);
            }

            // Scanning the bag receives it outright: every parcel is now
            // accounted for, so there is no discrepancy to close later.
            $lockedBag->update([
                'status' => ShipmentBag::STATUS_RECEIVED,
                'received_by' => $lockedBag->received_by ?? $request->user()->id,
                'received_at' => $lockedBag->received_at ?? now(),
            ]);

            $lockedBag->refresh()->load(['fromHub.user', 'toHub.user', 'items.order']);

            return [
                'state' => 'received_now',
                'bag' => $lockedBag,
                'total' => $total,
                'pending' => $pendingCount,
            ];
        });

        return match ($result['state']) {
            'missing' => $this->scanResponse(
                $request,
                false,
                "No bag found for code {$code}.",
                type: 'bag',
                reference: $code,
            ),
            'received' => $this->scanResponse(
                $request,
                false,
                "Bag {$bag->code} was already received.",
                type: 'bag',
                reference: $bag->code,
            ),
            'not_receivable' => $this->scanResponse(
                $request,
                false,
                "Bag {$bag->code} has not been sealed yet.",
                type: 'bag',
                reference: $bag->code,
            ),
            'empty' => $this->scanResponse(
                $request,
                false,
                "Bag {$bag->code} has no parcels to receive.",
                type: 'bag',
                reference: $bag->code,
            ),
            default => $this->bagReceivedResponse($request, $notifier, $result),
        };
    }

    /**
     * @param  array{state: string, bag: ShipmentBag, total: int, pending: int}  $result
     */
    private function bagReceivedResponse(Request $request, Notifier $notifier, array $result)
    {
        $bag = $result['bag'];
        $total = $result['total'];
        $pendingCount = $result['pending'];
        $alreadyReceived = $total - $pendingCount;

        $notifier->bagReceived($bag, $request->user()->id);

        if ($alreadyReceived === 0) {
            $message = "Bag {$bag->code} received with all {$total} parcel(s).";
        } elseif ($pendingCount === 0) {
            $message = "Bag {$bag->code} received. All {$total} parcel(s) were already scanned.";
        } else {
            $message = "Bag {$bag->code} received. {$pendingCount} parcel(s) scanned, {$alreadyReceived} already received.";
        }

        return $this->scanResponse(
            $request,
            true,
            $message,
            type: 'bag',
            reference: $bag->code,
        );
    }

    /**
     * Mark one bag item as received and put the parcel back into sorting at
     * this hub.
     */
    private function receiveItem(ShipmentBag $bag, ShipmentBagItem $item, int $hubId, int $actorId): string
    {
        return DB::transaction(function () use ($bag, $item, $hubId, $actorId) {
            $locked = ShipmentBagItem::whereKey($item->id)->lockForUpdate()->first();

            if (! $locked || $locked->received_at) {
                return 'Parcel was already confirmed.';
            }

            $locked->update(['received_at' => now()]);

            $order = $locked->order;

            if (! $order) {
                return 'Parcel confirmed.';
            }

            $bag->loadMissing('toHub');
            $hubName = $bag->toHub?->business_name ?? 'this hub';

            $transitioned = false;

            if ($order->status === Order::STATUS_AWAITING_TRANSFER) {
                $transitioned = $this->lifecycle->transition(
                    $order,
                    Order::STATUS_AWAITING_TRANSFER,
                    Order::STATUS_AT_SORTING_CENTER,
                    ['current_hub_id' => $hubId, 'logistics_profile_id' => $hubId],
                    "Arrived at {$hubName} hub.",
                    $actorId,
                    $hubId,
                    eventType: ParcelEvent::TYPE_BAG_RECEIVED,
                );
            }

            if (! $transitioned) {
                // The parcel may already be at sorting-center status, or a
                // previous status change may have won the race. Either way, the
                // receiving hub now holds it.
                $order->forceFill([
                    'current_hub_id' => $hubId,
                    'logistics_profile_id' => $hubId,
                ])->save();

                $this->lifecycle->record($order, ParcelEvent::TYPE_BAG_RECEIVED, [
                    'hub_id' => $hubId,
                    'note' => "Parcel confirmed in bag {$bag->code} at {$hubName}.",
                    'actor_user_id' => $actorId,
                ]);
            }

            return "Order #{$order->id} received at {$hubName}.";
        });
    }

    /**
     * Bag statuses that can still accept parcels.
     *
     * @return array<int, string>
     */
    private function receivableStatuses(): array
    {
        return [
            ShipmentBag::STATUS_SEALED,
            ShipmentBag::STATUS_IN_TRANSIT,
            ShipmentBag::STATUS_DISCREPANCY,
        ];
    }

    private function scanResponse(
        Request $request,
        bool $ok,
        string $message,
        ?int $orderId = null,
        string $type = 'parcel',
        ?string $reference = null,
    ) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'order_id' => $orderId,
                'type' => $type,
                'reference' => $reference ?? ($orderId ? "#{$orderId}" : null),
            ], $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('status', $message)
            : back()->withErrors(['code' => $message]);
    }

    private function verifyKey(ShipmentBag $bag): string
    {
        return "inbound.bag.{$bag->id}.verified";
    }

    private function authorizeReceiver(ShipmentBag $bag, int $hubId): void
    {
        abort_unless((int) $bag->to_hub_id === $hubId, 403, 'This bag is addressed to another hub.');
    }

    private function authorizeVerification(Request $request, ShipmentBag $bag): void
    {
        abort_unless(
            $request->session()->get($this->verifyKey($bag)),
            403,
            'Scan the bag code before receiving its parcels.'
        );
    }

    private function authorizeReceivable(ShipmentBag $bag): void
    {
        abort_unless(
            in_array($bag->status, [
                ShipmentBag::STATUS_SEALED,
                ShipmentBag::STATUS_IN_TRANSIT,
                ShipmentBag::STATUS_DISCREPANCY,
            ], true),
            422,
            'This bag is not open for receiving.'
        );
    }
}
