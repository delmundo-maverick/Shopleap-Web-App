<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Services\OrderReturns;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    private const TABS = [
        'needs_action' => [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_SHIPPED_BACK],
        'in_progress' => [
            OrderReturn::STATUS_APPROVED,
            OrderReturn::STATUS_REFUND_PENDING,
            OrderReturn::STATUS_DISPUTED,
            OrderReturn::STATUS_DECLINED,
        ],
        'closed' => [
            OrderReturn::STATUS_REFUNDED,
            OrderReturn::STATUS_REJECTED,
            OrderReturn::STATUS_CANCELLED,
            OrderReturn::STATUS_EXPIRED,
        ],
    ];

    public function index(Request $request)
    {
        $sellerId = $request->user()->id;
        $tab = array_key_exists($request->get('tab'), self::TABS) ? $request->get('tab') : 'needs_action';

        $counts = collect(self::TABS)
            ->map(fn($statuses) => OrderReturn::where('seller_id', $sellerId)->whereIn('status', $statuses)->count())
            ->all();

        $returns = OrderReturn::with(['order', 'items'])
            ->where('seller_id', $sellerId)
            ->whereIn('status', self::TABS[$tab])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('seller.returns.index', compact('returns', 'tab', 'counts'));
    }

    public function show(Request $request, OrderReturn $orderReturn)
    {
        $this->authorizeOwnership($request, $orderReturn);

        $orderReturn->load(['order', 'items.orderItem.product', 'media', 'events']);

        return view('seller.returns.show', ['return' => $orderReturn]);
    }

    public function approve(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $this->authorizeOwnership($request, $orderReturn);

        if ($returns->approve($orderReturn, $request->user()->id)) {
            return back()->with('status', $orderReturn->type === OrderReturn::TYPE_REFUND_ONLY
                ? 'Approved. Shopleap will send the refund to the buyer.'
                : 'Approved. The buyer can now ship the item back to you.');
        }

        return back()->withErrors(['return' => 'This request was just updated. Please refresh the page.']);
    }

    public function decline(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $this->authorizeOwnership($request, $orderReturn);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        if ($returns->decline($orderReturn, trim($data['reason']), $request->user()->id)) {
            return back()->with('status', 'Request declined. The buyer can ask Shopleap to review your decision.');
        }

        return back()->withErrors(['return' => 'This request was just updated. Please refresh the page.']);
    }

    public function received(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $this->authorizeOwnership($request, $orderReturn);

        if ($returns->confirmReceived($orderReturn, $request->user()->id)) {
            return back()->with('status', 'Receipt confirmed. Shopleap will send the refund to the buyer.');
        }

        return back()->withErrors(['return' => 'This request was just updated. Please refresh the page.']);
    }

    public function problem(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $this->authorizeOwnership($request, $orderReturn);

        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);

        if ($returns->reportProblem($orderReturn, trim($data['reason']), $request->user()->id)) {
            return back()->with('status', 'Reported. Shopleap will review the case.');
        }

        return back()->withErrors(['return' => 'This request was just updated. Please refresh the page.']);
    }

    private function authorizeOwnership(Request $request, OrderReturn $return): void
    {
        abort_unless($return->seller_id === $request->user()->id, 403, 'You do not have access to this request.');
    }
}
