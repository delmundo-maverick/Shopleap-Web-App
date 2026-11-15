<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RiderEarnings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistoryController extends Controller
{
    private const GROUPS = [
        'delivered' => [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED],
        'returned' => [Order::STATUS_RETURNED],
    ];

    /** Finished deliveries, newest first, with optional date and outcome filters. */
    public function index(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(array_keys(self::GROUPS))],
        ]);

        $statuses = isset($data['status'])
            ? self::GROUPS[$data['status']]
            : array_merge(...array_values(self::GROUPS));

        $when = 'COALESCE(delivered_at, updated_at)';

        $orders = Order::with(['items', 'buyer.buyerProfile', 'deliveryProof'])
            ->whereRaw('COALESCE(earning_courier_id, delivery_rider_id) = ?', [$courier->id])
            ->whereIn('status', $statuses)
            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereRaw("$when >= ?", [Carbon::parse($d)->startOfDay()]))
            ->when($data['to'] ?? null, fn ($q, $d) => $q->whereRaw("$when <= ?", [Carbon::parse($d)->endOfDay()]))
            ->orderByRaw("$when DESC")
            ->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(function (Order $o) {
                $ship = $o->ship_to;
                $earned = in_array($o->status, RiderEarnings::EARNED_STATUSES, true);

                return [
                    'order_id' => $o->order_number,
                    'order_number' => $o->order_number,
                    'waybill_code' => $o->waybill_code,
                    'status' => $o->status,
                    'outcome' => $earned ? 'delivered' : 'returned',
                    'proof' => $o->deliveryProof?->riderSummary() ?? ['uploaded' => false, 'captured_at' => null],
                    'buyer_name' => $ship['name'],
                    'barangay' => $ship['barangay'],
                    'municipality' => $ship['municipality'],
                    'items_count' => $o->items->sum('quantity'),
                    'cod_collected' => $o->cod_collected_amount !== null ? $o->cod_collected_amount : null,
                    'earning' => $earned ? $o->rider_pay : '0.00',
                    'currency' => 'PHP',
                    'finished_at' => ($o->delivered_at ?? $o->updated_at)?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
