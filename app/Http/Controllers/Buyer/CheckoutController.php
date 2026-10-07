<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $this->selectedCartItems($request);

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index');
        }

        $groupedBySeller = $cartItems->groupBy('product.seller_id');
        $total = $cartItems->sum('line_total');
        $itemIds = $cartItems->pluck('id')->implode(',');

        return view('Buyer.checkout.index', [
            'groupedBySeller' => $groupedBySeller,
            'total' => $total,
            'itemIds' => $itemIds,
        ]);
    }

    public function store(Request $request)
    {
        $cartItems = $this->selectedCartItems($request, withLock: true);

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index')->with('status', 'Your cart is empty.');
        }

        foreach ($cartItems as $item) {
            $availableStock = $item->variant?->stock_quantity ?? $item->product?->stock_quantity;

            if (!$item->product || $item->product->status !== 'active' || $availableStock < $item->quantity) {
                return back()->withErrors([
                    'stock' => "\"{$item->product?->name}\" no longer has enough stock. Please update your cart.",
                ]);
            }
        }

        $buyer = $request->user();

        DB::transaction(function () use ($cartItems, $buyer) {
            $bySeller = $cartItems->groupBy('product.seller_id');

            foreach ($bySeller as $sellerId => $items) {
                $orderTotal = $items->sum('line_total');

                $order = Order::create([
                    'seller_id' => $sellerId,
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                    'status' => Order::STATUS_PLACED,
                    'total_amount' => $orderTotal,
                    'commission_amount' => round($orderTotal * PlatformSetting::commissionRate(), 2),
                    'ship_province' => $buyer->buyerProfile?->province,
                    'ship_municipality' => $buyer->buyerProfile?->municipality,
                    'ship_barangay' => $buyer->buyerProfile?->barangay,
                    'ship_street' => $buyer->buyerProfile
                        ? trim(($buyer->buyerProfile->house_number ?? '') . ' ' . ($buyer->buyerProfile->street ?? ''))
                        : null,
                    'ship_contact_no' => $buyer->buyerProfile?->contact_no,
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
                        'subtotal' => $item->line_total,
                    ]);

                    if ($item->variant) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }
                }
            }

            // Only remove the items that were actually checked out.
            $cartItems->pluck('id')->chunk(500)->each(
                fn($chunk) => CartItem::whereIn('id', $chunk)->delete()
            );
        });

        return redirect()->route('buyer.orders.index')
            ->with('status', 'Order placed successfully! You can track its status here.');
    }

    /**
     * Cart items to check out. If an `items` param (comma-separated cart_item
     * IDs) is present, only those. Always scoped to the current buyer.
     * Falls back to the whole cart if no selection is given.
     */
    private function selectedCartItems(Request $request, bool $withLock = false)
    {
        $query = $request->user()->cartItems()->with('product.seller.sellerProfile', 'variant');

        if ($withLock) {
            $query->lockForUpdate();
        }

        if ($request->filled('items')) {
            $ids = is_array($request->items)
                ? $request->items
                : explode(',', $request->items);

            $query->whereIn('id', array_filter($ids, 'is_numeric'));
        }

        return $query->get();
    }
}
