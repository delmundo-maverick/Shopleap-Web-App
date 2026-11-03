<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LogisticsReportService
{
    public function build(?int $hubId, Carbon $from, Carbon $to, string $type, array $filters = []): array
    {
        return match ($type) {
            'delivery_performance' => $this->deliveryPerformance($hubId, $from, $to, $filters),
            'failed_returns' => $this->failedReturns($hubId, $from, $to, $filters),
            'rider_performance' => $this->riderPerformance($hubId, $from, $to, $filters),
            'cod_remittance' => $this->codRemittance($hubId, $from, $to, $filters),
            'shipping_earnings' => $this->shippingEarnings($hubId, $from, $to, $filters),
            'area_route' => $this->areaRoute($hubId, $from, $to, $filters),
            'hub_throughput' => $this->hubThroughput($hubId, $from, $to, $filters),
            'damaged_lost' => $this->damagedLost($hubId, $from, $to, $filters),
            default => $this->shipmentSummary($hubId, $from, $to, $filters),
        };
    }

    public function types(): array
    {
        return [
            'shipment_summary' => 'Shipment Summary',
            'delivery_performance' => 'Delivery Performance',
            'failed_returns' => 'Failed Delivery / Returns',
            'rider_performance' => 'Rider/Driver Performance',
            'cod_remittance' => 'COD & Remittance',
            'shipping_earnings' => 'Shipping Fees & Earnings',
            'area_route' => 'Area/Route Report',
            'hub_throughput' => 'Hub/Warehouse Throughput',
            'damaged_lost' => 'Damaged/Lost Parcels',
        ];
    }

    private function baseOrders(?int $hubId, Carbon $from, Carbon $to, array $filters): Builder
    {
        $q = Order::whereBetween('created_at', [$from, $to]);
        if ($hubId) {
            $q->where(function ($q) use ($hubId) {
                $q->where('logistics_profile_id', $hubId)
                    ->orWhere('origin_hub_id', $hubId)
                    ->orWhere('destination_hub_id', $hubId)
                    ->orWhere('current_hub_id', $hubId);
            });
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['rider_id'])) {
            $q->where('delivery_rider_id', $filters['rider_id']);
        }
        if (! empty($filters['origin_area'])) {
            $q->where('ship_municipality', $filters['origin_area']);
        }
        if (! empty($filters['destination_area'])) {
            $q->where('delivery_area', $filters['destination_area']);
        }
        if (! empty($filters['seller_id'])) {
            $q->where('seller_id', $filters['seller_id']);
        }

        return $q;
    }

    private function riders(?int $hubId): Collection
    {
        $q = CourierProfile::query();
        if ($hubId) {
            $q->where('logistics_profile_id', $hubId);
        }

        return $q->get()->keyBy('id');
    }

    private function emptyReport(string $title, string $message): array
    {
        return [
            'title' => $title,
            'summary' => [],
            'secondary' => [],
            'columns' => [],
            'rows' => [],
            'chart' => null,
            'hasData' => false,
            'emptyMessage' => $message,
        ];
    }

    private function makeChart(string $type, string $title, string $subtitle, array $labels, array $datasets): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    private function shipmentSummary(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $total = $orders->count();
        if ($total === 0) {
            return $this->emptyReport('Shipment Summary', 'No shipments in this date range.');
        }

        $pending = $orders->whereIn('status', Order::GROUP_TO_SHIP)->count();
        $inTransit = $orders->whereIn('status', Order::GROUP_TO_DELIVER)->count();
        $delivered = $orders->whereIn('status', Order::GROUP_COMPLETED)->count();
        $cancelled = $orders->whereIn('status', Order::GROUP_CANCELLED)->count();
        $returned = $orders->whereIn('status', Order::GROUP_RETURN_REFUND)->count();

        $summary = [
            ['label' => 'Total Shipments', 'value' => number_format($total), 'sub' => 'In selected range', 'accent' => true],
            ['label' => 'Pending', 'value' => number_format($pending), 'sub' => 'Awaiting pickup/sort', 'accent' => false],
            ['label' => 'In Transit', 'value' => number_format($inTransit), 'sub' => 'On the way', 'accent' => false],
            ['label' => 'Delivered', 'value' => number_format($delivered), 'sub' => 'Completed deliveries', 'accent' => false],
        ];

        $secondary = [
            ['label' => 'Cancelled', 'value' => $cancelled],
            ['label' => 'Returned', 'value' => $returned],
            ['label' => 'Delivery Rate', 'value' => $total ? round($delivered / $total * 100, 1).'%' : '0%'],
            ['label' => 'Date Range', 'value' => $from->format('M d').' – '.$to->format('M d')],
        ];

        $statusGroups = [
            'Pending' => $pending,
            'In Transit' => $inTransit,
            'Delivered' => $delivered,
            'Cancelled' => $cancelled,
            'Returned' => $returned,
        ];

        $rows = [];
        foreach ($statusGroups as $label => $count) {
            $rows[] = [
                'status' => $label,
                'count' => $count,
                'share' => $total ? round($count / $total * 100, 1).'%' : '0%',
            ];
        }

        $columns = [
            ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ['key' => 'count', 'label' => 'Shipments', 'align' => 'right'],
            ['key' => 'share', 'label' => 'Share', 'align' => 'right'],
        ];

        $chart = $this->makeChart('doughnut', 'Shipments by Status', 'All shipments in range', array_keys($statusGroups), [
            ['label' => 'Shipments', 'data' => array_values($statusGroups)],
        ]);

        return [
            'title' => 'Shipment Summary',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function damagedLost(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $lostStatuses = [Order::STATUS_CANCELLED, Order::STATUS_RETURNED, Order::STATUS_DELIVERY_FAILED];
        $lostOrders = $orders->whereIn('status', $lostStatuses);

        $missingEvents = ParcelEvent::whereBetween('created_at', [$from, $to])
            ->where('event_type', ParcelEvent::TYPE_BAG_MISSING)
            ->when($hubId, fn ($q) => $q->where('hub_id', $hubId))
            ->get();

        $total = $lostOrders->count() + $missingEvents->count();
        if ($total === 0) {
            return $this->emptyReport('Damaged/Lost Parcels', 'No damaged or lost parcels in this date range.');
        }

        $returned = $orders->where('status', Order::STATUS_RETURNED)->count();
        $cancelled = $orders->where('status', Order::STATUS_CANCELLED)->count();
        $failed = $orders->where('status', Order::STATUS_DELIVERY_FAILED)->count();

        $summary = [
            ['label' => 'Incidents', 'value' => number_format($total), 'sub' => 'Lost/damaged/missing', 'accent' => true],
            ['label' => 'Missing Parcels', 'value' => number_format($missingEvents->count()), 'sub' => 'Bag discrepancies', 'accent' => false],
            ['label' => 'Returned to Sender', 'value' => number_format($returned), 'sub' => 'Returned parcels', 'accent' => false],
            ['label' => 'Cancelled', 'value' => number_format($cancelled), 'sub' => 'Cancelled orders', 'accent' => false],
        ];

        $rows = [];
        foreach ($missingEvents as $ev) {
            $rows[] = [
                'order' => $ev->order_id ? '#'.$ev->order_id : '—',
                'incident' => 'Missing from bag',
                'reason' => $ev->note ?: 'Parcel missing during inbound verification',
                'status' => 'Open',
                'date' => $ev->created_at?->format('M d, Y') ?? '—',
            ];
        }
        foreach ($lostOrders as $o) {
            $label = match ($o->status) {
                Order::STATUS_RETURNED => 'Returned to sender',
                Order::STATUS_CANCELLED => 'Cancelled',
                Order::STATUS_DELIVERY_FAILED => 'Delivery failed',
                default => 'Lost',
            };
            $rows[] = [
                'order' => '#'.$o->id,
                'incident' => $label,
                'reason' => $o->cancel_reason ?? '—',
                'status' => $o->status,
                'date' => $o->updated_at?->format('M d, Y') ?? '—',
            ];
        }

        $columns = [
            ['key' => 'order', 'label' => 'Order #', 'align' => 'left'],
            ['key' => 'incident', 'label' => 'Incident', 'align' => 'left'],
            ['key' => 'reason', 'label' => 'Reason / Note', 'align' => 'left'],
            ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ['key' => 'date', 'label' => 'Date', 'align' => 'left'],
        ];

        $chart = $this->makeChart('bar', 'Incidents by Type', 'Count of damaged/lost parcels', ['Missing', 'Returned', 'Cancelled', 'Failed'], [
            ['label' => 'Count', 'data' => [$missingEvents->count(), $returned, $cancelled, $failed]],
        ]);

        $secondary = [
            ['label' => 'Missing', 'value' => $missingEvents->count()],
            ['label' => 'Returned', 'value' => $returned],
            ['label' => 'Cancelled', 'value' => $cancelled],
            ['label' => 'Failed', 'value' => $failed],
        ];

        return [
            'title' => 'Damaged/Lost Parcels',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function hubThroughput(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        if (! $hubId) {
            return $this->emptyReport('Hub/Warehouse Throughput', 'No hub context available.');
        }

        $sentBags = ShipmentBag::sentBy($hubId)->whereBetween('created_at', [$from, $to])->get();
        $receivedBags = ShipmentBag::addressedTo($hubId)->whereBetween('received_at', [$from, $to])->get();

        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $receivedParcels = $orders->whereIn('status', [Order::STATUS_AT_SORTING_CENTER, Order::STATUS_SORTED, Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count();
        $sorted = $orders->whereIn('status', [Order::STATUS_SORTED, Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count();
        $dispatched = $orders->whereIn('status', [Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count();

        if ($sentBags->count() === 0 && $receivedBags->count() === 0 && $orders->count() === 0) {
            return $this->emptyReport('Hub/Warehouse Throughput', 'No hub activity in this date range.');
        }

        $summary = [
            ['label' => 'Parcels Received', 'value' => number_format($receivedParcels), 'sub' => 'Arrived at hub', 'accent' => true],
            ['label' => 'Sorted', 'value' => number_format($sorted), 'sub' => 'Sorted for delivery', 'accent' => false],
            ['label' => 'Dispatched', 'value' => number_format($dispatched), 'sub' => 'Sent out', 'accent' => false],
            ['label' => 'Bags In Transit', 'value' => number_format($sentBags->whereIn('status', [ShipmentBag::STATUS_SEALED, ShipmentBag::STATUS_IN_TRANSIT])->count()), 'sub' => 'Outbound', 'accent' => false],
        ];

        $rows = [];
        foreach ($sentBags as $bag) {
            $rows[] = [
                'code' => $bag->code,
                'direction' => 'Outbound',
                'to' => $bag->toHub?->business_name ?? '—',
                'status' => $bag->status_label,
                'parcels' => $bag->items()->count(),
                'date' => ($bag->dispatched_at ?? $bag->created_at)->format('M d, Y'),
            ];
        }
        foreach ($receivedBags as $bag) {
            $rows[] = [
                'code' => $bag->code,
                'direction' => 'Inbound',
                'to' => $bag->fromHub?->business_name ?? '—',
                'status' => $bag->status_label,
                'parcels' => $bag->items()->count(),
                'date' => ($bag->received_at ?? $bag->created_at)->format('M d, Y'),
            ];
        }

        $columns = [
            ['key' => 'code', 'label' => 'Bag Code', 'align' => 'left'],
            ['key' => 'direction', 'label' => 'Direction', 'align' => 'left'],
            ['key' => 'to', 'label' => 'Counterpart Hub', 'align' => 'left'],
            ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ['key' => 'parcels', 'label' => 'Parcels', 'align' => 'right'],
            ['key' => 'date', 'label' => 'Date', 'align' => 'left'],
        ];

        $chart = $this->makeChart('bar', 'Throughput', 'Received vs sorted vs dispatched', ['Received', 'Sorted', 'Dispatched'], [
            ['label' => 'Parcels', 'data' => [$receivedParcels, $sorted, $dispatched]],
        ]);

        $secondary = [
            ['label' => 'Sent Bags', 'value' => $sentBags->count()],
            ['label' => 'Received Bags', 'value' => $receivedBags->count()],
            ['label' => 'Parcels Received', 'value' => $receivedParcels],
            ['label' => 'Dispatched', 'value' => $dispatched],
        ];

        return [
            'title' => 'Hub/Warehouse Throughput',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function areaRoute(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        if ($orders->count() === 0) {
            return $this->emptyReport('Area/Route Report', 'No shipments in this date range.');
        }

        $grouped = $orders->groupBy(fn ($o) => $o->ship_municipality ?: ($o->delivery_area ?: 'Unknown'));

        $rows = [];
        foreach ($grouped as $area => $items) {
            $total = $items->count();
            $delivered = $items->whereIn('status', Order::GROUP_COMPLETED)->count();
            $onTime = $items->whereIn('status', Order::GROUP_COMPLETED)
                ->filter(fn ($o) => (int) ($o->delivery_attempts ?? 1) <= 1)->count();
            $onTimeRate = $delivered ? round($onTime / $delivered * 100, 1) : 0;
            $avgDays = $delivered ? round($items->whereIn('status', Order::GROUP_COMPLETED)->avg(fn ($o) => $o->delivered_at && $o->created_at
                ? $o->created_at->diffInDays($o->delivered_at) : 0), 2) : 0;

            $rows[] = [
                'area' => $area,
                'volume' => $total,
                'delivered' => $delivered,
                'on_time_rate' => $onTimeRate.'%',
                'avg_days' => $avgDays.' days',
            ];
        }
        usort($rows, fn ($a, $b) => $b['volume'] <=> $a['volume']);

        $totalVolume = $orders->count();
        $totalDelivered = $orders->whereIn('status', Order::GROUP_COMPLETED)->count();

        $summary = [
            ['label' => 'Areas Served', 'value' => number_format(count($rows)), 'sub' => 'Distinct cities/zones', 'accent' => true],
            ['label' => 'Total Volume', 'value' => number_format($totalVolume), 'sub' => 'Shipments', 'accent' => false],
            ['label' => 'Delivered', 'value' => number_format($totalDelivered), 'sub' => 'Completed', 'accent' => false],
            ['label' => 'Delivery Rate', 'value' => $totalVolume ? round($totalDelivered / $totalVolume * 100, 1).'%' : '0%', 'sub' => 'Overall', 'accent' => false],
        ];

        $columns = [
            ['key' => 'area', 'label' => 'City / Zone', 'align' => 'left'],
            ['key' => 'volume', 'label' => 'Volume', 'align' => 'right'],
            ['key' => 'delivered', 'label' => 'Delivered', 'align' => 'right'],
            ['key' => 'on_time_rate', 'label' => 'On-Time Rate', 'align' => 'right'],
            ['key' => 'avg_days', 'label' => 'Avg Delivery', 'align' => 'right'],
        ];

        $chart = $this->makeChart('bar', 'Volume by Area', 'Shipments per city/zone', array_column($rows, 'area'), [
            ['label' => 'Volume', 'data' => array_column($rows, 'volume')],
        ]);

        $secondary = [
            ['label' => 'Areas', 'value' => count($rows)],
            ['label' => 'Volume', 'value' => $totalVolume],
            ['label' => 'Delivered', 'value' => $totalDelivered],
            ['label' => 'Delivery Rate', 'value' => $totalVolume ? round($totalDelivered / $totalVolume * 100, 1).'%' : '0%'],
        ];

        return [
            'title' => 'Area/Route Report',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function shippingEarnings(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $delivered = $orders->whereIn('status', Order::GROUP_SHIPPING_EARNED);

        $fees = (float) $delivered->sum('shipping_fee');
        $reattempts = (int) $delivered->sum(fn ($o) => max(0, (int) ($o->delivery_attempts ?? 1) - 1));
        $surcharge = $reattempts * 50.0;
        $total = $fees + $surcharge;

        if ($delivered->count() === 0) {
            return $this->emptyReport('Shipping Fees & Earnings', 'No delivered parcels in this date range.');
        }

        $summary = [
            ['label' => 'Delivery Fees', 'value' => '₱'.number_format($fees, 2), 'sub' => $delivered->count().' parcels', 'accent' => true],
            ['label' => 'Surcharges', 'value' => '₱'.number_format($surcharge, 2), 'sub' => $reattempts.' reattempts', 'accent' => false],
            ['label' => 'Total Earnings', 'value' => '₱'.number_format($total, 2), 'sub' => 'Fees + surcharges', 'accent' => false],
            ['label' => 'Avg per Parcel', 'value' => '₱'.number_format($delivered->count() ? $total / $delivered->count() : 0, 2), 'sub' => 'Average', 'accent' => false],
        ];

        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $buckets[$cursor->format('Y-m-d')] = ['label' => $cursor->format('M d'), 'fees' => 0.0, 'surcharges' => 0.0];
            $cursor->addDay();
        }
        foreach ($delivered as $o) {
            $key = ($o->delivered_at ?? $o->created_at)->format('Y-m-d');
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['fees'] += (float) ($o->shipping_fee ?? 0);
            $attempts = (int) ($o->delivery_attempts ?? 1);
            if ($attempts > 1) {
                $buckets[$key]['surcharges'] += ($attempts - 1) * 50.0;
            }
        }

        $rows = [];
        foreach ($buckets as $b) {
            $rows[] = [
                'period' => $b['label'],
                'fees' => '₱'.number_format($b['fees'], 2),
                'surcharges' => '₱'.number_format($b['surcharges'], 2),
                'total' => '₱'.number_format($b['fees'] + $b['surcharges'], 2),
            ];
        }

        $columns = [
            ['key' => 'period', 'label' => 'Period', 'align' => 'left'],
            ['key' => 'fees', 'label' => 'Delivery Fees', 'align' => 'right'],
            ['key' => 'surcharges', 'label' => 'Surcharges', 'align' => 'right'],
            ['key' => 'total', 'label' => 'Total Earnings', 'align' => 'right'],
        ];

        $chart = $this->makeChart('bar', 'Earnings Over Time', 'Daily delivery fees', array_values(array_map(fn ($b) => $b['label'], $buckets)), [
            ['label' => 'Delivery Fees', 'data' => array_values(array_map(fn ($b) => round($b['fees'], 2), $buckets))],
            ['label' => 'Surcharges', 'data' => array_values(array_map(fn ($b) => round($b['surcharges'], 2), $buckets))],
        ]);

        $secondary = [
            ['label' => 'Delivery Fees', 'value' => '₱'.number_format($fees, 2)],
            ['label' => 'Surcharges', 'value' => '₱'.number_format($surcharge, 2)],
            ['label' => 'Total Earnings', 'value' => '₱'.number_format($total, 2)],
            ['label' => 'Parcels', 'value' => $delivered->count()],
        ];

        return [
            'title' => 'Shipping Fees & Earnings',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function codRemittance(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $collectedOrders = $orders->whereNotNull('cod_collected_at');
        $collected = (float) $collectedOrders->sum('cod_collected_amount');
        $pending = (float) $orders->where('status', Order::STATUS_DELIVERED)->sum('cod_collected_amount');
        $remitted = (float) $orders->whereIn('status', Order::GROUP_COMPLETED)->sum('cod_collected_amount');

        if ($collected == 0 && $pending == 0 && $remitted == 0) {
            return $this->emptyReport('COD & Remittance', 'No COD activity in this date range.');
        }

        $summary = [
            ['label' => 'COD Collected', 'value' => '₱'.number_format($collected, 2), 'sub' => $collectedOrders->count().' parcels', 'accent' => true],
            ['label' => 'Pending Remittance', 'value' => '₱'.number_format($pending, 2), 'sub' => 'Delivered, not completed', 'accent' => false],
            ['label' => 'Remitted', 'value' => '₱'.number_format($remitted, 2), 'sub' => 'Completed orders', 'accent' => false],
            ['label' => 'Parcels', 'value' => number_format($collectedOrders->count()), 'sub' => 'With COD collected', 'accent' => false],
        ];

        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $buckets[$cursor->format('Y-m-d')] = ['label' => $cursor->format('M d'), 'collected' => 0.0, 'pending' => 0.0, 'remitted' => 0.0];
            $cursor->addDay();
        }
        foreach ($orders as $o) {
            if (! $o->cod_collected_at) {
                continue;
            }
            $key = $o->cod_collected_at->format('Y-m-d');
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['collected'] += (float) ($o->cod_collected_amount ?? 0);
            if ($o->status === Order::STATUS_DELIVERED) {
                $buckets[$key]['pending'] += (float) ($o->cod_collected_amount ?? 0);
            }
            if (in_array($o->status, Order::GROUP_COMPLETED, true)) {
                $buckets[$key]['remitted'] += (float) ($o->cod_collected_amount ?? 0);
            }
        }

        $rows = [];
        foreach ($buckets as $b) {
            $rows[] = [
                'period' => $b['label'],
                'collected' => '₱'.number_format($b['collected'], 2),
                'pending' => '₱'.number_format($b['pending'], 2),
                'remitted' => '₱'.number_format($b['remitted'], 2),
            ];
        }

        $columns = [
            ['key' => 'period', 'label' => 'Period', 'align' => 'left'],
            ['key' => 'collected', 'label' => 'COD Collected', 'align' => 'right'],
            ['key' => 'pending', 'label' => 'Pending Remittance', 'align' => 'right'],
            ['key' => 'remitted', 'label' => 'Remitted', 'align' => 'right'],
        ];

        $chart = $this->makeChart('bar', 'COD Over Time', 'Daily collected vs remitted', array_values(array_map(fn ($b) => $b['label'], $buckets)), [
            ['label' => 'Collected', 'data' => array_values(array_map(fn ($b) => round($b['collected'], 2), $buckets))],
            ['label' => 'Remitted', 'data' => array_values(array_map(fn ($b) => round($b['remitted'], 2), $buckets))],
        ]);

        $secondary = [
            ['label' => 'Collected', 'value' => '₱'.number_format($collected, 2)],
            ['label' => 'Pending', 'value' => '₱'.number_format($pending, 2)],
            ['label' => 'Remitted', 'value' => '₱'.number_format($remitted, 2)],
            ['label' => 'Parcels', 'value' => $collectedOrders->count()],
        ];

        return [
            'title' => 'COD & Remittance',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function riderPerformance(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $riders = $this->riders($hubId);

        $rows = [];
        foreach ($riders as $rider) {
            $riderOrders = $orders->where('delivery_rider_id', $rider->id);
            $total = $riderOrders->count();
            if ($total === 0) {
                continue;
            }
            $success = $riderOrders->whereIn('status', Order::GROUP_COMPLETED)->count();
            $failed = $riderOrders->where('status', Order::STATUS_DELIVERY_FAILED)->count();
            $successRate = $total ? round($success / $total * 100, 1) : 0;
            $delivered = $riderOrders->whereIn('status', Order::GROUP_COMPLETED);
            $avgHours = $delivered->count() ? round($delivered->avg(fn ($o) => $o->delivered_at && $o->created_at
                ? $o->created_at->diffInHours($o->delivered_at) : 0), 1) : 0;

            $rows[] = [
                'rider' => $rider->full_name,
                'deliveries' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => $successRate.'%',
                'avg_time' => round($avgHours / 24, 2).' days',
            ];
        }

        usort($rows, fn ($a, $b) => $b['deliveries'] <=> $a['deliveries']);

        if (empty($rows)) {
            return $this->emptyReport('Rider/Driver Performance', 'No rider activity in this date range.');
        }

        $totalDeliveries = array_sum(array_column($rows, 'deliveries'));
        $totalSuccess = array_sum(array_column($rows, 'success'));
        $overallRate = $totalDeliveries ? round($totalSuccess / $totalDeliveries * 100, 1) : 0;

        $summary = [
            ['label' => 'Active Riders', 'value' => number_format(count($rows)), 'sub' => 'With deliveries', 'accent' => true],
            ['label' => 'Total Deliveries', 'value' => number_format($totalDeliveries), 'sub' => 'Assigned parcels', 'accent' => false],
            ['label' => 'Successful', 'value' => number_format($totalSuccess), 'sub' => 'Delivered', 'accent' => false],
            ['label' => 'Success Rate', 'value' => $overallRate.'%', 'sub' => 'Overall', 'accent' => false],
        ];

        $columns = [
            ['key' => 'rider', 'label' => 'Rider', 'align' => 'left'],
            ['key' => 'deliveries', 'label' => 'Deliveries', 'align' => 'right'],
            ['key' => 'success', 'label' => 'Success', 'align' => 'right'],
            ['key' => 'failed', 'label' => 'Failed', 'align' => 'right'],
            ['key' => 'success_rate', 'label' => 'Success Rate', 'align' => 'right'],
            ['key' => 'avg_time', 'label' => 'Avg Time', 'align' => 'right'],
        ];

        $chart = $this->makeChart('bar', 'Deliveries per Rider', 'Total assigned parcels', array_column($rows, 'rider'), [
            ['label' => 'Deliveries', 'data' => array_column($rows, 'deliveries')],
            ['label' => 'Successful', 'data' => array_column($rows, 'success')],
        ]);

        $secondary = [
            ['label' => 'Riders', 'value' => count($rows)],
            ['label' => 'Deliveries', 'value' => $totalDeliveries],
            ['label' => 'Success', 'value' => $totalSuccess],
            ['label' => 'Success Rate', 'value' => $overallRate.'%'],
        ];

        return [
            'title' => 'Rider/Driver Performance',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function failedReturns(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $failed = $orders->where('status', Order::STATUS_DELIVERY_FAILED);
        $returned = $orders->where('status', Order::STATUS_RETURNED);
        $total = $failed->count() + $returned->count();
        if ($total === 0) {
            return $this->emptyReport('Failed Delivery / Returns', 'No failed or returned parcels in this date range.');
        }

        $allOrders = $orders->count();
        $failureRate = $allOrders ? round($total / $allOrders * 100, 1) : 0;

        $summary = [
            ['label' => 'Failed Attempts', 'value' => number_format($failed->count()), 'sub' => 'Delivery failed', 'accent' => true],
            ['label' => 'Returned to Sender', 'value' => number_format($returned->count()), 'sub' => 'Returned parcels', 'accent' => false],
            ['label' => 'Total Incidents', 'value' => number_format($total), 'sub' => 'Failed + returned', 'accent' => false],
            ['label' => 'Failure Rate', 'value' => $failureRate.'%', 'sub' => 'Of all shipments', 'accent' => false],
        ];

        $rows = [];
        foreach ($failed as $o) {
            $rows[] = [
                'order' => '#'.$o->id,
                'type' => 'Delivery Failed',
                'reason' => $o->cancel_reason ?? 'Delivery attempt unsuccessful',
                'attempts' => (int) ($o->delivery_attempts ?? 0),
                'date' => $o->updated_at?->format('M d, Y') ?? '—',
            ];
        }
        foreach ($returned as $o) {
            $rows[] = [
                'order' => '#'.$o->id,
                'type' => 'Returned',
                'reason' => $o->cancel_reason ?? 'Returned to sender',
                'attempts' => (int) ($o->delivery_attempts ?? 0),
                'date' => $o->updated_at?->format('M d, Y') ?? '—',
            ];
        }

        $columns = [
            ['key' => 'order', 'label' => 'Order #', 'align' => 'left'],
            ['key' => 'type', 'label' => 'Type', 'align' => 'left'],
            ['key' => 'reason', 'label' => 'Reason', 'align' => 'left'],
            ['key' => 'attempts', 'label' => 'Attempts', 'align' => 'right'],
            ['key' => 'date', 'label' => 'Date', 'align' => 'left'],
        ];

        $reasons = collect($rows)->groupBy('reason')->map->count();
        $chart = $this->makeChart('bar', 'Failures by Reason', 'Count of failed/returned parcels', $reasons->keys()->values()->all(), [
            ['label' => 'Incidents', 'data' => $reasons->values()->all()],
        ]);

        $secondary = [
            ['label' => 'Failed', 'value' => $failed->count()],
            ['label' => 'Returned', 'value' => $returned->count()],
            ['label' => 'Total Shipments', 'value' => $allOrders],
            ['label' => 'Failure Rate', 'value' => $failureRate.'%'],
        ];

        return [
            'title' => 'Failed Delivery / Returns',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }

    private function deliveryPerformance(?int $hubId, Carbon $from, Carbon $to, array $filters): array
    {
        $orders = $this->baseOrders($hubId, $from, $to, $filters)->get();
        $delivered = $orders->whereIn('status', Order::GROUP_COMPLETED);
        $total = $delivered->count();
        if ($total === 0) {
            return $this->emptyReport('Delivery Performance', 'No delivered parcels in this date range.');
        }

        $onTime = $delivered->filter(fn ($o) => (int) ($o->delivery_attempts ?? 1) <= 1)->count();
        $late = $total - $onTime;
        $onTimeRate = round($onTime / $total * 100, 1);
        $avgHours = round($delivered->avg(fn ($o) => $o->delivered_at && $o->created_at
            ? $o->created_at->diffInHours($o->delivered_at) : 0), 1);
        $avgDays = round($avgHours / 24, 2);

        $summary = [
            ['label' => 'Delivered', 'value' => number_format($total), 'sub' => 'Completed deliveries', 'accent' => true],
            ['label' => 'On-Time Rate', 'value' => $onTimeRate.'%', 'sub' => $onTime.' on time', 'accent' => false],
            ['label' => 'Late Deliveries', 'value' => number_format($late), 'sub' => 'More than 1 attempt', 'accent' => false],
            ['label' => 'Avg. Delivery Time', 'value' => $avgDays.' days', 'sub' => $avgHours.' hours', 'accent' => false],
        ];

        $riders = $this->riders($hubId);
        $rows = $delivered->map(function ($o) use ($riders) {
            $hours = $o->delivered_at && $o->created_at ? $o->created_at->diffInHours($o->delivered_at) : null;
            $riderName = isset($riders[$o->delivery_rider_id]) ? $riders[$o->delivery_rider_id]->full_name : '—';

            return [
                'order' => '#'.$o->id,
                'delivered_at' => $o->delivered_at?->format('M d, Y g:i A') ?? '—',
                'rider' => $riderName,
                'attempts' => (int) ($o->delivery_attempts ?? 1),
                'time' => $hours !== null ? round($hours / 24, 2).' days' : '—',
            ];
        })->values()->all();

        $columns = [
            ['key' => 'order', 'label' => 'Order #', 'align' => 'left'],
            ['key' => 'delivered_at', 'label' => 'Delivered At', 'align' => 'left'],
            ['key' => 'rider', 'label' => 'Rider', 'align' => 'left'],
            ['key' => 'attempts', 'label' => 'Attempts', 'align' => 'right'],
            ['key' => 'time', 'label' => 'Delivery Time', 'align' => 'right'],
        ];

        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $buckets[$cursor->format('Y-m-d')] = ['label' => $cursor->format('M d'), 'count' => 0];
            $cursor->addDay();
        }
        foreach ($delivered as $o) {
            $key = $o->delivered_at?->format('Y-m-d');
            if ($key && isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }
        $labels = array_column($buckets, 'label');
        $data = array_column($buckets, 'count');

        $chart = $this->makeChart('line', 'Deliveries Over Time', 'Daily delivered parcels', $labels, [
            ['label' => 'Delivered', 'data' => $data, 'type' => 'line'],
        ]);

        $secondary = [
            ['label' => 'On-Time', 'value' => $onTime],
            ['label' => 'Late', 'value' => $late],
            ['label' => 'Total Delivered', 'value' => $total],
            ['label' => 'Avg Hours', 'value' => $avgHours],
        ];

        return [
            'title' => 'Delivery Performance',
            'summary' => $summary,
            'secondary' => $secondary,
            'columns' => $columns,
            'rows' => $rows,
            'chart' => $chart,
            'hasData' => true,
            'emptyMessage' => '',
        ];
    }
}
