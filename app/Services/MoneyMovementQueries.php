<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MoneyMovementQueries
{
    public function handovers(?int $hubId = null, ?int $riderId = null): Builder
    {
        return DB::table('rider_cash_handovers as p')
            ->when($hubId !== null, fn (Builder $query) => $query->where('p.hub_id', $hubId))
            ->when($riderId !== null, fn (Builder $query) => $query->where('p.rider_id', $riderId));
    }

    public function shortageAmount(?int $hubId = null, ?int $riderId = null): string
    {
        $amount = $this->handovers($hubId, $riderId)->where('p.status', 'short')
            ->selectRaw('ROUND(COALESCE(SUM(p.short_amount - p.settled_amount), 0), 2) as amount')->value('amount');

        return LedgerService::decimal(LedgerService::cents((string) $amount));
    }

    public function deliveries(?int $hubId = null, ?int $riderId = null): Builder
    {
        return DB::table('orders as o')->whereNotNull('o.earnings_recorded_at')->whereNotNull('o.delivered_at')
            ->when($hubId !== null, fn (Builder $q) => $q->where('o.earning_hub_id', $hubId))
            ->when($riderId !== null, fn (Builder $q) => $q->where('o.earning_courier_id', $riderId))
            ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('ledger_transactions as t')->whereColumn('t.order_id', 'o.id')->where('t.type', 'delivery')
                ->whereNotExists(fn (Builder $r) => $r->selectRaw('1')->from('ledger_transactions as reversal')->whereColumn('reversal.reversal_of', 't.id')));
    }

    public function cashOrders(?int $hubId = null, ?int $riderId = null): Builder
    {
        $allocated = DB::table('rider_cash_handover_items')->select('order_id')->selectRaw('SUM(amount) as amount')->groupBy('order_id');

        return $this->deliveries($hubId, $riderId)->leftJoinSub($allocated, 'allocated', 'allocated.order_id', '=', 'o.id')
            ->select('o.id', 'o.order_number', 'o.delivered_at', 'o.earning_hub_id', 'o.earning_courier_id')
            ->selectRaw('ROUND(o.cod_collected_amount - COALESCE(allocated.amount, 0), 2) as amount')
            ->whereRaw('ROUND(o.cod_collected_amount - COALESCE(allocated.amount, 0), 2) > 0')->orderBy('o.delivered_at')->orderBy('o.id');
    }

    public function remittable(?int $hubId = null): Builder
    {
        return $this->deliveries($hubId)->where('o.total_amount', '>', 0)
            ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('remittance_items as ri')->whereColumn('ri.active_order_id', 'o.id'))
            ->select('o.id', 'o.order_number', 'o.delivered_at', 'o.earning_hub_id', 'o.total_amount as amount')->orderBy('o.delivered_at')->orderBy('o.id');
    }

    public function unremittedHubs(): Collection
    {
        return DB::table('wallet_accounts as a')->join('ledger_entries as e', 'e.account_id', '=', 'a.id')
            ->join('logistics_profiles as h', 'h.id', '=', 'a.owner_id')->where('a.owner_type', 'hub')->where('a.account_type', 'cod_to_remit')
            ->select('h.id', 'h.business_name')->selectRaw("ROUND(SUM(CASE WHEN e.direction = 'credit' THEN e.amount ELSE -e.amount END), 2) as amount")
            ->groupBy('h.id', 'h.business_name')->havingRaw("SUM(CASE WHEN e.direction = 'credit' THEN e.amount ELSE -e.amount END) <> 0")->orderBy('h.business_name')->get();
    }
}
