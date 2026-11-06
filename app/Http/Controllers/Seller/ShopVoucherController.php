<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ShopVoucher;
use App\Services\ShopVoucherService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopVoucherController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $vouchers = $request->user()
            ->shopVouchers()
            ->withCount(['redemptions', 'claims', 'usedClaims'])
            ->latest()
            ->paginate(20);

        return view('seller.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('seller.vouchers.create');
    }

    public function store(Request $request, ShopVoucherService $service)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'unique:shop_vouchers,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_quantity' => ['nullable', 'integer', 'min:1'],
            'claim_limit' => ['nullable', 'integer', 'min:1'],
            'per_buyer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'combine_with_promotions' => ['nullable', 'boolean'],
            'eligible_product_ids' => ['nullable', 'array'],
            'eligible_category_ids' => ['nullable', 'array'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($data['code'])) {
            $data['code'] = $service->generateCode();
        } else {
            $data['code'] = strtoupper(trim($data['code']));
        }

        if ($data['discount_type'] === 'percentage') {
            if ((float) $data['discount_value'] > 100) {
                throw ValidationException::withMessages(['discount_value' => 'Percentage discount cannot exceed 100%.']);
            }
            if (empty($data['max_discount_amount']) || (float) $data['max_discount_amount'] <= 0) {
                throw ValidationException::withMessages(['max_discount_amount' => 'Maximum discount cap is required for percentage discounts.']);
            }
        }

        $isActive = $request->boolean('is_active');
        $data['seller_id'] = $request->user()->id;
        $data['is_active'] = $isActive;
        $data['status'] = $isActive ? 'active' : 'draft';
        $data['used_quantity'] = 0;
        $data['per_buyer_limit'] = $data['per_buyer_limit'] ?? 1;
        $data['min_purchase_amount'] = $data['min_purchase_amount'] ?? 0;

        ShopVoucher::create($data);

        return redirect()->route('seller.vouchers.index')->with('status', 'Voucher created successfully.');
    }

    public function show(Request $request, ShopVoucher $shopVoucher)
    {
        $this->authorize('view', $shopVoucher);
        $shopVoucher->load(['redemptions.buyer', 'redemptions.order'])->loadCount(['claims', 'usedClaims']);
        $stats = [
            'total_redemptions' => $shopVoucher->redemptions()->whereIn('status', ['applied', 'used'])->count(),
            'total_discount' => $shopVoucher->redemptions()->whereIn('status', ['applied', 'used'])->sum('discount_amount'),
        ];

        return view('seller.vouchers.show', compact('shopVoucher', 'stats'));
    }

    public function edit(Request $request, ShopVoucher $shopVoucher)
    {
        $this->authorize('update', $shopVoucher);

        return view('seller.vouchers.edit', compact('shopVoucher'));
    }

    public function update(Request $request, ShopVoucher $shopVoucher)
    {
        $this->authorize('update', $shopVoucher);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('shop_vouchers', 'code')->ignore($shopVoucher->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_quantity' => ['nullable', 'integer', 'min:'.$shopVoucher->used_quantity],
            'claim_limit' => ['nullable', 'integer', 'min:1'],
            'per_buyer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'combine_with_promotions' => ['nullable', 'boolean'],
            'eligible_product_ids' => ['nullable', 'array'],
            'eligible_category_ids' => ['nullable', 'array'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        if ($data['discount_type'] === 'percentage') {
            if ((float) $data['discount_value'] > 100) {
                throw ValidationException::withMessages(['discount_value' => 'Percentage discount cannot exceed 100%.']);
            }
            if (empty($data['max_discount_amount']) || (float) $data['max_discount_amount'] <= 0) {
                throw ValidationException::withMessages(['max_discount_amount' => 'Maximum discount cap is required for percentage discounts.']);
            }
        }

        $isActive = $request->boolean('is_active');
        $data['is_active'] = $isActive;
        $data['status'] = $isActive ? 'active' : 'disabled';
        $data['per_buyer_limit'] = $data['per_buyer_limit'] ?? 1;
        $data['min_purchase_amount'] = $data['min_purchase_amount'] ?? 0;

        DB::transaction(function () use ($shopVoucher, $data): void {
            $locked = ShopVoucher::whereKey($shopVoucher->id)->lockForUpdate()->firstOrFail();
            if (($data['claim_limit'] ?? null) !== null && $data['claim_limit'] < $locked->claims()->count()) {
                throw ValidationException::withMessages(['claim_limit' => 'The claim limit cannot be below the number already claimed.']);
            }
            if (($data['total_quantity'] ?? null) !== null && $data['total_quantity'] < $locked->used_quantity) {
                throw ValidationException::withMessages(['total_quantity' => 'The quantity cannot be below the number already used.']);
            }
            $locked->update($data);
        });

        return redirect()->route('seller.vouchers.index')->with('status', 'Voucher updated successfully.');
    }

    public function toggle(Request $request, ShopVoucher $shopVoucher)
    {
        $this->authorize('update', $shopVoucher);

        $shopVoucher->is_active = ! $shopVoucher->is_active;
        $shopVoucher->status = $shopVoucher->is_active ? 'active' : 'disabled';
        $shopVoucher->save();

        return back()->with('status', $shopVoucher->is_active ? 'Voucher activated.' : 'Voucher disabled.');
    }

    public function generateCode(ShopVoucherService $service)
    {
        return response()->json(['code' => $service->generateCode()]);
    }
}
