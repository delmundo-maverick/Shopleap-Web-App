<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\ShipmentBagItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyDashboard
{
    public function __construct(private CompanyCoverage $coverage, private CompanyTransfers $transfers) {}

    /** @return array{range: string, periodLabel: string, cards: array, chartData: array, leaderboard: array, attention: array, hasHubs: bool, handled: int, hasAttempts: bool, staleHours: int} */
    public function build(Company $company, string $range): array
    {
        $days = match ($range) {
            'today' => 0, '30d' => 29, default => 6
        };
        $now = now();
        $localToday = $now->copy()->setTimezone('Asia/Manila')->startOfDay();
        $localFrom = $localToday->copy()->subDays($days);
        $from = $localFrom->copy()->setTimezone(config('app.timezone'));
        $periodLabel = match ($range) {
            'today' => 'Today', '30d' => 'Last 30 days', default => 'Last 7 days'
        };
        $hubs = $company->hubs()->select(['id', 'public_code', 'business_name', 'user_id', 'status'])->with('user:id,account_status')->orderBy('business_name')->get();
        $riders = CourierProfile::whereIn('logistics_profile_id', $company->hubs()->select('id'))->count();
        $events = ParcelEvent::whereIn('hub_id', $company->hubs()->select('id'))->where('event_type', '!=', ParcelEvent::TYPE_STALE)->whereBetween('created_at', [$from, $now]);
        $handled = (clone $events)->distinct()->count('order_id');
        $hubHandled = (clone $events)->select('hub_id')->selectRaw('COUNT(DISTINCT order_id) AS total')->groupBy('hub_id')->pluck('total', 'hub_id');
        $dayExpression = 'DATE((created_at AT TIME ZONE ?) AT TIME ZONE ?)';
        $timezones = [config('app.timezone'), 'Asia/Manila'];
        $daily = (clone $events)->selectRaw($dayExpression.' AS day, COUNT(DISTINCT order_id) AS total', $timezones)
            ->groupByRaw('1')->pluck('total', 'day');

        $deliveryHub = 'CASE WHEN earnings_recorded_at IS NOT NULL THEN earning_hub_id ELSE COALESCE(destination_hub_id, logistics_profile_id) END';
        $deliveries = Order::whereBetween('delivered_at', [$from, $now])->where(function (Builder $query) use ($company): void {
            $query->where(fn (Builder $frozen) => $frozen->whereNotNull('earnings_recorded_at')->whereIn('earning_hub_id', $company->hubs()->select('id')))
                ->orWhere(function (Builder $legacy) use ($company): void {
                    $legacy->whereNull('earnings_recorded_at')->where(function (Builder $owner) use ($company): void {
                        $owner->whereIn('destination_hub_id', $company->hubs()->select('id'))
                            ->orWhere(fn (Builder $fallback) => $fallback->whereNull('destination_hub_id')->whereIn('logistics_profile_id', $company->hubs()->select('id')));
                    });
                });
        })->selectRaw($deliveryHub.' AS hub_id, id AS order_id');
        $deliveredByHub = DB::query()->fromSub(clone $deliveries, 'delivered')->select('hub_id')->selectRaw('COUNT(DISTINCT order_id) AS total')->groupBy('hub_id')->pluck('total', 'hub_id');
        $attempts = (clone $events)->where(function (Builder $query): void {
            $query->whereIn('event_type', [ParcelEvent::TYPE_OUT_FOR_DELIVERY, ParcelEvent::TYPE_DELIVERY_ATTEMPT, ParcelEvent::TYPE_DELIVERED])
                ->orWhereIn('to_status', [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERY_FAILED, Order::STATUS_DELIVERED]);
        })->select(['hub_id', 'order_id'])->union(clone $deliveries);
        $attemptsByHub = DB::query()->fromSub(clone $attempts, 'attempted')->select('hub_id')->selectRaw('COUNT(DISTINCT order_id) AS total')->groupBy('hub_id')->pluck('total', 'hub_id');
        $attempted = DB::query()->fromSub($attempts, 'attempted')->distinct()->count('order_id');
        $delivered = (int) $deliveredByHub->sum();
        $hubRows = $hubs->map(function (LogisticsProfile $hub) use ($hubHandled, $deliveredByHub, $attemptsByHub): array {
            $total = (int) $attemptsByHub->get($hub->id, 0);
            $delivered = (int) $deliveredByHub->get($hub->id, 0);

            return ['name' => $hub->business_name, 'code' => $hub->public_code, 'handled' => (int) $hubHandled->get($hub->id, 0),
                'delivered' => $delivered, 'attempted' => $total, 'rate' => $total > 0 ? round(100 * $delivered / $total, 1) : null];
        });
        $labels = $values = [];
        for ($day = $localFrom->copy(); $day->lte($localToday); $day->addDay()) {
            $labels[] = $day->format('M j');
            $values[] = (int) $daily->get($day->format('Y-m-d'), 0);
        }
        $active = $hubs->filter(fn (LogisticsProfile $hub): bool => $hub->isAvailable())->count();
        $suspended = $hubs->filter(fn (LogisticsProfile $hub): bool => $hub->user->account_status === 'suspended')->count();
        $staleHours = $this->staleHours();
        $gaps = $this->coverage->gaps($company)->selectRaw('COUNT(DISTINCT hub_id) AS hubs, COUNT(*) AS barangays')->first();
        $cards = [
            ['label' => 'Total hubs', 'value' => $hubs->count(), 'hint' => $active.' active / '.$suspended.' suspended', 'href' => route('company.hubs.index')],
            ['label' => 'Total riders', 'value' => $riders, 'hint' => 'Across your hubs, all application statuses'],
            ['label' => 'Parcels handled', 'value' => $handled, 'hint' => $periodLabel.' - distinct parcels across the company'],
            ['label' => 'Delivered', 'value' => $delivered, 'hint' => $periodLabel.' - recorded delivery dates'],
            ['label' => 'Delivery success rate', 'value' => $attempted ? 100 * $delivered / $attempted : 0, 'display' => $attempted ? number_format(100 * $delivered / $attempted, 1).'%' : 'No data', 'hint' => $periodLabel.' - delivered / parcels attempted'],
            ['label' => 'Parcels in transit', 'value' => $this->transfers->transitParcels($company), 'hint' => 'Currently between your hubs', 'href' => route('company.transfers.index', ['status' => 'in_transit'])],
        ];
        $attention = [
            ['label' => 'Parcels delayed at a hub', 'count' => $this->delayed($company)->count(), 'hint' => 'No movement for more than '.$staleHours.' hours', 'href' => route('company.attention', ['type' => 'delayed'])],
            ['label' => 'Bags in transit over 48 hours', 'count' => $this->transfers->late($company)->count(), 'hint' => 'Dispatched and still in transit', 'href' => route('company.transfers.index', ['late' => 1])],
            ['label' => 'Missing inbound parcels', 'count' => $this->transfers->missingParcels($company), 'hint' => 'Still unreceived in bags with discrepancies', 'href' => route('company.transfers.index', ['missing' => 1])],
            ['label' => 'Hubs with barangays without an approved rider', 'count' => (int) $gaps->hubs, 'hint' => (int) $gaps->barangays.' covered barangays need a rider', 'href' => route('company.coverage', ['needs_rider' => 1])],
            ['label' => 'Managers inactive for 14 days', 'count' => $this->inactiveManagers($company)->count(), 'hint' => 'Includes accounts created at least 14 days ago that never logged in', 'href' => route('company.attention', ['type' => 'managers'])],
        ];

        return [
            'range' => $range, 'periodLabel' => $periodLabel, 'cards' => $cards, 'handled' => $handled, 'hasHubs' => $hubs->isNotEmpty(), 'hasAttempts' => $attempted > 0, 'attention' => $attention, 'staleHours' => $staleHours,
            'chartData' => ['daily' => ['labels' => $labels, 'values' => $values], 'hubs' => ['labels' => $hubRows->pluck('name')->all(), 'values' => $hubRows->pluck('handled')->all()], 'success' => ['labels' => $hubRows->pluck('name')->all(), 'values' => $hubRows->pluck('rate')->all()]],
            'leaderboard' => ['delivered' => $hubRows->where('delivered', '>', 0)->sortByDesc('delivered')->take(5)->values(), 'success' => $hubRows->where('attempted', '>', 0)->sortByDesc('rate')->take(5)->values()],
        ];
    }

    public function staleHours(): int
    {
        return max(1, (int) config('logistics.company_delayed_parcel_hours', 48));
    }

    public function delayed(Company $company): Builder
    {
        $cutoff = now()->subHours($this->staleHours());

        return Order::where(function (Builder $orders) use ($company): void {
            $orders->whereIn('current_hub_id', $company->hubs()->select('id'))
                ->orWhere(fn (Builder $legacy) => $legacy->whereNull('current_hub_id')->whereIn('logistics_profile_id', $company->hubs()->select('id')));
        })->whereIn('status', [Order::STATUS_AT_SORTING_CENTER, Order::STATUS_SORTED, Order::STATUS_AWAITING_TRANSFER, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_DELIVERY_FAILED])
            ->where('updated_at', '<', $cutoff)->whereDoesntHave('parcelEvents', fn (Builder $events) => $events->where('event_type', '!=', ParcelEvent::TYPE_STALE)->where('created_at', '>=', $cutoff))
            ->whereNotIn('id', ShipmentBagItem::whereIn('bag_id', $this->transfers->bags($company)->where('status', ShipmentBag::STATUS_IN_TRANSIT)->select('id'))->whereNull('received_at')->select('order_id'));
    }

    public function inactiveManagers(Company $company): Builder
    {
        $cutoff = now()->subDays(14);

        return $company->hubs()->getQuery()->whereHas('user', fn (Builder $users) => $users->where('role', 'logistics')->where(fn (Builder $activity) => $activity
            ->where('last_login_at', '<=', $cutoff)->orWhere(fn (Builder $never) => $never->whereNull('last_login_at')->where('created_at', '<=', $cutoff))));
    }
}
