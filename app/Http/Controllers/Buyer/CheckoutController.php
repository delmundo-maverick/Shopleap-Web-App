<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $request->user()->cartItems()
            ->with('product.seller.sellerProfile', 'variant')
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index');
        }

        $groupedBySeller = $cartItems->groupBy('product.seller_id');

        $total = $cartItems->sum('line_total');

        return view('Buyer.checkout.index', compact('groupedBySeller', 'total'));
    }

    public function store(Request $request)
    {
        $cartItems = $request->user()->cartItems()->with('product', 'variant')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index')->with('status', 'Your cart is empty.');
        }

        // Verify stock is still available for everything before committing to anything
        foreach ($cartItems as $item) {
            $availableStock = $item->variant?->stock_quantity ?? $item->product?->stock_quantity;

            if (!$item->product || $item->product->status !== 'active' || $availableStock < $item->quantity) {
                return back()->withErrors([
                    'stock' => "\"{$item->product?->name}\" no longer has enough stock. Please update your cart.",
                ]);
            }
        }

        $buyer = $request->user();
        $createdOrderIds = [];

        DB::transaction(function () use ($cartItems, $buyer, &$createdOrderIds) {
            $bySeller = $cartItems->groupBy('product.seller_id');

            foreach ($bySeller as $sellerId => $items) {
                $orderTotal = $items->sum('line_total');

                $profile = $buyer->buyerProfile;

                $order = Order::create([
                    'seller_id' => $sellerId,
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                    'status' => Order::STATUS_PLACED,
                    'total_amount' => $orderTotal,
                    'commission_amount' => round($orderTotal * 0.10, 2),
                    'ship_province' => $profile?->province,
                    'ship_municipality' => $profile?->municipality,
                    'ship_barangay' => $profile?->barangay,
                    'ship_street' => $profile ? trim(($profile->house_number ?? '') . ' ' . ($profile->street ?? '')) : null,
                    'ship_contact_no' => $profile?->contact_no,
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

                $createdOrderIds[] = $order->id;
            }

            $buyer->cartItems()->delete();
        });

        return redirect()->route('buyer.orders.index')
            ->with('status', 'Order placed successfully! You can track its status here.');
    }
}
