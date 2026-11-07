<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\ShipmentBagItem;
use App\Models\SortingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LogisticsDashboardService
{
    public function build(LogisticsProfile $hub, string $range, string $queue): array
    {
        $now = now();
        $from = $now->copy()->subDays(match ($range) {
            'today' => 0, '30d' => 29, default => 6
        })->startOfDay();
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $periodLabel = match ($range) {
            'today' => 'Today', '30d' => 'Last 30 days', default => 'Last 7 days'
        };
        $orders = Order::heldBy($hub->id);
        $statusCounts = (clone $orders)->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $events = ParcelEvent::where('hub_id', $hub->id)->where('event_type', '!=', ParcelEvent::TYPE_STALE)->where('created_at', '<=', $now);
        $periodEvents = (clone $events)->where('created_at', '>=', $from);
        $daily = (clone $periodEvents)->selectRaw('DATE(created_at) as day, COUNT(DISTINCT order_id) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        $history = (clone $events)->where('created_at', '>=', $yesterday)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(DISTINCT CASE WHEN to_status = ? THEN order_id END) as sorting', [Order::STATUS_AT_SORTING_CENTER])
            ->selectRaw('COUNT(DISTINCT CASE WHEN to_status = ? THEN order_id END) as delivery', [Order::STATUS_OUT_FOR_DELIVERY])
            ->selectRaw('COUNT(DISTINCT CASE WHEN event_type = ? THEN order_id END) as received', [ParcelEvent::TYPE_BAG_RECEIVED])
            ->groupByRaw('DATE(created_at)')->get()->keyBy('day');
        $activity = (clone $periodEvents)->select('to_status')->selectRaw('COUNT(DISTINCT order_id) as total')->groupBy('to_status')->pluck('total', 'to_status');

        $inbound = ShipmentBag::addressedTo($hub->id)->whereIn('status', [ShipmentBag::STATUS_SEALED, ShipmentBag::STATUS_IN_TRANSIT]);
        $inboundExpected = ShipmentBagItem::whereIn('bag_id', (clone $inbound)->select('id'))->whereNull('received_at')->count();
        $outbound = ShipmentBag::sentBy($hub->id)->where('status', ShipmentBag::STATUS_OPEN);
        $bagActivity = $this->datedCounts(ShipmentBag::sentBy($hub->id), 'created_at', $from, $now, $today, $yesterday);
        $applicationActivity = $this->datedCounts(CourierProfile::where('logistics_profile_id', $hub->id), 'created_at', $from, $now, $today, $yesterday);
        $deliveries = $this->datedCounts((clone $orders)->whereNotNull('delivered_at'), 'delivered_at', $from, $now, $today, $yesterday);
        $pending = CourierProfile::where('logistics_profile_id', $hub->id)->where('status', 'pending')->count();
        $staleHours = max(1, (int) config('logistics.stale_parcel_hours', 24));
        $cutoff = $now->copy()->subHours($staleHours);
        $attention = [
            ['label' => 'Quiet for '.$staleHours.'+ hours', 'value' => $this->staleOrders($hub->id, $cutoff)->count(), 'queue' => 'stale'],
            ['label' => 'Inbound bags outstanding', 'value' => (clone $inbound)->count(), 'queue' => 'inbound'],
            ['label' => 'Failed deliveries', 'value' => (int) $statusCounts->get(Order::STATUS_DELIVERY_FAILED, 0), 'queue' => 'failed'],
            ['label' => 'Returned parcels', 'value' => (int) $statusCounts->get(Order::STATUS_RETURNED, 0), 'queue' => 'returned'],
        ];

        $cards = [
            $this->card('Parcels waiting to sort', (int) $statusCounts->get(Order::STATUS_AT_SORTING_CENTER, 0), 'sorting', 'sorting', 'Waiting now',
                number_format((int) $activity->get(Order::STATUS_AT_SORTING_CENTER, 0)).' arrived in range', $this->eventTrend($history, $today, $yesterday, 'sorting'), 'Arrivals today'),
            $this->card('Inbound expected', $inboundExpected, 'inbound', 'inbound', 'Unreceived parcels in incoming bags',
                number_format((clone $periodEvents)->where('event_type', ParcelEvent::TYPE_BAG_RECEIVED)->distinct()->count('order_id')).' received in range',
                $this->eventTrend($history, $today, $yesterday, 'received'), 'Received today'),
            $this->card('Outbound bags open', (clone $outbound)->count(), 'outbound', 'outbound', 'Open now',
                number_format($bagActivity['period']).' bags created in range', $bagActivity, 'Bags created today'),
            $this->card('Parcels out for delivery', (int) $statusCounts->get(Order::STATUS_OUT_FOR_DELIVERY, 0), 'delivery', 'deliveries', 'On the road now',
                number_format((int) $activity->get(Order::STATUS_OUT_FOR_DELIVERY, 0)).' sent out in range',
                $this->eventTrend($history, $today, $yesterday, 'delivery'), 'Sent out today'),
            $this->card($range === 'today' ? 'Delivered today' : 'Delivered in range', $deliveries['period'], 'delivered', 'deliveries', $periodLabel,
                'Recorded delivery dates', $deliveries, 'Delivered today'),
            $this->card('Pending rider applications', $pending, 'applications', 'riders', 'Awaiting a decision now',
                number_format($applicationActivity['period']).' applications received in range', $applicationActivity, 'Applications today'),
        ];
        foreach ($cards as &$card) {
            $card['href'] = route('logistics.dashboard', ['range' => $range, 'queue' => $card['queue']]).'#work-queue';
        }
        unset($card);
        $chartDays = [];
        for ($day = $from->copy(); $day->lte($today); $day->addDay()) {
            $chartDays[] = ['label' => $day->format('M j'), 'count' => (int) $daily->get($day->format('Y-m-d'), 0)];
        }
        $breakdown = (clone $orders)->whereIn('id', (clone $periodEvents)->select('order_id'))
            ->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $activeSession = SortingSession::forHub($hub->id)->active()->withCount([
            'scans', 'scans as sorted_count' => fn (Builder $query) => $query->where('result', 'sorted'),
            'scans as review_count' => fn (Builder $query) => $query->where('result', 'needs_review'),
        ])->latest('started_at')->first();

        return [
            'hub' => $hub, 'now' => $now, 'from' => $from, 'range' => $range, 'queue' => $queue, 'periodLabel' => $periodLabel,
            'cards' => $cards, 'attention' => $attention, 'staleHours' => $staleHours,
            'activeSession' => $activeSession, 'riders' => $this->riders($hub->id),
            'hasServiceAreas' => $hub->serviceAreas()->exists(),
            'workQueue' => $this->workQueue($hub->id, $queue, $from, $now, $cutoff),
            'handledTotal' => (clone $periodEvents)->distinct()->count('order_id'),
            'chartData' => [
                'daily' => ['labels' => array_column($chartDays, 'label'), 'values' => array_column($chartDays, 'count')],
                'statuses' => ['labels' => $breakdown->keys()->map(fn (string $status): string => Order::STATUS_LABELS[$status] ?? $status)->all(), 'values' => $breakdown->values()->map(fn ($count): int => (int) $count)->all()],
            ],
        ];
    }

    /**
     * @param  array{today: int, yesterday: int, period?: int}  $trend
     * @return array{label: string, value: int, queue: string, icon: string, hint: string, activity: string, trend_direction: int, trend: string}
     */
    private function card(string $label, int $value, string $queue, string $icon, string $hint, string $activity, array $trend, string $trendLabel): array
    {
        $difference = $trend['today'] - $trend['yesterday'];

        return compact('label', 'value', 'queue', 'icon', 'hint', 'activity') + [
            'trend_direction' => $difference <=> 0,
            'trend' => $trendLabel.': '.number_format($trend['today']).' ('.($difference > 0 ? '+' : '').number_format($difference).' vs yesterday)',
        ];
    }

    /** @return array{today: int, yesterday: int} */
    private function eventTrend(Collection $history, Carbon $today, Carbon $yesterday, string $metric): array
    {
        return [
            'today' => (int) ($history->get($today->format('Y-m-d'))?->{$metric} ?? 0),
            'yesterday' => (int) ($history->get($yesterday->format('Y-m-d'))?->{$metric} ?? 0),
        ];
    }

    /** @return array{period: int, today: int, yesterday: int} */
    private function datedCounts(Builder $query, string $column, Carbon $from, Carbon $now, Carbon $today, Carbon $yesterday): array
    {
        $totals = $query->selectRaw('COUNT(CASE WHEN '.$column.' BETWEEN ? AND ? THEN 1 END) as period', [$from, $now])
            ->selectRaw('COUNT(CASE WHEN '.$column.' BETWEEN ? AND ? THEN 1 END) as today', [$today, $now])
            ->selectRaw('COUNT(CASE WHEN '.$column.' >= ? AND '.$column.' < ? THEN 1 END) as yesterday', [$yesterday, $today])->first();

        return ['period' => (int) $totals->period, 'today' => (int) $totals->today, 'yesterday' => (int) $totals->yesterday];
    }

    private function staleOrders(int $hubId, Carbon $cutoff): Builder
    {
        return Order::heldBy($hubId)->nonFinal()->where('updated_at', '<=', $cutoff)
            ->whereDoesntHave('parcelEvents', fn (Builder $events) => $events->where('event_type', '!=', ParcelEvent::TYPE_STALE)->where('created_at', '>', $cutoff));
    }

    private function riders(int $hubId): Collection
    {
        $rider = new CourierProfile;
        $load = Order::heldBy($hubId)->where(function (Builder $query) use ($rider): void {
            $query->where(fn (Builder $delivery) => $delivery->whereColumn('delivery_rider_id', $rider->qualifyColumn('id'))
                ->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERY_FAILED]))
                ->orWhere(fn (Builder $pickup) => $pickup->whereColumn('pickup_rider_id', $rider->qualifyColumn('id'))
                    ->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP]));
        })->selectRaw('COUNT(*)');

        return CourierProfile::where('logistics_profile_id', $hubId)->where('status', 'approved')->where('is_active', true)
            ->select(['id', 'first_name', 'last_name', 'barangay'])->selectSub($load, 'carrying_count')
            ->orderBy('carrying_count')->orderBy('last_name')->orderBy('id')->limit(6)->get();
    }

    private function workQueue(int $hubId, string $queue, Carbon $from, Carbon $now, Carbon $cutoff): Collection
    {
        $rows = collect();
        if (! in_array($queue, ['inbound', 'outbound', 'applications'], true)) {
            $orders = $queue === 'stale' ? $this->staleOrders($hubId, $cutoff) : Order::heldBy($hubId);
            match ($queue) {
                'sorting' => $orders->where('status', Order::STATUS_AT_SORTING_CENTER),
                'delivery' => $orders->where('status', Order::STATUS_OUT_FOR_DELIVERY),
                'delivered' => $orders->whereBetween('delivered_at', [$from, $now]),
                'failed' => $orders->where('status', Order::STATUS_DELIVERY_FAILED),
                'returned' => $orders->where('status', Order::STATUS_RETURNED),
                'stale' => $orders,
                default => $orders->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP, Order::STATUS_AT_SORTING_CENTER, Order::STATUS_SORTED, Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERY_FAILED, Order::STATUS_RETURNED]),
            };
            foreach ($orders->orderByDesc('updated_at')->orderByDesc('id')->limit(10)->get(['id', 'status', 'ship_barangay', 'ship_municipality', 'updated_at']) as $order) {
                $rows->push([
                    'reference' => 'Parcel #'.$order->id, 'kind' => 'Parcel', 'status' => $order->status, 'domain' => 'order',
                    'detail' => collect([$order->ship_barangay, $order->ship_municipality])->filter()->join(', ') ?: 'Destination not recorded',
                    'quantity' => 1, 'updated_at' => $order->updated_at, 'href' => $this->orderLink($order),
                ]);
            }
        }
        if (in_array($queue, ['all', 'inbound', 'outbound'], true)) {
            $bags = ShipmentBag::query()->where(function (Builder $bags) use ($hubId, $queue): void {
                if ($queue !== 'outbound') {
                    $bags->where(fn (Builder $incoming) => $incoming->where('to_hub_id', $hubId)->whereIn('status', [ShipmentBag::STATUS_SEALED, ShipmentBag::STATUS_IN_TRANSIT]));
                }
                if ($queue !== 'inbound') {
                    $bags->orWhere(fn (Builder $outgoing) => $outgoing->where('from_hub_id', $hubId)->where('status', ShipmentBag::STATUS_OPEN));
                }
            })->with(['fromHub:id,business_name', 'toHub:id,business_name'])->withCount([
                'items', 'items as outstanding_count' => fn (Builder $items) => $items->whereNull('received_at'),
            ])->latest('updated_at')->orderByDesc('id')->limit(10)->get();
            foreach ($bags as $bag) {
                $incoming = (int) $bag->to_hub_id === $hubId;
                $rows->push([
                    'reference' => $bag->code, 'kind' => $incoming ? 'Inbound bag' : 'Outbound bag',
                    'status' => $bag->status, 'domain' => 'bag',
                    'detail' => ($incoming ? 'From ' : 'To ').($incoming ? $bag->fromHub?->business_name : $bag->toHub?->business_name),
                    'quantity' => $incoming ? $bag->outstanding_count : $bag->items_count,
                    'updated_at' => $bag->updated_at,
                    'href' => route($incoming ? 'logistics.inbound.show' : 'logistics.outbound.show', $bag),
                ]);
            }
        }
        if ($queue === 'applications') {
            foreach (CourierProfile::where('logistics_profile_id', $hubId)->where('status', 'pending')->latest()->limit(10)->get() as $rider) {
                $rows->push([
                    'reference' => $rider->full_name, 'kind' => 'Rider application', 'status' => 'pending', 'domain' => 'application',
                    'detail' => $rider->barangay ?: 'Barangay not recorded', 'quantity' => null, 'updated_at' => $rider->created_at,
                    'href' => route('logistics.riders.index', ['status' => 'pending', 'search' => $rider->first_name]),
                ]);
            }
        }

        return $rows->sortByDesc('updated_at')->take(10)->values();
    }

    private function orderLink(Order $order): string
    {
        return match ($order->status) {
            Order::STATUS_READY_FOR_PICKUP => route('logistics.pickups.index'),
            Order::STATUS_PICKED_UP => route('logistics.incoming.index', ['search' => $order->id]),
            Order::STATUS_AT_SORTING_CENTER => route('logistics.sorting.index'),
            Order::STATUS_SORTED => route('logistics.sorting.index', ['tab' => 'to_assign']),
            Order::STATUS_AWAITING_TRANSFER => route('logistics.outbound.index'),
            Order::STATUS_ASSIGNED_TO_RIDER => route('logistics.deliveries.index', ['tab' => 'assigned', 'search' => $order->id]),
            Order::STATUS_OUT_FOR_DELIVERY => route('logistics.deliveries.index', ['tab' => 'out', 'search' => $order->id]),
            Order::STATUS_DELIVERY_FAILED => route('logistics.deliveries.index', ['tab' => 'failed', 'search' => $order->id]),
            Order::STATUS_DELIVERED => route('logistics.deliveries.index', ['tab' => 'delivered', 'search' => $order->id]),
            Order::STATUS_COMPLETED => route('logistics.reports.index', ['type' => 'delivery_performance', 'status' => $order->status]),
            default => route('logistics.reports.index', ['type' => 'failed_returns', 'status' => $order->status]),
        };
    }
}
