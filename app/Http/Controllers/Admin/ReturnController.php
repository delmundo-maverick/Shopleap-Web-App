<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Services\ActivityLogger;
use App\Services\OrderReturns;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    private const TABS = [
        'disputes' => [OrderReturn::STATUS_DISPUTED],
        'refunds' => [OrderReturn::STATUS_REFUND_PENDING],
        'open' => [
            OrderReturn::STATUS_REQUESTED,
            OrderReturn::STATUS_APPROVED,
            OrderReturn::STATUS_SHIPPED_BACK,
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
        $tab = array_key_exists($request->get('tab'), self::TABS) ? $request->get('tab') : 'disputes';

        $counts = collect(self::TABS)
            ->map(fn ($statuses) => OrderReturn::whereIn('status', $statuses)->count())
            ->all();

        $query = OrderReturn::with(['order', 'seller.sellerProfile'])
            ->whereIn('status', self::TABS[$tab]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('order', fn ($order) => $order->matchingPublicCode($search))
                    ->orWhereHas('order', fn ($o) => $o->where('buyer_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('seller.sellerProfile', fn ($s) => $s->where('business_name', 'ilike', "%{$search}%"));
            });
        }

        // Cases waiting on an admin are shown oldest first, so nothing sits at the bottom.
        in_array($tab, ['disputes', 'refunds'], true) ? $query->oldest('updated_at') : $query->latest();

        $returns = $query->paginate(20)->withQueryString();

        return view('admin.returns.index', compact('returns', 'tab', 'counts'));
    }

    public function show(OrderReturn $orderReturn)
    {
        $orderReturn->load([
            'order.buyer.buyerProfile',
            'order.deliveryRider',
            'order.logisticsProfile',
            'order.events.actor',
            'seller.sellerProfile',
            'items.orderItem.product',
            'media',
            'events.actor',
        ]);

        return view('admin.returns.show', ['return' => $orderReturn]);
    }

    /** Settles a disputed case: approve (refund) or reject. */
    public function decide(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $approve = $data['decision'] === 'approve';

        if (! $returns->adminDecide($orderReturn, $approve, trim($data['note']), $request->user()->id)) {
            return back()->withErrors(['return' => 'This case was just updated. Please refresh the page.']);
        }

        ActivityLogger::log(
            $approve ? 'return.approved' : 'return.rejected',
            'Returns',
            "Return/refund #{$orderReturn->id} for order #{$orderReturn->order_id} ".($approve ? 'approved' : 'rejected').' after review.',
            $orderReturn
        );

        $message = ! $approve
            ? 'Case rejected. The buyer and seller can see your note.'
            : ($orderReturn->fresh()->status === OrderReturn::STATUS_APPROVED
                ? 'Case approved. The buyer will ship the item back first, then the refund is paid.'
                : 'Case approved. The refund is now under "Refunds to pay".');

        return redirect()->route('admin.returns.show', $orderReturn)->with('status', $message);
    }

    /** After sending the money by GCash, Maya or bank transfer. */
    public function markRefunded(Request $request, OrderReturn $orderReturn, OrderReturns $returns)
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:100']]);

        if (! $returns->markRefunded($orderReturn, trim($data['reference']), $request->user()->id)) {
            return back()->withErrors(['return' => 'This refund was just updated. Please refresh the page.']);
        }

        ActivityLogger::log(
            'return.refunded',
            'Returns',
            'Refund of ₱'.number_format($orderReturn->refund_amount, 2)." sent for return #{$orderReturn->id} (order #{$orderReturn->order_id}).",
            $orderReturn
        );

        return redirect()->route('admin.returns.show', $orderReturn)->with('status', 'Refund marked as sent.');
    }
}
