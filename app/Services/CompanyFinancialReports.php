<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CompanyFinancialReports
{
    public function __construct(private RiderEarnings $earnings) {}

    public function deliveries(Company $company, ?int $hubId, Carbon $from, Carbon $to): EloquentBuilder
    {
        $owner = 'CASE WHEN earnings_recorded_at IS NOT NULL THEN earning_hub_id ELSE COALESCE(destination_hub_id, logistics_profile_id) END';

        return $this->earnings->deliveries($hubId, null, $from, $to)
            ->whereIn(DB::raw($owner), $company->hubs()->select('id'));
    }

    public function grouped(EloquentBuilder $deliveries, bool $riders = false): Builder
    {
        $source = (clone $deliveries)->selectRaw('CASE WHEN earnings_recorded_at IS NOT NULL THEN earning_hub_id ELSE COALESCE(destination_hub_id, logistics_profile_id) END AS hub_id,
            CASE WHEN earnings_recorded_at IS NOT NULL THEN earning_courier_id ELSE delivery_rider_id END AS rider_id,
            delivered_shipping_fee, rider_pay');
        $query = DB::query()->fromSub($source, 'd')->join((new LogisticsProfile)->getTable().' as h', 'h.id', '=', 'd.hub_id')
            ->select('h.public_code as hub_code', 'h.business_name as hub_name')->groupBy('h.id', 'h.public_code', 'h.business_name')
            ->selectRaw('COUNT(*) AS delivered, COUNT(d.delivered_shipping_fee) AS snapshots, COUNT(d.rider_pay) AS pay_snapshots,
                SUM(d.delivered_shipping_fee) AS shipping_fees, SUM(d.rider_pay) AS rider_pay,
                CASE WHEN COUNT(d.delivered_shipping_fee) = 0 OR COUNT(d.rider_pay) < COUNT(d.delivered_shipping_fee) THEN NULL ELSE SUM(d.delivered_shipping_fee - d.rider_pay) END AS margin');
        if ($riders) {
            $query->leftJoin((new CourierProfile)->getTable().' as r', 'r.id', '=', 'd.rider_id')->groupBy('d.rider_id', 'r.first_name', 'r.last_name')
                ->selectRaw("CASE WHEN d.rider_id IS NULL THEN 'Unassigned' ELSE TRIM(CONCAT_WS(' ', r.first_name, r.last_name)) END AS rider_name");
        }

        return $query;
    }
}
