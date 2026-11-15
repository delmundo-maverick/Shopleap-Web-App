<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Services\LedgerService;
use App\Services\MoneyMovementQueries;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashController extends Controller
{
    public function __construct(private MoneyMovementQueries $queries) {}

    public function summary(Request $request): JsonResponse
    {
        $riderId = $request->attributes->get('courier')->id;
        $orders = $this->queries->cashOrders(null, $riderId)->get();

        return response()->json([
            'cash_on_hand' => LedgerService::decimal($orders->sum(fn (object $order): int => LedgerService::cents((string) $order->amount))),
            'shortage_amount' => $this->queries->shortageAmount(null, $riderId),
            'currency' => 'PHP',
            'orders' => $orders->map(fn (object $order): array => [
                'order_number' => $order->order_number, 'amount' => LedgerService::decimal(LedgerService::cents((string) $order->amount)),
                'delivered_at' => CarbonImmutable::parse($order->delivered_at)->toIso8601String(),
            ]),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $handovers = $this->queries->handovers(null, $request->attributes->get('courier')->id)
            ->orderByDesc('p.confirmed_at')->orderByDesc('p.id')->paginate(20)->withQueryString();

        return response()->json([
            'currency' => 'PHP',
            'data' => $handovers->getCollection()->map(fn (object $handover): array => [
                'reference_no' => $handover->reference_no,
                'amount' => LedgerService::decimal(LedgerService::cents((string) $handover->amount_received) + LedgerService::cents((string) $handover->settled_amount)),
                'currency' => 'PHP', 'status' => $handover->status,
                'confirmed_at' => CarbonImmutable::parse($handover->confirmed_at)->toIso8601String(),
            ]),
            'meta' => ['current_page' => $handovers->currentPage(), 'last_page' => $handovers->lastPage(), 'total' => $handovers->total()],
        ]);
    }
}
