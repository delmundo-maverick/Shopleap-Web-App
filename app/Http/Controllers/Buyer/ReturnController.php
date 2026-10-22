<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\OrderReturns;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    use LoadsCartSummary;

    public function create(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $order->load(['items.product', 'seller.sellerProfile', 'returns']);

        if (! $order->canRequestReturn()) {
            return redirect()->route('buyer.orders.index')
                ->withErrors(['return' => $this->unavailableMessage($order)]);
        }

        $reasons = collect(OrderReturn::REASONS);

        return view('Buyer.returns.create', array_merge(compact('order', 'reasons'), $this->cartSummary($request)));
    }

    public function store(Request $request, Order $order, OrderReturns $returns)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(OrderReturn::REASONS))],
            'details' => ['required', 'string', 'min:10', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'refund_method' => ['required', Rule::in(array_keys(OrderReturn::REFUND_METHODS))],
            'refund_account_name' => ['required', 'string', 'max:150'],
            'refund_account_number' => ['required', 'string', 'max:60', 'regex:/^[0-9 \-]+$/'],
            'media' => ['nullable', 'array', 'max:' . OrderReturn::MAX_MEDIA],
            'media.*' => [
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
                'max:20480', // KB
            ],
        ], [
            'refund_account_number.regex' => 'The account number may only contain digits, spaces and dashes.',
        ]);

        $files = $request->file('media', []);

        if (OrderReturn::REASONS[$data['reason']]['evidence'] && count($files) === 0) {
            return back()->withInput()->withErrors(['media' => 'Please add at least one photo or video of the problem.']);
        }

        $orderItems = $order->items()->whereIn('id', $data['items'])->get();

        if ($orderItems->count() !== count(array_unique($data['items']))) {
            return back()->withInput()->withErrors(['items' => 'Please choose items from this order.']);
        }

        try {
            $return = $returns->open($order, $request->user()->id, $data, $orderItems, $files);
        } catch (\DomainException $e) {
            return redirect()->route('buyer.orders.index')->withErrors(['return' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['return' => "We couldn't send your request. Please try again."]);
        }

        $message = $return->status === OrderReturn::STATUS_DISPUTED
            ? 'Your request was sent to Shopleap, who will check the delivery. You can follow it here.'
            : 'Your request was sent to the seller. They have ' . OrderReturn::RESPONSE_DAYS . ' days to respond.';

        return redirect()->route('buyer.returns.show', $return)->with('status', $message);
    }

    public function show(Request $request, OrderReturn $orderReturn)
    {
        abort_unless($orderReturn->buyer_id === $request->user()->id, 403);

        $orderReturn->load(['order.seller.sellerProfile', 'items.orderItem.product', 'media', 'events']);

        return view('Buyer.returns.show', array_merge(['return' => $orderReturn], $this->cartSummary($request)));
    }

    public function withdraw(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        abort_unless($orderReturn->buyer_id === $request->user()->id, 403);

        if ($returns->withdraw($orderReturn, $request->user()->id)) {
            return back()->with('status', 'Your request was withdrawn.');
        }

        return back()->withErrors(['return' => "This request can't be withdrawn anymore."]);
    }

    public function ship(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        abort_unless($orderReturn->buyer_id === $request->user()->id, 403);

        $data = $request->validate([
            'courier' => ['required', Rule::in(OrderReturn::RETURN_COURIERS)],
            'tracking_no' => ['required', 'string', 'max:100'],
        ]);

        if ($returns->ship($orderReturn, $data['courier'], trim($data['tracking_no']), $request->user()->id)) {
            return back()->with('status', 'Thanks. The seller will confirm when the item arrives.');
        }

        return back()->withErrors(['return' => "The item can't be marked as shipped right now. Please refresh the page."]);
    }

    public function escalate(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        abort_unless($orderReturn->buyer_id === $request->user()->id, 403);

        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);

        if ($returns->escalateByBuyer($orderReturn, trim($data['reason']), $request->user()->id)) {
            return back()->with('status', 'Shopleap will review the seller\'s decision. We\'ll update this page.');
        }

        return back()->withErrors(['return' => 'The time to ask for a review has ended, or this request already moved on.']);
    }

    private function unavailableMessage(Order $order): string
    {
        if (! in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true) || ! $order->delivered_at) {
            return 'A return or refund can be requested once your parcel is delivered.';
        }

        if (now()->greaterThan($order->returnWindowEndsAt())) {
            return 'The ' . Order::returnWindowDays() . '-day return window for this order has ended.';
        }

        return 'You already have a return/refund request for this order.';
    }
}
