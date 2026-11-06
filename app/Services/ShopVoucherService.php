<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShopVoucher;
use App\Models\ShopVoucherRedemption;
use App\Models\User;
use App\Models\VoucherClaim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopVoucherService
{
    public function generateCode(): string
    {
        return ShopVoucher::generateUniqueCode(10);
    }

    public function getAvailableVouchersForBuyer(User $buyer): Collection
    {
        // Get vouchers from sellers where buyer might have items
        return ShopVoucher::with('seller.sellerProfile')
            ->where(fn ($query) => $query->whereNull('seller_id')->orWhereHas('claims', fn ($claims) => $claims->where('user_id', $buyer->id)->whereNull('used_at')))
            ->where('is_active', true)
            ->where(function ($q) {
                $now = now();
                $q->where(function ($q2) use ($now) {
                    $q2->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })->where(function ($q2) use ($now) {
                    $q2->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                });
            })
            ->where(function ($q) {
                $q->whereNull('total_quantity')
                    ->orWhereRaw('used_quantity < total_quantity');
            })
            ->whereIn('status', ['active', 'scheduled'])
            ->get()
            ->filter(fn (ShopVoucher $voucher) => $voucher->canBeUsedByBuyer($buyer->id) && $voucher->hasUnusedClaim($buyer))
            ->values();
    }

    public function getRedeemedVouchersForBuyer(User $buyer): Collection
    {
        return ShopVoucherRedemption::with(['voucher.seller.sellerProfile', 'seller.sellerProfile', 'order'])
            ->where('buyer_id', $buyer->id)
            ->whereHas('voucher', fn ($query) => $query->whereNull('seller_id')->orWhereHas('claims', fn ($claims) => $claims->where('user_id', $buyer->id)))
            ->orderByDesc('created_at')
            ->get();
    }

    public function validateAndApplyForCart(User $buyer, string $code, Collection $cartItems, bool $lock = false): array
    {
        $voucher = ShopVoucher::where('code', strtoupper(trim($code)))->when($lock, fn ($query) => $query->lockForUpdate())->first();

        if (! $voucher) {
            throw new \Exception('Invalid voucher code.');
        }

        if (! $voucher->hasUnusedClaim($buyer)) {
            throw new \Exception('Claim this voucher from the shop before using it, or choose an unused claimed voucher.');
        }

        if (! $voucher->isValidNow()) {
            throw new \Exception('This voucher is not valid or has expired.');
        }

        if (! $voucher->canBeUsedByBuyer($buyer->id)) {
            throw new \Exception('You have already used this voucher the maximum number of times.');
        }

        // Group by seller
        $grouped = $cartItems->groupBy(function ($item) {
            return $item->product->seller_id;
        });

        // Voucher only applies to its issuing seller
        if (! $grouped->has($voucher->seller_id)) {
            throw new \Exception('This voucher is not applicable to items in your cart.');
        }

        $sellerItems = $grouped->get($voucher->seller_id);
        $eligibleSubtotal = $sellerItems->sum('line_total');
        $eligibleQuantity = $sellerItems->sum('quantity');

        $eligibleItems = $sellerItems;

        // Check eligible products/categories if specified
        if ($voucher->eligible_product_ids && count($voucher->eligible_product_ids) > 0) {
            $eligibleProductIds = $voucher->eligible_product_ids;
            $eligibleItems = $eligibleItems->filter(function ($item) use ($eligibleProductIds) {
                return in_array($item->product_id, $eligibleProductIds, true);
            });
            if ($eligibleItems->isEmpty()) {
                throw new \Exception('This voucher is not applicable to the products in your cart.');
            }
            $eligibleSubtotal = $eligibleItems->sum('line_total');
            $eligibleQuantity = $eligibleItems->sum('quantity');
        }

        if ($voucher->eligible_category_ids && count($voucher->eligible_category_ids) > 0) {
            // Categories stored as array - check product categories
            $eligibleCategoryIds = $voucher->eligible_category_ids;
            $eligibleItems = $eligibleItems->filter(function ($item) use ($eligibleCategoryIds) {
                $category = $item->product->category;

                return in_array($category, $eligibleCategoryIds, true) || in_array((string) $category, $eligibleCategoryIds, true);
            });
            if ($eligibleItems->isEmpty()) {
                throw new \Exception('This voucher is not applicable to the product categories in your cart.');
            }
            $eligibleSubtotal = $eligibleItems->sum('line_total');
            $eligibleQuantity = $eligibleItems->sum('quantity');
        }

        $discount = $voucher->calculateDiscount($eligibleSubtotal, $eligibleQuantity);

        if ($discount <= 0) {
            throw new \Exception('This voucher does not meet the minimum purchase requirements.');
        }

        return [
            'voucher' => $voucher,
            'discount' => $discount,
            'eligible_subtotal' => $eligibleSubtotal,
            'eligible_items' => $eligibleItems,
        ];
    }

    public function claim(User $buyer, ShopVoucher $voucher): VoucherClaim
    {
        return DB::transaction(function () use ($buyer, $voucher): VoucherClaim {
            $voucher = ShopVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if ($buyer->role !== 'buyer' || ! $voucher->isShopVoucher()) {
                throw ValidationException::withMessages(['voucher' => 'Only buyers can claim shop vouchers.']);
            }
            if (! $voucher->seller?->isEligibleSeller()) {
                throw ValidationException::withMessages(['voucher' => 'This shop is unavailable.']);
            }
            if ($reason = $voucher->unavailableReason()) {
                throw ValidationException::withMessages(['voucher' => 'This voucher is '.strtolower($reason).'.']);
            }
            if (! $buyer->followedSellers()->whereKey($voucher->seller_id)->exists()) {
                throw ValidationException::withMessages(['voucher' => 'Follow this shop before claiming its vouchers.']);
            }
            if ($voucher->claims()->where('user_id', $buyer->id)->exists()) {
                throw ValidationException::withMessages(['voucher' => 'You have already claimed this voucher.']);
            }
            if ($voucher->claim_limit !== null && $voucher->claims()->count() >= $voucher->claim_limit) {
                throw ValidationException::withMessages(['voucher' => 'This voucher is fully claimed.']);
            }

            return $buyer->voucherClaims()->create(['voucher_id' => $voucher->id, 'claimed_at' => now()]);
        }, 3);
    }

    public function restoreForCancelledOrder(Order $order): void
    {
        if (! $order->shop_voucher_id) {
            return;
        }
        DB::transaction(function () use ($order): void {
            $voucher = ShopVoucher::whereKey($order->shop_voucher_id)->lockForUpdate()->first();
            if (! $voucher) {
                return;
            }
            $redemptions = $voucher->redemptions()->where('order_id', $order->id)->whereIn('status', ['applied', 'used'])->lockForUpdate()->get();
            if ($redemptions->isEmpty()) {
                return;
            }
            foreach ($redemptions as $redemption) {
                $redemption->update(['status' => 'cancelled']);
            }
            $voucher->claims()->where('user_id', $order->buyer_id)->where('order_id', $order->id)->update(['used_at' => null, 'order_id' => null]);
            $voucher->used_quantity = max(0, $voucher->used_quantity - $redemptions->count());
            if ($voucher->status === 'exhausted' && (! $voucher->expires_at || $voucher->expires_at->isFuture())) {
                $voucher->status = 'active';
                $voucher->is_active = true;
            }
            $voucher->save();
        });
    }
}
