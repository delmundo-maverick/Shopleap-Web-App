<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayoutQueries
{
    public function __construct(private MoneyMovementQueries $movements) {}

    public function riderOrders(?int $hubId, ?int $riderId = null): Builder
    {
        return $this->movements->deliveries($hubId, $riderId)
            ->whereNotNull('o.rider_pay')->where('o.rider_pay', '>', 0)
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('rider_payout_items as i')->whereColumn('i.order_id', 'o.id'))
            ->select('o.id', 'o.order_number', 'o.delivered_at', 'o.earning_courier_id', 'o.rider_pay as amount')
            ->orderBy('o.delivered_at')->orderBy('o.id');
    }

    public function paidRiderOrders(int $riderId): Builder
    {
        return $this->movements->deliveries(null, $riderId)
            ->join('rider_payout_items as i', 'i.order_id', '=', 'o.id')
            ->join('rider_payouts as p', 'p.id', '=', 'i.rider_payout_id')
            ->where('p.rider_id', $riderId)->where('p.status', 'paid');
    }

    /** @return array{accrued_unpaid: string, paid: string} */
    public function riderTotals(int $riderId, ?Carbon $from, Carbon $to): array
    {
        $totals = [];
        foreach (['accrued_unpaid' => $this->riderOrders(null, $riderId), 'paid' => $this->paidRiderOrders($riderId)] as $key => $query) {
            $column = $key === 'paid' ? 'i.amount' : 'o.rider_pay';
            $amount = $query->when($from !== null, fn (Builder $query) => $query->where('o.delivered_at', '>=', $from))
                ->where('o.delivered_at', '<=', $to)->reorder()->select([])
                ->selectRaw('ROUND(COALESCE(SUM('.$column.'), 0), 2) as total')->value('total');
            $totals[$key] = LedgerService::decimal(LedgerService::cents((string) $amount));
        }

        return $totals;
    }

    /**
     * @param  array<int, int>  $orderIds
     * @return Collection<int, int>
     */
    public function paidRiderOrderIds(int $riderId, array $orderIds): Collection
    {
        return $this->paidRiderOrders($riderId)->whereIn('o.id', $orderIds)->pluck('o.id');
    }

    public function refunds(): Builder
    {
        return DB::table('refund_payouts as p')->join('order_returns as r', 'r.id', '=', 'p.order_return_id')
            ->join('orders as o', 'o.id', '=', 'p.order_id')->join('users as u', 'u.id', '=', 'p.buyer_id')
            ->whereIn('r.status', ['refund_pending', 'refunded'])
            ->select('p.*', 'o.order_number', 'u.name as party_name', 'r.refund_method as method_type', 'r.refund_account_name as account_name', 'r.refund_account_number as account_number_masked');
    }
}
