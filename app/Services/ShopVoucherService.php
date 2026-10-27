<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ShopVoucher;
use App\Models\ShopVoucherRedemption;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->filter(fn (ShopVoucher $voucher) => $voucher->canBeUsedByBuyer($buyer->id))
            ->values();
    }

    public function getRedeemedVouchersForBuyer(User $buyer): Collection
    {
        return ShopVoucherRedemption::with(['voucher.seller.sellerProfile', 'order'])
            ->where('buyer_id', $buyer->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function validateAndApplyForCart(User $buyer, string $code, Collection $cartItems): array
    {
        $voucher = ShopVoucher::where('code', strtoupper(trim($code)))->first();

        if (! $voucher) {
            throw new \Exception('Invalid voucher code.');
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

        // Check eligible products/categories if specified
        if ($voucher->eligible_product_ids && count($voucher->eligible_product_ids) > 0) {
            $eligibleProductIds = $voucher->eligible_product_ids;
            $eligibleItems = $sellerItems->filter(function ($item) use ($eligibleProductIds) {
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
            $eligibleItems = $sellerItems->filter(function ($item) use ($eligibleCategoryIds) {
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
            'eligible_items' => $sellerItems,
        ];
    }
}
