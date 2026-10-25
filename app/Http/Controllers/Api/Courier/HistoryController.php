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

        $orders = Order::with(['items', 'buyer.buyerProfile'])
            ->where('delivery_rider_id', $courier->id)
            ->whereIn('status', $statuses)
            ->when($data['from'] ?? null, fn($q, $d) => $q->whereRaw("$when >= ?", [Carbon::parse($d)->startOfDay()]))
            ->when($data['to'] ?? null, fn($q, $d) => $q->whereRaw("$when <= ?", [Carbon::parse($d)->endOfDay()]))
            ->orderByRaw("$when DESC")
            ->paginate(20);

        $fee = RiderEarnings::feePerDelivery();

        return response()->json([
            'data' => $orders->getCollection()->map(function (Order $o) use ($fee) {
                $ship = $o->ship_to;
                $earned = in_array($o->status, RiderEarnings::EARNED_STATUSES, true);

                return [
                    'order_id' => $o->id,
                    'status' => $o->status,
                    'outcome' => $earned ? 'delivered' : 'returned',
                    'buyer_name' => $ship['name'],
                    'barangay' => $ship['barangay'],
                    'municipality' => $ship['municipality'],
                    'items_count' => $o->items->sum('quantity'),
                    'cod_collected' => $o->cod_collected_amount !== null ? (float) $o->cod_collected_amount : null,
                    'earning' => $earned ? $fee : 0,
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
