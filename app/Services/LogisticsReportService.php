<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @phpstan-type Report array{title: string, summary: array<int, array<string, mixed>>, secondary: array, columns: array<int, array{key: string, label: string, align: string}>, rows: iterable<int, array<string, mixed>>, chart: array<string, mixed>|null, notes: array<int, string>, hasData: bool, emptyMessage: string}
 */
class LogisticsReportService
{
    public function __construct(private RiderEarnings $earnings) {}

    /** @return array<string, string> */
    public function types(): array
    {
        return ['shipment_summary' => 'Shipment Summary', 'delivery_performance' => 'Delivery Performance',
            'failed_returns' => 'Failed Delivery / Returns', 'rider_performance' => 'Rider Performance',
            'cod_remittance' => 'COD Collections', 'shipping_earnings' => 'Shipping Fees & Earnings',
            'area_route' => 'Area / Route', 'hub_throughput' => 'Hub Throughput', 'damaged_lost' => 'Damage & Missing Parcels'];
    }

    /** @param array<string, mixed> $filters
     * @return Report
     */
    public function build(?int $hubId, Carbon $from, Carbon $to, string $type, array $filters = [], bool $paginate = false): array
    {
        if (! $hubId) {
            return $this->report($this->types()[$type] ?? 'Report', [], [], [], null, ['No hub context is available.']);
        }

        return match ($type) {
            'delivery_performance' => $this->delivery($hubId, $from, $to, $filters),
            'failed_returns' => $this->incidents($hubId, $from, $to, $filters, false),
            'damaged_lost' => $this->incidents($hubId, $from, $to, $filters, true),
            'shipping_earnings' => $this->shippingEarnings($hubId, $from, $to, $filters, $paginate),
            'cod_remittance' => $this->money($hubId, $from, $to, $filters, $type),
            'rider_performance' => $this->riderPerformance($hubId, $from, $to, $filters),
            'area_route' => $this->areas($hubId, $from, $to, $filters),
            'hub_throughput' => $this->throughput($hubId, $from, $to, $filters),
            default => $this->shipments($hubId, $from, $to, $filters),
        };
    }

    public function scopedOrders(int $hubId, array $filters = [], bool $deliveryOnly = false): Builder
    {
        $query = Order::query()->where(function (Builder $query) use ($hubId, $deliveryOnly): void {
            if ($deliveryOnly) {
                $query->where('destination_hub_id', $hubId)->orWhere(fn (Builder $legacy) => $legacy->whereNull('destination_hub_id')->where('logistics_profile_id', $hubId));
            } else {
                $query->where('logistics_profile_id', $hubId)->orWhere('origin_hub_id', $hubId)->orWhere('destination_hub_id', $hubId)->orWhere('current_hub_id', $hubId)
                    ->orWhereHas('parcelEvents', fn (Builder $events) => $events->where('hub_id', $hubId));
            }
        });
        foreach (['status' => 'status', 'rider_id' => 'delivery_rider_id', 'seller_id' => 'seller_id'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        if (! empty($filters['origin_area'])) {
            $query->whereHas('seller.sellerProfile', fn (Builder $profile) => $profile->where('municipality', $filters['origin_area']));
        }
        if (! empty($filters['destination_area'])) {
            $query->where(fn (Builder $destination) => $destination->where('ship_municipality', $filters['destination_area'])->orWhere('delivery_area', $filters['destination_area']));
        }

        return $query;
    }

    public function ridersForHub(int $hubId): Builder
    {
        return CourierProfile::where('logistics_profile_id', $hubId)
            ->orWhereIn('id', Order::where('earning_hub_id', $hubId)->whereNotNull('earning_courier_id')->select('earning_courier_id'));
    }

    public function filterOptions(int $hubId): array
    {
        $orders = $this->scopedOrders($hubId);
        $sellers = User::whereIn('id', (clone $orders)->select('seller_id'))->with('sellerProfile')->orderBy('name')->get();

        return ['riders' => $this->ridersForHub($hubId)->orderBy('last_name')->get(),
            'sellers' => $sellers,
            'origins' => $sellers->map(fn (User $seller) => $seller->sellerProfile?->municipality)->filter()->unique()->sort()->values(),
            'destinations' => (clone $orders)->pluck('ship_municipality')->merge((clone $orders)->pluck('delivery_area'))->filter()->unique()->sort()->values()];
    }

    private function events(int $hubId, Carbon $from, Carbon $to, array $filters): Builder
    {
        $events = ParcelEvent::where('hub_id', $hubId)->whereBetween('created_at', [$from, $to])
            ->whereIn('order_id', $this->scopedOrders($hubId, $filters)->select('orders.id'));
        if (! empty($filters['rider_id'])) {
            $events->where('rider_id', $filters['rider_id']);
        }

        return $events;
    }

    /** @return Report */
    private function report(string $title, array $summary, array $columns, array $rows, ?array $chart = null, array $notes = []): array
    {
        return ['title' => $title, 'summary' => $summary, 'secondary' => [], 'columns' => $columns, 'rows' => $rows,
            'chart' => $chart, 'notes' => $notes, 'hasData' => count($rows) > 0,
            'emptyMessage' => 'No matching activity in this period. Try a different date range or clear the filters.'];
    }

    private function cards(array $metrics): array
    {
        $cards = [];
        foreach ($metrics as $label => $value) {
            $cards[] = ['label' => $label, 'value' => $value, 'sub' => '', 'accent' => count($cards) === 0];
        }

        return $cards;
    }

    private function columns(array $labels): array
    {
        $columns = [];
        foreach ($labels as $key => $label) {
            $columns[] = ['key' => $key, 'label' => $label, 'align' => in_array($key, ['count', 'share', 'amount', 'fees', 'pay', 'margin', 'volume', 'delivered', 'failed', 'attempts', 'parcels'], true) ? 'right' : 'left'];
        }

        return $columns;
    }

    private function chart(string $title, array $labels, array $datasets, string $type = 'bar'): array
    {
        return ['type' => $type, 'title' => $title, 'subtitle' => 'Selected hub and filters', 'labels' => $labels, 'datasets' => $datasets];
    }

    private function percent(int $part, int $total): string
    {
        return ($total ? round($part / $total * 100, 1) : 0).'%';
    }

    private function currency(float $amount): string
    {
        return 'PHP '.number_format($amount, 2);
    }

    private function averageDays(Collection $orders): string
    {
        $timed = $orders->filter(fn (Order $order) => $order->delivered_at && $order->created_at && $order->delivered_at->gte($order->created_at));

        return $timed->isEmpty() ? 'Not recorded' : round($timed->avg(fn (Order $order) => $order->created_at->diffInHours($order->delivered_at)) / 24, 2).' days';
    }

    private function days(Carbon $from, Carbon $to, Collection $totals): Collection
    {
        $days = collect();
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->format('Y-m-d');
            $days->put($key, $totals->get($key, 0));
        }

        return $days;
    }

    private function shipments(int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->scopedOrders($hubId, $filters)->whereBetween('created_at', [$from, $to])->get();
        $groups = ['Pending' => Order::GROUP_TO_SHIP, 'In transit' => Order::GROUP_TO_DELIVER, 'Delivered' => Order::GROUP_COMPLETED, 'Cancelled' => Order::GROUP_CANCELLED, 'Returned' => Order::GROUP_RETURN_REFUND];
        $rows = [];
        foreach ($groups as $label => $statuses) {
            $count = $orders->whereIn('status', $statuses)->count();
            if ($count) {
                $rows[] = ['status' => $label, 'count' => $count, 'share' => $this->percent($count, $orders->count())];
            }
        }

        return $this->report('Shipment Summary', $this->cards(['Shipments' => $orders->count(), 'Delivered' => $orders->whereIn('status', Order::GROUP_COMPLETED)->count(), 'In transit' => $orders->whereIn('status', Order::GROUP_TO_DELIVER)->count(), 'Delivery rate' => $this->percent($orders->whereIn('status', Order::GROUP_COMPLETED)->count(), $orders->count())]),
            $this->columns(['status' => 'Current status', 'count' => 'Shipments', 'share' => 'Share']), $rows,
            $this->chart('Shipments by status', array_column($rows, 'status'), [['label' => 'Shipments', 'data' => array_column($rows, 'count')]], 'doughnut'),
            ['Cohort: orders created in the period. Status is the current status, not a historical end-of-period snapshot.']);
    }

    private function delivery(int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->scopedOrders($hubId, $filters, true)->whereBetween('delivered_at', [$from, $to])->with(['deliveryRider', 'deliveryProof'])->get();
        $orders = $orders->filter(fn (Order $order) => empty($filters['proof']) || ($filters['proof'] === 'with' ? $order->deliveryProof !== null : $order->deliveryProof === null));
        $first = $orders->where('delivery_attempts', 0)->count();
        $rows = $orders->map(fn (Order $order) => ['proof' => $this->proofLabel($order, $hubId), 'proof_order' => $order->deliveryProof && (int) $order->deliveryProof->logistics_profile_id === $hubId && ! $order->deliveryProof->expired_at ? $order->order_number : null, 'order' => '#'.$order->order_number, 'date' => $order->delivered_at->format('Y-m-d H:i'), 'rider' => $order->deliveryRider?->full_name ?? 'Unassigned', 'attempts' => (int) $order->delivery_attempts, 'time' => $this->averageDays(collect([$order]))])->all();
        $daily = $orders->groupBy(fn (Order $order) => $order->delivered_at->format('Y-m-d'))->sortKeys()->map->count();
        $daily = $this->days($from, $to, $daily);

        return $this->report('Delivery Performance', $this->cards(['Delivered' => $orders->count(), 'First-attempt success' => $this->percent($first, $orders->count()), 'Required reattempts' => $orders->count() - $first, 'Average delivery time' => $this->averageDays($orders)]),
            $this->columns(['order' => 'Order', 'date' => 'Delivered at', 'rider' => 'Delivery rider', 'attempts' => 'Failed attempts', 'time' => 'Order to delivery', 'proof' => 'Proof of delivery']), $rows,
            $this->chart('Deliveries by day', $daily->keys()->all(), [['label' => 'Delivered', 'data' => $daily->values()->all()]], 'line'),
            ['Based on delivered_at, including orders created before the period. Delivery is credited to the destination hub.', 'delivery_attempts records failed attempts. Zero means delivered without a failed attempt. No delivery deadline is stored, so an on-time/late SLA cannot be calculated.']);
    }

    private function proofLabel(Order $order, int $hubId): string
    {
        if (! $order->deliveryProof) {
            return 'No proof (before feature)';
        }
        if ((int) $order->deliveryProof->logistics_profile_id !== $hubId) {
            return 'Restricted to delivery hub';
        }

        return $order->deliveryProof->expired_at ? 'Proof expired' : 'View proof';
    }

    private function shippingEarnings(int $hubId, Carbon $from, Carbon $to, array $filters, bool $paginate): array
    {
        $query = $this->earnings->deliveries($hubId, isset($filters['rider_id']) ? (int) $filters['rider_id'] : null, $from, $to);
        foreach (['status', 'seller_id'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }
        if (! empty($filters['origin_area'])) {
            $query->whereHas('seller.sellerProfile', fn (Builder $profile) => $profile->where('municipality', $filters['origin_area']));
        }
        if (! empty($filters['destination_area'])) {
            $query->where(fn (Builder $destination) => $destination->where('ship_municipality', $filters['destination_area'])->orWhere('delivery_area', $filters['destination_area']));
        }
        if (($filters['proof'] ?? '') === 'with') {
            $query->whereHas('deliveryProof');
        } elseif (($filters['proof'] ?? '') === 'missing') {
            $query->whereDoesntHave('deliveryProof');
        }
        $totals = $this->earnings->totals($query);
        $daily = (clone $query)->toBase()->selectRaw('DATE(delivered_at) AS day, COUNT(*) AS parcels')->groupByRaw('DATE(delivered_at)')->pluck('parcels', 'day');
        $daily = $this->days($from, $to, $daily)->map(fn ($count) => (int) $count);
        $groups = (clone $query)->toBase()->selectRaw('COALESCE(earning_courier_id, delivery_rider_id) AS courier_id, COUNT(*) AS parcels, SUM(rider_pay) AS rider_pay')
            ->groupByRaw('COALESCE(earning_courier_id, delivery_rider_id)')->get();
        $couriers = CourierProfile::whereIn('id', $groups->pluck('courier_id')->filter())->get()->keyBy('id');
        $riders = $groups->map(fn ($group) => ['rider' => $couriers->get($group->courier_id)?->full_name ?? 'Unassigned',
            'barangay' => $couriers->get($group->courier_id)?->barangay ?? '-', 'parcels' => (int) $group->parcels,
            'pay' => $group->rider_pay === null ? '-' : $this->currency((float) $group->rider_pay)])->all();
        $details = (clone $query)->with(['earningCourier', 'deliveryRider', 'deliveryProof'])->orderByDesc('delivered_at')->orderByDesc('id');
        $paginator = $paginate ? $details->paginate(25)->withQueryString() : null;
        $rows = ($paginator ? $paginator->getCollection() : $details->lazy(250))->map(fn (Order $order) => [
            'proof' => $this->proofLabel($order, $hubId), 'proof_order' => $order->deliveryProof && (int) $order->deliveryProof->logistics_profile_id === $hubId && ! $order->deliveryProof->expired_at ? $order->order_number : null,
            'date' => $order->delivered_at->format('Y-m-d H:i'), 'order' => '#'.$order->order_number,
            'rider' => ($order->earnings_recorded_at ? $order->earningCourier : $order->deliveryRider)?->full_name ?? 'Unassigned',
            'fees' => $order->delivered_shipping_fee === null ? '-' : $this->currency((float) $order->delivered_shipping_fee),
            'pay' => $order->rider_pay === null ? '-' : $this->currency((float) $order->rider_pay),
            'margin' => $order->rider_pay === null || $order->delivered_shipping_fee === null ? '-' : $this->currency((float) $order->delivered_shipping_fee - (float) $order->rider_pay),
        ]);
        if ($paginator) {
            $paginator->setCollection($rows);
        }
        $money = fn (?string $amount) => $amount === null ? ($totals['deliveries'] === 0 ? $this->currency(0) : '-') : $this->currency((float) $amount);

        return ['title' => 'Shipping Fees & Earnings', 'summary' => $this->cards(['Parcels delivered' => $totals['deliveries'],
            'Shipping fees collected' => $money($totals['shipping_fees']), 'Rider payouts owed' => $money($totals['rider_pay']), 'Net margin' => $money($totals['margin'])]),
            'secondary' => $riders, 'columns' => $this->columns(['date' => 'Date', 'order' => 'Order', 'rider' => 'Rider', 'fees' => 'Shipping fee', 'pay' => 'Rider pay', 'margin' => 'Margin', 'proof' => 'Proof of delivery']),
            'rows' => $rows, 'paginator' => $paginator, 'chart' => $this->chart('Parcels delivered per day', $daily->keys()->all(), [['label' => 'Delivered', 'data' => $daily->values()->all()]], 'line'),
            'notes' => ['Delivery is credited to the recorded holding hub and courier. Legacy deliveries use their destination hub and assigned rider for parcel counts only.',
                'Money totals include recorded delivery snapshots only. Missing pay and margins are shown as -; no old orders are backfilled.',
                $totals['snapshots'].' of '.$totals['deliveries'].' deliveries have shipping snapshots; '.$totals['paid_snapshots'].' have rider pay. Net margin is unavailable if any shipping snapshot has no rider pay.'],
            'hasData' => $totals['deliveries'] > 0, 'emptyMessage' => 'No delivered parcels in this period. Try a different date range or clear the filters.'];
    }

    private function money(int $hubId, Carbon $from, Carbon $to, array $filters, string $type): array
    {
        $cash = $type === 'cod_remittance';
        $date = $cash ? 'cod_collected_at' : 'delivered_at';
        $orders = $this->scopedOrders($hubId, $filters, true)->whereBetween($date, [$from, $to]);
        if ($cash) {
            $orders->whereNotNull('cod_collected_amount');
        }
        if (! $cash) {
            $orders->whereNotNull('cod_collected_at')->whereIn('status', Order::GROUP_SHIPPING_EARNED);
        }
        $orders = $orders->get();
        $total = (float) $orders->sum($cash ? 'cod_collected_amount' : 'shipping_fee');
        $rows = $orders->sortBy($date)->map(fn (Order $order) => ['order' => '#'.$order->order_number, 'date' => $order->{$date}->format('Y-m-d H:i'), 'amount' => $this->currency((float) ($cash ? $order->cod_collected_amount : $order->shipping_fee)), 'status' => $order->status, 'rider' => $order->delivery_rider_id ?? 'Unassigned'])->values()->all();
        $daily = $orders->groupBy(fn (Order $order) => $order->{$date}->format('Y-m-d'))->sortKeys()->map(fn (Collection $group) => round((float) $group->sum($cash ? 'cod_collected_amount' : 'shipping_fee'), 2));
        $daily = $this->days($from, $to, $daily);
        $notes = $cash ? ['Based on cod_collected_at and cash actually recorded. Order completion does not prove remittance; remittance transactions are not tracked by this application.']
            : ['Based on delivered_at for destination-hub deliveries with cash collected, matching the deliveries earnings page. Only recorded shipping fees are counted; no estimated reattempt surcharge is added.'];

        return $this->report($cash ? 'COD Collections' : 'Shipping Fees & Earnings', $this->cards([$cash ? 'Cash collected' : 'Shipping fees earned' => $this->currency($total), 'Parcels' => $orders->count(), 'Average per parcel' => $this->currency($orders->count() ? $total / $orders->count() : 0)]),
            $this->columns(['order' => 'Order', 'date' => $cash ? 'Collected at' : 'Delivered at', 'amount' => $cash ? 'Recorded COD' : 'Shipping fee', 'status' => 'Current status']), $rows,
            $this->chart($cash ? 'Cash collected by day' : 'Shipping fees by day', $daily->keys()->all(), [['label' => 'PHP', 'data' => $daily->values()->all()]]), $notes);
    }

    private function riderPerformance(int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->scopedOrders($hubId, $filters, true)->where(function (Builder $query) use ($hubId, $from, $to): void {
            $query->whereBetween('delivered_at', [$from, $to])->orWhereHas('parcelEvents', fn (Builder $events) => $events->where('hub_id', $hubId)->whereBetween('created_at', [$from, $to])->whereIn('event_type', [ParcelEvent::TYPE_ASSIGNED, ParcelEvent::TYPE_OUT_FOR_DELIVERY, ParcelEvent::TYPE_DELIVERY_ATTEMPT]));
        })->with('deliveryRider')->get();
        $rows = [];
        foreach ($orders->groupBy('delivery_rider_id') as $riderId => $items) {
            $delivered = $items->filter(fn (Order $order) => $order->delivered_at && $order->delivered_at->between($from, $to));
            $rows[] = ['rider' => $items->first()->deliveryRider?->full_name ?? 'Unassigned', 'volume' => $items->count(), 'delivered' => $delivered->count(), 'failed' => $items->where('status', Order::STATUS_DELIVERY_FAILED)->count(), 'rate' => $this->percent($delivered->count(), $items->count()), 'time' => $this->averageDays($delivered)];
        }

        return $this->report('Rider Performance', $this->cards(['Riders / assignments' => count($rows), 'Parcels with activity' => $orders->count(), 'Delivered in period' => array_sum(array_column($rows, 'delivered'))]),
            $this->columns(['rider' => 'Current delivery rider', 'volume' => 'Active parcels', 'delivered' => 'Delivered', 'failed' => 'Currently failed', 'rate' => 'Success rate', 'time' => 'Average delivery time']), $rows,
            $this->chart('Parcels by rider', array_column($rows, 'rider'), [['label' => 'Active parcels', 'data' => array_column($rows, 'volume')], ['label' => 'Delivered', 'data' => array_column($rows, 'delivered')]]),
            ['Parcels delivered or with assignment / delivery-attempt events in the period, grouped by current delivery rider. Reassigned parcels may have historical events from a previous rider.']);
    }

    private function areas(int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->scopedOrders($hubId, $filters)->whereBetween('created_at', [$from, $to])->get();
        $rows = [];
        foreach ($orders->groupBy(fn (Order $order) => ($order->ship_province ?: 'Unknown').' / '.($order->ship_municipality ?: 'Unknown')) as $area => $items) {
            $delivered = $items->whereIn('status', Order::GROUP_COMPLETED);
            $rows[] = ['area' => $area, 'volume' => $items->count(), 'delivered' => $delivered->count(), 'rate' => $this->percent($delivered->count(), $items->count()), 'time' => $this->averageDays($delivered)];
        }
        usort($rows, fn (array $a, array $b) => $b['volume'] <=> $a['volume']);

        return $this->report('Area / Route', $this->cards(['Destinations' => count($rows), 'Shipments' => $orders->count(), 'Delivered' => array_sum(array_column($rows, 'delivered'))]),
            $this->columns(['area' => 'Destination province / city', 'volume' => 'Volume', 'delivered' => 'Delivered', 'rate' => 'Delivery rate', 'time' => 'Average delivery time']), $rows,
            $this->chart('Shipments by destination', array_column($rows, 'area'), [['label' => 'Shipments', 'data' => array_column($rows, 'volume')]]),
            ['Cohort: orders created in the period. Destination uses the saved shipping snapshot; cities in different provinces are kept separate. Pickup-city filters use the seller profile because no pickup-address snapshot is stored.']);
    }

    private function incidents(int $hubId, Carbon $from, Carbon $to, array $filters, bool $damage): array
    {
        $types = $damage ? [ParcelEvent::TYPE_BAG_MISSING] : [ParcelEvent::TYPE_DELIVERY_ATTEMPT, ParcelEvent::TYPE_RETURNED];
        $events = $this->events($hubId, $from, $to, $filters)->with('order')->whereIn('event_type', $types)->orderByDesc('created_at')->get();
        $rows = $events->map(fn (ParcelEvent $event) => ['order' => '#'.$event->order?->order_number, 'incident' => ParcelEvent::TYPE_LABELS[$event->event_type], 'reason' => $event->note ?: 'No note recorded', 'date' => $event->created_at->format('Y-m-d H:i')])->all();
        if ($damage) {
            $claims = OrderReturn::with('order')->where('reason', 'damaged')->whereBetween('created_at', [$from, $to])->whereIn('order_id', $this->scopedOrders($hubId, $filters)->select('orders.id'))->get();
            foreach ($claims as $claim) {
                $rows[] = ['order' => '#'.$claim->order?->order_number, 'incident' => 'Buyer-reported damage', 'reason' => $claim->details.' ('.$claim->status.')', 'date' => $claim->created_at->format('Y-m-d H:i')];
            }
        }
        $grouped = collect($rows)->groupBy('incident')->map->count();

        return $this->report($damage ? 'Damage & Missing Parcels' : 'Failed Delivery / Returns', $this->cards(['Recorded incidents' => count($rows), 'Distinct parcels' => collect($rows)->pluck('order')->unique()->count()]),
            $this->columns(['order' => 'Order', 'incident' => 'Incident', 'reason' => 'Recorded reason / note', 'date' => 'Recorded at']), $rows,
            $this->chart('Recorded incidents', $grouped->keys()->all(), [['label' => 'Incidents', 'data' => $grouped->values()->all()]]),
            $damage ? ['Missing entries are bag discrepancy events, not confirmed losses. Damage entries are buyer claims, not proof of carrier fault. Cancelled / returned orders are not automatically classified as damaged or lost.']
                : ['Uses failed-attempt and returned events recorded at this hub during the period, including parcels subsequently delivered. Multiple failed attempts remain separate incidents.']);
    }

    private function throughput(int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $events = $this->events($hubId, $from, $to, $filters)->get();
        $received = $events->whereIn('event_type', [ParcelEvent::TYPE_BAG_RECEIVED, ParcelEvent::TYPE_PICKED_UP])->pluck('order_id')->unique()->count();
        $sorted = $events->where('event_type', ParcelEvent::TYPE_SORTED)->pluck('order_id')->unique()->count();
        $dispatched = $events->whereIn('event_type', [ParcelEvent::TYPE_BAG_DISPATCHED, ParcelEvent::TYPE_OUT_FOR_DELIVERY])->pluck('order_id')->unique()->count();
        $bags = ShipmentBag::with(['fromHub', 'toHub'])->withCount(['items' => fn (Builder $items) => $items->whereIn('order_id', $this->scopedOrders($hubId, $filters)->select('orders.id'))])
            ->where(fn (Builder $query) => $query->where(fn (Builder $outbound) => $outbound->where('from_hub_id', $hubId)->whereBetween('dispatched_at', [$from, $to]))
                ->orWhere(fn (Builder $inbound) => $inbound->where('to_hub_id', $hubId)->whereBetween('received_at', [$from, $to])))
            ->whereHas('items', fn (Builder $items) => $items->whereIn('order_id', $this->scopedOrders($hubId, $filters)->select('orders.id')))->get();
        $rows = $bags->map(fn (ShipmentBag $bag) => ['code' => $bag->code, 'direction' => $bag->from_hub_id === $hubId ? 'Outbound' : 'Inbound', 'hub' => ($bag->from_hub_id === $hubId ? $bag->toHub : $bag->fromHub)?->business_name ?? 'Unknown', 'status' => $bag->status_label, 'parcels' => $bag->items_count, 'date' => ($bag->from_hub_id === $hubId ? $bag->dispatched_at : $bag->received_at)->format('Y-m-d H:i')])->all();
        if (empty($rows) && $events->isNotEmpty()) {
            $rows[] = ['code' => 'Direct parcel activity', 'direction' => 'Hub processing', 'hub' => '-', 'status' => '-', 'parcels' => $events->pluck('order_id')->unique()->count(), 'date' => $from->format('Y-m-d').' to '.$to->format('Y-m-d')];
        }

        return $this->report('Hub Throughput', $this->cards(['Parcels received' => $received, 'Parcels sorted' => $sorted, 'Parcels dispatched' => $dispatched, 'Transfer bags' => $bags->count()]),
            $this->columns(['code' => 'Bag / activity', 'direction' => 'Direction', 'hub' => 'Counterpart hub', 'status' => 'Current bag status', 'parcels' => 'Matching parcels', 'date' => 'Activity date']), $rows,
            $this->chart('Hub processing', ['Received', 'Sorted', 'Dispatched'], [['label' => 'Distinct parcels', 'data' => [$received, $sorted, $dispatched]]]),
            ['Parcel totals count distinct parcels with recorded processing events at this hub in the period. Bags use their actual dispatch / receipt dates and matching parcel counts; empty bags are excluded.']);
    }
}
