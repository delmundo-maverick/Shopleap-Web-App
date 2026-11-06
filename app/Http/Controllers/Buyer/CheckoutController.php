<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\BuyerAddress;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopVoucher;
use App\Models\ShopVoucherRedemption;
use App\Services\HubCoverage;
use App\Services\ParcelLifecycle;
use App\Services\ShippingFeeCalculator;
use App\Services\ShopVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(
        Request $request,
        ShippingFeeCalculator $shipping,
        HubCoverage $coverage
    ) {
        $address = $this->selectedAddress($request);
        $cartItems = $this->selectedCartItems($request);

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index');
        }

        $groupedBySeller = $cartItems->groupBy('product.seller_id');
        $subtotal = $cartItems->sum('line_total');
        $itemIds = $cartItems->pluck('id')->implode(',');

        // Parcel weight per seller, used for display only. Shipping fees stay zone-based.
        $weightBySeller = $groupedBySeller
            ->map(fn ($items) => round((float) $items->sum('line_weight_kg'), 3))
            ->all();

        $totalWeight = round(array_sum($weightBySeller), 3);

        $shippingBySeller = $this->quoteShipping(
            $request,
            $groupedBySeller,
            $shipping
        );

        $shippingTotal = collect($shippingBySeller)->sum('fee');

        // Sellers whose pickup hub and delivery hub can't be paired under one
        // logistics company.
        $profile = $address;

        $blockedSellerIds = $groupedBySeller
            ->filter(
                fn ($items) => ! $coverage->isCovered(
                    $items->first()->product->seller,
                    $profile?->province,
                    $profile?->city_municipality,
                    $profile?->barangay
                )
            )
            ->keys()
            ->all();

        if ($request->expectsJson()) {
            return response()->json(['shipping' => $shippingBySeller, 'shippingTotal' => $shippingTotal,
                'grandTotal' => $subtotal + $shippingTotal, 'blocked' => $blockedSellerIds]);
        }

        return view('Buyer.checkout.index', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest('id')->get(),
            'selectedAddress' => $address,
            'claimedVouchers' => app(ShopVoucherService::class)->getAvailableVouchersForBuyer($request->user()),
            'groupedBySeller' => $groupedBySeller,
            'total' => $subtotal,
            'weightBySeller' => $weightBySeller,
            'totalWeight' => $totalWeight,
            'shippingBySeller' => $shippingBySeller,
            'shippingTotal' => $shippingTotal,
            'grandTotal' => $subtotal + $shippingTotal,
            'itemIds' => $itemIds,
            'blockedSellerIds' => $blockedSellerIds,
        ]);
    }

    public function store(
        Request $request,
        ShippingFeeCalculator $shipping,
        HubCoverage $coverage,
        ShopVoucherService $voucherService,
        ParcelLifecycle $lifecycle
    ) {
        $buyer = $request->user();

        // Validate optional buyer notes for each seller's order.
        $request->validate([
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        $request->validate(['address_id' => ['required', 'integer']]);

        $placed = DB::transaction(function () use (
            $request,
            $buyer,
            $shipping,
            $coverage,
            $voucherService,
            $lifecycle
        ) {
            $address = $buyer->addresses()->whereKey($request->integer('address_id'))->lockForUpdate()->first();
            if (! $address) {
                throw ValidationException::withMessages(['address_id' => 'Select one of your saved delivery addresses.']);
            }

            // 1. Lock the cart rows so a double-click cannot place the order twice.
            $cartItems = $this->selectedCartItems(
                $request,
                withLock: true
            );

            if ($cartItems->isEmpty()) {
                return false;
            }

            // 2. Lock stock rows in a fixed order: products, then variants,
            // ascending by ID, to reduce the risk of deadlocks.
            $productIds = $cartItems
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $variants = ProductVariant::whereIn(
                'id',
                $cartItems
                    ->pluck('product_variant_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Products with variants must not be purchased as bare products.
            $variantProducts = ProductVariant::whereIn(
                'product_id',
                $productIds
            )
                ->pluck('product_id')
                ->flip();

            // 3. Validate against the locked, fresh product and variant rows.
            foreach ($cartItems as $item) {
                $product = $products->get($item->product_id);

                $variant = $item->product_variant_id
                    ? $variants->get($item->product_variant_id)
                    : null;

                $name = $product?->name ?? 'An item';

                $unavailable = ! $product
                    || $product->status !== 'active'
                    || (
                        $item->product_variant_id
                        && (! $variant || ! $variant->is_active)
                    )
                    || (
                        ! $item->product_variant_id
                        && $variantProducts->has($product->id)
                    );

                if ($unavailable) {
                    $this->fail(
                        "\"{$name}\" is no longer available. Please update your cart."
                    );
                }

                $stock = $variant?->stock_quantity
                    ?? $product->stock_quantity;

                if ($stock < $item->quantity) {
                    $this->fail(
                        "\"{$name}\" no longer has enough stock. Please update your cart."
                    );
                }
            }

            // 3b. Resolve the pickup hub and delivery hub for each seller.
            // Both ends must belong to the same logistics company.
            $routes = [];

            foreach ($cartItems->groupBy('product.seller_id') as $sellerId => $items) {
                $seller = $items->first()->product->seller;

                $route = $coverage->routeFor(
                    $seller,
                    $address->province,
                    $address->city_municipality,
                    $address->barangay
                );

                if (! $route) {
                    $name = $seller->sellerProfile->business_name
                        ?? $seller->name;

                    $this->fail(
                        "\"{$name}\" can't deliver to your address yet. No logistics company serves both the seller's pickup area and your barangay. Please remove their items from your cart."
                    );
                }

                $routes[$sellerId] = $route;
            }

            // 4. Create one order per seller and deduct stock.
            $touchedVariantProducts = [];
            $voucherOrder = null;

            // Handle voucher if applied
            $appliedVoucher = null;
            $voucherDiscountTotal = 0;
            if ($request->filled('voucher_code')) {
                try {
                    $appliedVoucher = $voucherService->validateAndApplyForCart($buyer, $request->voucher_code, $cartItems, lock: true);
                } catch (\Throwable $e) {
                    $this->fail($e->getMessage());
                }
            }

            foreach (
                $cartItems->groupBy('product.seller_id') as $sellerId => $items
            ) {
                $orderTotal = $items->sum('line_total');
                $sellerVoucherDiscount = 0;
                $voucherModel = null;
                if ($appliedVoucher && $appliedVoucher['voucher']->seller_id == $sellerId) {
                    $voucherModel = $appliedVoucher['voucher'];
                    // Re-validate under lock
                    $voucherModel = ShopVoucher::where('id', $voucherModel->id)->lockForUpdate()->first();
                    if (! $voucherModel || ! $voucherModel->isValidNow() || ! $voucherModel->canBeUsedByBuyer($buyer->id) || ! $voucherModel->hasUnusedClaim($buyer)) {
                        $this->fail('Voucher is no longer valid.');
                    }
                    $eligibleSubtotal = $appliedVoucher['eligible_items']->sum('line_total');
                    $eligibleQuantity = $appliedVoucher['eligible_items']->sum('quantity');
                    $sellerVoucherDiscount = $voucherModel->calculateDiscount($eligibleSubtotal, $eligibleQuantity);
                    $voucherDiscountTotal += $sellerVoucherDiscount;
                }

                // Recalculate shipping on the server using the current address.
                $quote = $shipping->calculate(
                    $items->first()->product->seller,
                    $address->province,
                    $address->city_municipality
                );

                // Commission calculated on order total (items subtotal) as per existing policy
                $route = $routes[$sellerId];

                $order = Order::create([
                    'seller_id' => $sellerId,
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                    'status' => Order::STATUS_PLACED,
                    'total_amount' => $orderTotal,
                    'shipping_fee' => $quote['fee'],
                    'shipping_zone' => $quote['zone'],
                    'commission_amount' => round(
                        $orderTotal * PlatformSetting::commissionRate(),
                        2
                    ),
                    'shop_voucher_id' => $voucherModel?->id,
                    'voucher_code' => $voucherModel?->code,
                    'voucher_discount' => $sellerVoucherDiscount,
                    'origin_hub_id' => $route['origin']->id,
                    'destination_hub_id' => $route['destination']->id,
                    'ship_province' => $address->province,
                    'ship_municipality' => $address->city_municipality,
                    'ship_barangay' => $address->barangay,
                    'ship_street' => $address->street_address,
                    'address_id' => $address->id,
                    'shipping_address' => $address->snapshot(),
                    'ship_contact_no' => $address->phone,

                    // Save the optional note for this seller's order.
                    'buyer_note' => trim(
                        (string) $request->input("notes.{$sellerId}")
                    ) ?: null,
                ]);

                if ($voucherModel) {
                    $voucherOrder = $order;
                }
                $lifecycle->placed($order, $buyer->id);

                // Allocate voucher discount proportionally to eligible items
                $eligibleItemsForVoucher = collect();
                if ($voucherModel && isset($appliedVoucher)) {
                    $eligibleItemsForVoucher = $appliedVoucher['eligible_items']->pluck('id')->flip();
                }
                $eligibleSubtotalForAlloc = 0;
                if ($voucherModel && $eligibleItemsForVoucher->isNotEmpty()) {
                    $eligibleSubtotalForAlloc = $items->filter(fn ($it) => $eligibleItemsForVoucher->has($it->id))->sum('line_total');
                }

                foreach ($items as $item) {
                    $itemVoucherDiscount = 0;
                    if ($voucherModel && $sellerVoucherDiscount > 0 && $eligibleSubtotalForAlloc > 0 && $eligibleItemsForVoucher->has($item->id)) {
                        $itemVoucherDiscount = round($sellerVoucherDiscount * ($item->line_total / $eligibleSubtotalForAlloc), 2);
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'selected_options' => $item->selected_options,
                        'quantity' => $item->quantity,
                        'weight_kg' => $item->line_weight_kg,
                        'price_at_purchase' => $item->unit_price,
                        'original_price' => $item->variant?->price
                            ?? $item->product->price,
                        'subtotal' => $item->line_total,
                        'voucher_discount_allocated' => $itemVoucherDiscount,
                    ]);

                    // Conditional decrement prevents stock from going below zero.
                    if ($item->product_variant_id) {
                        $affected = ProductVariant::whereKey(
                            $item->product_variant_id
                        )
                            ->where('stock_quantity', '>=', $item->quantity)
                            ->decrement('stock_quantity', $item->quantity);

                        $touchedVariantProducts[$item->product_id] =
                            $products->get($item->product_id);
                    } else {
                        $affected = Product::whereKey($item->product_id)
                            ->where('stock_quantity', '>=', $item->quantity)
                            ->decrement('stock_quantity', $item->quantity);
                    }

                    if (! $affected) {
                        $this->fail(
                            "\"{$item->product->name}\" no longer has enough stock. Please update your cart."
                        );
                    }
                }
            }

            // Record voucher redemption if used
            if (isset($appliedVoucher) && $appliedVoucher['voucher'] && $voucherDiscountTotal > 0) {
                $voucherModelFinal = ShopVoucher::where('id', $appliedVoucher['voucher']->id)->lockForUpdate()->first();
                if ($voucherModelFinal && $voucherModelFinal->canBeUsedByBuyer($buyer->id)) {
                    $orderForRedemption = $voucherOrder;
                    $claim = $voucherModelFinal->claims()->where('user_id', $buyer->id)->lockForUpdate()->first();
                    if ($voucherModelFinal->isShopVoucher() && (! $claim || $claim->used_at)) {
                        $this->fail('This voucher has not been claimed or has already been used.');
                    }
                    $claim?->update(['used_at' => now(), 'order_id' => $orderForRedemption->id]);
                    ShopVoucherRedemption::create([
                        'shop_voucher_id' => $voucherModelFinal->id,
                        'buyer_id' => $buyer->id,
                        'seller_id' => $voucherModelFinal->seller_id,
                        'order_id' => $orderForRedemption?->id,
                        'discount_amount' => $voucherDiscountTotal,
                        'order_subtotal' => $cartItems->sum('line_total'),
                        'status' => 'used',
                        'used_at' => now(),
                    ]);
                    $voucherModelFinal->increment('used_quantity');
                    if (! is_null($voucherModelFinal->total_quantity) && $voucherModelFinal->used_quantity >= $voucherModelFinal->total_quantity) {
                        $voucherModelFinal->status = 'exhausted';
                        $voucherModelFinal->is_active = false;
                        $voucherModelFinal->save();
                    }
                } else {
                    $this->fail('Voucher is no longer available.');
                }
            }

            // 5. Synchronize each variant product's total stock.
            foreach ($touchedVariantProducts as $product) {
                $product->syncFromVariants();
            }

            // 6. Remove only the cart items that were checked out.
            $cartItems->pluck('id')->chunk(500)->each(
                fn ($chunk) => CartItem::whereIn('id', $chunk)->delete()
            );

            return true;
        });

        if (! $placed) {
            return redirect()
                ->route('buyer.cart.index')
                ->with('status', 'Your cart is empty.');
        }

        return redirect()
            ->route('buyer.orders.index')
            ->with(
                'status',
                'Order placed successfully! You can track its status here.'
            );
    }

    /**
     * Abort checkout and roll back the transaction.
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'stock' => $message,
        ]);
    }

    /**
     * Calculate shipping for each seller.
     *
     * @param  Collection<int|string, Collection<int, CartItem>>  $groupedBySeller
     * @return array<int|string, array{fee: float, zone: string}>
     */
    private function quoteShipping(
        Request $request,
        Collection $groupedBySeller,
        ShippingFeeCalculator $shipping
    ): array {
        $profile = $this->selectedAddress($request);
        $quotes = [];

        foreach ($groupedBySeller as $sellerId => $items) {
            $quotes[$sellerId] = $shipping->calculate(
                $items->first()->product->seller,
                $profile?->province,
                $profile?->city_municipality
            );
        }

        return $quotes;
    }

    private function selectedAddress(Request $request): ?BuyerAddress
    {
        if (! $request->filled('address_id') && $request->old('address_id')) {
            $request->merge(['address_id' => $request->old('address_id')]);
        }
        if ($request->filled('address_id')) {
            $request->validate(['address_id' => ['integer']]);
            $address = $request->user()->addresses()->find($request->integer('address_id'));
            if (! $address) {
                throw ValidationException::withMessages(['address_id' => 'Select one of your saved addresses.']);
            }

            return $address;
        }

        return $request->user()->defaultAddress;
    }

    /**
     * Retrieve the current buyer's cart items.
     *
     * If an `items` parameter containing comma-separated cart item IDs is
     * present, retrieve only those selected items. Otherwise, use the full cart.
     */
    private function selectedCartItems(
        Request $request,
        bool $withLock = false
    ) {
        $query = $request->user()->cartItems()->with([
            'product.seller.sellerProfile',
            'product.liveDiscount',
            'variant',
        ]);

        if ($withLock) {
            $query->lockForUpdate();
        }

        if ($request->filled('items')) {
            $ids = is_array($request->items)
                ? $request->items
                : explode(',', $request->items);

            $query->whereIn(
                'id',
                array_filter($ids, 'is_numeric')
            );
        }

        return $query->get();
    }
}
