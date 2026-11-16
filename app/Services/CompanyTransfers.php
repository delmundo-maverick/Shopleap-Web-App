<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LogisticsProfile;
use App\Models\ShipmentBag;
use App\Models\ShipmentBagItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CompanyTransfers
{
    public function bags(Company $company): Builder
    {
        return ShipmentBag::whereIn('from_hub_id', $company->hubs()->select('id'))
            ->whereIn('to_hub_id', $company->hubs()->select('id'));
    }

    public function late(Company $company): Builder
    {
        return $this->bags($company)->where('status', ShipmentBag::STATUS_IN_TRANSIT)
            ->where('dispatched_at', '<', now()->subHours(48));
    }

    public function missing(Company $company): Builder
    {
        return $this->bags($company)->where('status', ShipmentBag::STATUS_DISCREPANCY)
            ->whereHas('items', fn (Builder $items) => $items->whereNull('received_at'));
    }

    public function transitParcels(Company $company): int
    {
        return ShipmentBagItem::whereIn('bag_id', $this->bags($company)->where('status', ShipmentBag::STATUS_IN_TRANSIT)->select('id'))
            ->whereNull('received_at')->distinct()->count('order_id');
    }

    public function missingParcels(Company $company): int
    {
        return ShipmentBagItem::whereIn('bag_id', $this->missing($company)->select('id'))->whereNull('received_at')->distinct()->count('order_id');
    }

    /** @param array<string, mixed> $filters */
    public function filtered(Company $company, array $filters): Builder
    {
        $query = $this->bags($company);
        foreach (['origin' => 'from_hub_id', 'destination' => 'to_hub_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $hub = LogisticsProfile::where('public_code', $filters[$key])->firstOrFail();
                abort_unless((int) $hub->company_id === (int) $company->id, 403);
                $query->where($column, $hub->id);
            }
        }
        $query->when($filters['status'] ?? null, fn (Builder $bags, string $status) => $bags->where('status', $status));
        if (! empty($filters['date_from'])) {
            $from = Carbon::parse($filters['date_from'], 'Asia/Manila')->startOfDay()->setTimezone(config('app.timezone'));
            $query->whereRaw('COALESCE(dispatched_at, created_at) >= ?', [$from]);
        }
        if (! empty($filters['date_to'])) {
            $to = Carbon::parse($filters['date_to'], 'Asia/Manila')->endOfDay()->setTimezone(config('app.timezone'));
            $query->whereRaw('COALESCE(dispatched_at, created_at) <= ?', [$to]);
        }
        if (! empty($filters['late'])) {
            $query->where('status', ShipmentBag::STATUS_IN_TRANSIT)->where('dispatched_at', '<', now()->subHours(48));
        }
        if (! empty($filters['missing'])) {
            $query->where('status', ShipmentBag::STATUS_DISCREPANCY)->whereHas('items', fn (Builder $items) => $items->whereNull('received_at'));
        }

        return $query;
    }
}
