<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\HubCoverage;
use App\Services\ShippingFeeCalculator;
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
        $cartItems = $this->selectedCartItems($request);

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index');
        }

        $groupedBySeller = $cartItems->groupBy('product.seller_id');
        $subtotal = $cartItems->sum('line_total');
        $itemIds = $cartItems->pluck('id')->implode(',');

        $shippingBySeller = $this->quoteShipping(
            $request,
            $groupedBySeller,
            $shipping
        );

        $shippingTotal = collect($shippingBySeller)->sum('fee');

        // Sellers for whom no single hub covers both pickup and delivery.
        $profile = $request->user()->buyerProfile;

        $blockedSellerIds = $groupedBySeller
            ->filter(
                fn($items) => ! $coverage->isCovered(
                    $items->first()->product->seller,
                    $profile?->province,
                    $profile?->municipality
                )
            )
            ->keys()
            ->all();

        return view('Buyer.checkout.index', [
            'groupedBySeller' => $groupedBySeller,
            'total' => $subtotal,
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
        HubCoverage $coverage
    ) {
        $buyer = $request->user();

        // Validate optional buyer notes for each seller's order.
        $request->validate([
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        // Shipping fees and hub coverage require a complete delivery address.
        if (
            ! $buyer->buyerProfile?->province
            || ! $buyer->buyerProfile?->municipality
        ) {
            return back()->withErrors([
                'address' => 'Please add your delivery address before placing your order.',
            ]);
        }

        $placed = DB::transaction(function () use (
            $request,
            $buyer,
            $shipping,
            $coverage
        ) {
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

            // 3b. A single hub must cover both the seller's pickup area
            // and the buyer's delivery area.
            foreach ($cartItems->groupBy('product.seller_id') as $items) {
                $seller = $items->first()->product->seller;

                if (! $coverage->isCovered(
                    $seller,
                    $buyer->buyerProfile->province,
                    $buyer->buyerProfile->municipality
                )) {
                    $name = $seller->sellerProfile->business_name
                        ?? $seller->name;

                    $this->fail(
                        "\"{$name}\" can't deliver to your address yet. Please remove their items from your cart."
                    );
                }
            }

            // 4. Create one order per seller and deduct stock.
            $touchedVariantProducts = [];

            foreach (
                $cartItems->groupBy('product.seller_id') as $sellerId => $items
            ) {
                $orderTotal = $items->sum('line_total');

                // Recalculate shipping on the server using the current address.
                $quote = $shipping->calculate(
                    $items->first()->product->seller,
                    $buyer->buyerProfile?->province,
                    $buyer->buyerProfile?->municipality
                );

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
                    'ship_province' => $buyer->buyerProfile?->province,
                    'ship_municipality' => $buyer->buyerProfile?->municipality,
                    'ship_barangay' => $buyer->buyerProfile?->barangay,
                    'ship_street' => $buyer->buyerProfile
                        ? trim(
                            ($buyer->buyerProfile->house_number ?? '')
                                . ' '
                                . ($buyer->buyerProfile->street ?? '')
                        )
                        : null,
                    'ship_contact_no' => $buyer->buyerProfile?->contact_no,

                    // Save the optional note for this seller's order.
                    'buyer_note' => trim(
                        (string) $request->input("notes.{$sellerId}")
                    ) ?: null,
                ]);

                $order->logStatusEvent(null, $buyer->id);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'selected_options' => $item->selected_options,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->unit_price,
                        'original_price' => $item->variant?->price
                            ?? $item->product->price,
                        'subtotal' => $item->line_total,
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

            // 5. Synchronize each variant product's total stock.
            foreach ($touchedVariantProducts as $product) {
                $product->syncFromVariants();
            }

            // 6. Remove only the cart items that were checked out.
            $cartItems->pluck('id')->chunk(500)->each(
                fn($chunk) => CartItem::whereIn('id', $chunk)->delete()
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
     * @param Collection<int|string, Collection<int, \App\Models\CartItem>> $groupedBySeller
     * @return array<int|string, array{fee: float, zone: string}>
     */
    private function quoteShipping(
        Request $request,
        Collection $groupedBySeller,
        ShippingFeeCalculator $shipping
    ): array {
        $profile = $request->user()->buyerProfile;
        $quotes = [];

        foreach ($groupedBySeller as $sellerId => $items) {
            $quotes[$sellerId] = $shipping->calculate(
                $items->first()->product->seller,
                $profile?->province,
                $profile?->municipality
            );
        }

        return $quotes;
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
