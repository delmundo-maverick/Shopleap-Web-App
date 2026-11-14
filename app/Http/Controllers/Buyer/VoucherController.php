<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\ShopVoucher;
use App\Services\ShopVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request, ShopVoucherService $service)
    {
        $available = $service->getAvailableVouchersForBuyer($request->user());
        $redeemed = $service->getRedeemedVouchersForBuyer($request->user());

        $expired = $request->user()->voucherClaims()->with('voucher.seller.sellerProfile')->whereNull('used_at')->whereHas('voucher', fn ($query) => $query->where('expires_at', '<=', now())->orWhere('status', 'expired'))->get();

        return view('Buyer.vouchers.index', compact('available', 'redeemed', 'expired'));
    }

    public function claim(Request $request, ShopVoucher $shopVoucher, ShopVoucherService $service): JsonResponse
    {
        $claim = $service->claim($request->user(), $shopVoucher);
        $shopVoucher->loadCount('claims');

        return response()->json(['message' => 'Voucher claimed. Find it in My Vouchers.', 'claim_id' => $claim->id,
            'remaining' => $shopVoucher->claim_limit === null ? null : max(0, $shopVoucher->claim_limit - $shopVoucher->claims_count)]);
    }

    public function dropdown(Request $request, ShopVoucherService $service)
    {
        $available = $service->getAvailableVouchersForBuyer($request->user());
        $redeemed = $service->getRedeemedVouchersForBuyer($request->user());

        return response()->json([
            'available' => $available->map(function ($v) {
                return [
                    'code' => $v->code,
                    'discount_type' => $v->discount_type,
                    'discount_value' => $v->discount_value,
                    'min_purchase_amount' => $v->min_purchase_amount,
                    'max_discount_amount' => $v->max_discount_amount,
                    'expires_at' => $v->expires_at?->toIso8601String(),
                    'seller_name' => $v->seller?->sellerProfile?->business_name ?? $v->seller?->name,
                    'remaining' => $v->remaining_quantity,
                ];
            }),
            'redeemed' => $redeemed->map(function ($r) {
                return [
                    'code' => $r->voucher?->code,
                    'discount_amount' => $r->discount_amount,
                    'status' => $r->status,
                    'order_id' => $r->order?->order_number,
                    'seller_name' => $r->seller?->sellerProfile?->business_name ?? $r->seller?->name,
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function apply(Request $request, ShopVoucherService $service)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'items' => ['nullable', 'string'],
        ]);

        $cartItems = $request->user()->cartItems()->with(['product.seller.sellerProfile', 'product', 'variant'])->get();
        if ($request->filled('items')) {
            $ids = array_filter(explode(',', $request->items), 'is_numeric');
            if (! empty($ids)) {
                $cartItems = $cartItems->whereIn('id', $ids);
            }
        }

        try {
            $result = $service->validateAndApplyForCart($request->user(), $request->code, $cartItems);

            return response()->json([
                'success' => true,
                'voucher_code' => $result['voucher']->code,
                'discount' => $result['discount'],
                'message' => 'Voucher applied successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
