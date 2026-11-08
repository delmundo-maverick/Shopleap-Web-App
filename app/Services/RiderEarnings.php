<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RiderRate;
use App\Models\ShippingRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class RiderEarnings
{
    public const EARNED_STATUSES = [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED];

    public function currentRate(?int $hubId): ?RiderRate
    {
        return $hubId === null ? null : RiderRate::where('logistics_profile_id', $hubId)
            ->where('effective_from', '<=', now())->orderByDesc('effective_from')->orderByDesc('id')->first();
    }

    public function lowestShippingFee(): ?string
    {
        $fee = ShippingRate::min('fee');

        return $fee === null ? null : self::money($fee);
    }

    /** @return array{amount: string, currency: string, recorded_at: string}|null */
    public function earning(Order $order): ?array
    {
        return $order->earnings_recorded_at && $order->rider_pay !== null
            ? ['amount' => $order->rider_pay, 'currency' => 'PHP', 'recorded_at' => $order->earnings_recorded_at->toIso8601String()]
            : null;
    }

    public function deliveries(?int $hubId, ?int $courierId = null, ?Carbon $from = null, ?Carbon $to = null): Builder
    {
        return Order::query()->whereIn('status', self::EARNED_STATUSES)->whereNotNull('delivered_at')
            ->when($hubId !== null, fn (Builder $query) => $query->where(function (Builder $scope) use ($hubId): void {
                $scope->where('earning_hub_id', $hubId)->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('earnings_recorded_at')->whereRaw('COALESCE(destination_hub_id, logistics_profile_id) = ?', [$hubId]));
            }))
            ->when($courierId !== null, fn (Builder $query) => $query->where(function (Builder $scope) use ($courierId): void {
                $scope->where('earning_courier_id', $courierId)->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('earnings_recorded_at')->where('delivery_rider_id', $courierId));
            }))
            ->when($from, fn (Builder $query) => $query->where('delivered_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('delivered_at', '<=', $to));
    }

    /** @return array{deliveries: int, shipping_fees: string|null, rider_pay: string|null, margin: string|null, cod_collected: string, snapshots: int, paid_snapshots: int} */
    public function totals(Builder $query): array
    {
        $totals = (clone $query)->toBase()->selectRaw('COUNT(*) AS deliveries, COUNT(delivered_shipping_fee) AS snapshots, COUNT(rider_pay) AS paid_snapshots,
            SUM(delivered_shipping_fee) AS shipping_fees, SUM(rider_pay) AS rider_pay,
            SUM(delivered_shipping_fee - rider_pay) AS margin, COALESCE(SUM(cod_collected_amount), 0) AS cod_collected')->first();

        return ['deliveries' => (int) $totals->deliveries, 'snapshots' => (int) $totals->snapshots, 'paid_snapshots' => (int) $totals->paid_snapshots,
            'shipping_fees' => $totals->shipping_fees === null ? null : self::money($totals->shipping_fees),
            'rider_pay' => $totals->rider_pay === null ? null : self::money($totals->rider_pay),
            'margin' => $totals->margin === null || (int) $totals->paid_snapshots < (int) $totals->snapshots ? null : self::money($totals->margin), 'cod_collected' => self::money($totals->cod_collected)];
    }

    public static function money(string|float|int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
