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
            ->with('product.seller.sellerProfile')
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index');
        }

        // Group by seller so the buyer sees exactly how their order will be split
        $groupedBySeller = $cartItems->groupBy('product.seller_id');

        $total = $cartItems->sum('line_total');

        return view('Buyer.checkout.index', compact('groupedBySeller', 'total'));
    }

    public function store(Request $request)
    {
        $cartItems = $request->user()->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('buyer.cart.index')->with('status', 'Your cart is empty.');
        }

        // Verify stock is still available for everything before committing to anything
        foreach ($cartItems as $item) {
            if (!$item->product || $item->product->status !== 'active' || $item->product->stock_quantity < $item->quantity) {
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

                $order = Order::create([
                    'seller_id' => $sellerId,
                    'buyer_id' => $buyer->id,
                    'buyer_name' => $buyer->name,
                    'status' => 'pending',
                    'total_amount' => $orderTotal,
                    'commission_amount' => round($orderTotal * 0.10, 2),
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->product->display_price,
                        'subtotal' => $item->line_total,
                    ]);

                    $item->product->decrement('stock_quantity', $item->quantity);
                }

                $createdOrderIds[] = $order->id;
            }

            // Empty the cart only after every order successfully posted
            $buyer->cartItems()->delete();
        });

        return redirect()->route('buyer.orders.index')
            ->with('status', 'Order placed successfully! You can track its status here.');
    }
}
