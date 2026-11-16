<?php

namespace App\Http\Controllers;

use App\Models\OrderReturn;
use App\Models\User;
use App\Services\HubContext;
use App\Services\Payouts;
use App\Services\SellerSettlements;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerSettlementController extends Controller
{
    public function index(Request $request, SellerSettlements $settlements): View
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['seller', 'logistics', 'super_admin'], true), 403);
        $hubId = $role === 'logistics' ? HubContext::require($request)->id : null;
        $sellerId = $role === 'seller' ? $request->user()->id : null;
        $filters = $request->validate(['status' => ['nullable', Rule::in(['all', 'pending', 'sent', 'paid', 'disputed', 'cancelled', 'overdue'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $status = $filters['status'] ?? 'all';
        $hasLegacyPayouts = $role === 'super_admin' && $settlements->hasLegacyPlatformPayouts();
        $scope = function (Builder $query) use ($hubId, $sellerId): void {
            $query->when($hubId !== null, fn (Builder $q) => $q->where('s.hub_id', $hubId))
                ->when($sellerId !== null, fn (Builder $q) => $q->where('s.seller_id', $sellerId));
        };
        $history = DB::table('seller_settlements as s')->join('users as u', 'u.id', '=', 's.seller_id')->join('logistics_profiles as h', 'h.id', '=', 's.hub_id');
        $scope($history);
        if ($status === 'overdue') {
            $history->whereIn('s.status', ['pending', 'sent', 'disputed'])->where('s.due_at', '<', now());
        } elseif ($status !== 'all') {
            $history->where('s.status', $status);
        }
        $rows = $history->select('s.*', 'u.name as seller_name', 'h.business_name as hub_name')->orderByDesc('s.id')->paginate(20)->withQueryString();
        $eligible = $settlements->eligible($hubId, $sellerId)->orderBy('completion.completed_at')->paginate(25, ['*'], 'eligible_page')->withQueryString();
        $methods = DB::table('seller_payout_methods')->whereIn('seller_id', $eligible->getCollection()->pluck('seller_id'))->get()->groupBy('seller_id');
        $items = DB::table('seller_settlement_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
            ->whereIn('i.settlement_id', $rows->getCollection()->pluck('id'))->select('i.*', 'o.order_number')->get()->groupBy('settlement_id');
        $events = DB::table('seller_settlement_events as e')->join('users as u', 'u.id', '=', 'e.actor_id')
            ->whereIn('e.settlement_id', $rows->getCollection()->pluck('id'))->select('e.*', 'u.name as actor_name')->orderBy('e.id')->get()->groupBy('settlement_id');
        $recoveries = DB::table('seller_settlement_orders as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('users as u', 'u.id', '=', 's.seller_id')->joinSub($settlements->orderBalances('seller', 'hub_receivable'), 'claim', 'claim.order_id', '=', 'o.id');
        $scope($recoveries);
        $recoveries = $recoveries->where('claim.amount', '<', 0)->select('s.*', 'o.order_number', 'u.name as seller_name', 'claim.amount')->paginate(20, ['*'], 'recoveries_page')->withQueryString();
        $credits = DB::table('seller_settlement_orders as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->joinSub($settlements->orderBalances('hub', 'cod_to_remit'), 'credit', 'credit.order_id', '=', 'o.id');
        $scope($credits);
        $credits = $credits->where('credit.amount', '<', 0)->select('s.*', 'o.order_number', 'credit.amount')->paginate(20, ['*'], 'credits_page')->withQueryString();
        $refunds = DB::table('refund_payouts as p')->join('seller_settlement_orders as s', 's.order_id', '=', 'p.order_id')
            ->join('orders as o', 'o.id', '=', 's.order_id')->join('order_returns as r', 'r.id', '=', 'p.order_return_id');
        $scope($refunds);
        $refunds = $refunds->where('p.status', 'pending')->where('r.status', OrderReturn::STATUS_REFUND_PENDING)
            ->select('p.*', 'o.order_number', 'r.refund_method', 'r.refund_account_name', 'r.refund_account_number')->paginate(20, ['*'], 'refunds_page')->withQueryString();
        $corrections = DB::table('seller_settlement_events as e')->join('seller_settlement_orders as s', 's.order_id', '=', 'e.order_id')
            ->join('orders as o', 'o.id', '=', 's.order_id')->join('users as u', 'u.id', '=', 'e.actor_id');
        $scope($corrections);
        $corrections = $corrections->select('e.*', 'o.order_number', 'u.name as actor_name')->orderByDesc('e.id')->paginate(20, ['*'], 'corrections_page')->withQueryString();

        return view('money.settlements', compact('role', 'rows', 'eligible', 'methods', 'items', 'events', 'status', 'settlements', 'recoveries', 'credits', 'refunds', 'corrections', 'hasLegacyPayouts'));
    }

    public function store(Request $request, SellerSettlements $settlements): RedirectResponse
    {
        $data = $request->validate(['seller_id' => ['required', 'integer'], 'method_id' => ['required', 'integer'],
            'orders' => ['required', 'array', 'min:1', 'max:500'], 'orders.*' => ['required', 'string', 'distinct', 'max:40'], 'request_key' => ['required', 'uuid']]);
        $record = $settlements->create($request->user(), $data['seller_id'], $data['method_id'], $data['orders'], $data['request_key']);

        return back()->with('status', 'Seller payment prepared · '.$record->reference_no);
    }

    public function send(Request $request, string $reference, SellerSettlements $settlements): RedirectResponse
    {
        $data = $request->validate(['transfer_reference' => ['required', 'string', 'max:150'], 'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $path = $request->file('proof')->store('seller-settlements', 'r2');
        abort_unless(is_string($path) && $path !== '', 503, 'Proof upload failed.');
        try {
            $record = $settlements->send($request->user(), $reference, $data['transfer_reference'], $path);
            if ($record->proof_path !== $path) {
                Storage::disk('r2')->delete($path);
            }
        } catch (\Throwable $exception) {
            Storage::disk('r2')->delete($path);
            throw $exception;
        }

        return back()->with('status', 'Transfer recorded. The seller must confirm receipt.');
    }

    public function confirm(Request $request, string $reference, SellerSettlements $settlements): RedirectResponse
    {
        $settlements->confirm($request->user(), $reference);

        return back()->with('status', 'Payment receipt confirmed.');
    }

    public function dispute(Request $request, string $reference, SellerSettlements $settlements): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']])['reason'];
        $settlements->dispute($request->user(), $reference, $reason);

        return back()->with('status', 'Dispute opened for admin review.');
    }

    public function cancel(Request $request, string $reference, SellerSettlements $settlements): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']])['reason'];
        $settlements->cancel($request->user(), $reference, $reason);

        return back()->with('status', 'Unsent payment cancelled; eligible funds released.');
    }

    public function resolve(Request $request, string $reference, SellerSettlements $settlements): RedirectResponse
    {
        $data = $request->validate(['outcome' => ['required', Rule::in(['paid', 'returned'])], 'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $path = $request->file('proof')->store('seller-settlements', 'r2');
        abort_unless(is_string($path) && $path !== '', 503, 'Proof upload failed.');
        try {
            $settlements->resolve($request->user(), $reference, $data['outcome'], $data['reason'], $path);
        } catch (\Throwable $exception) {
            Storage::disk('r2')->delete($path);
            throw $exception;
        }

        return back()->with('status', 'Dispute resolution recorded.');
    }

    public function recovery(Request $request, int $order, SellerSettlements $settlements): RedirectResponse
    {
        return $this->recordEvidence($request, function (string $reference, string $path) use ($request, $order, $settlements): void {
            $settlements->recover($request->user(), $order, $reference, $path);
        }, 'Seller recovery received and reconciled.');
    }

    public function commissionReturn(Request $request, int $order, SellerSettlements $settlements): RedirectResponse
    {
        return $this->recordEvidence($request, function (string $reference, string $path) use ($request, $order, $settlements): void {
            $settlements->returnCommission($request->user(), $order, $reference, $path);
        }, 'Commission refund returned to logistics.');
    }

    public function refund(Request $request, string $reference, Payouts $payouts): RedirectResponse
    {
        return $this->recordEvidence($request, function (string $transferReference, string $path) use ($request, $reference, $payouts): void {
            $payouts->payRefund($reference, $request->user()->id, HubContext::require($request)->id, $transferReference, $path);
        }, 'Buyer refund recorded.');
    }

    public function proof(Request $request, string $reference): StreamedResponse
    {
        $record = DB::table('seller_settlements')->where('reference_no', $reference)->firstOrFail();
        $this->authorizeRecord($request->user(), $record);
        $eventId = $request->validate(['event' => ['nullable', 'integer']])['event'] ?? null;
        $path = $eventId ? DB::table('seller_settlement_events')->where('id', $eventId)->where('settlement_id', $record->id)->value('proof_path') : $record->proof_path;
        abort_unless(is_string($path) && Storage::disk('r2')->exists($path), 404);

        return Storage::disk('r2')->download($path, basename($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function orderProof(Request $request, int $event): StreamedResponse
    {
        $record = DB::table('seller_settlement_events as e')->join('seller_settlement_orders as s', 's.order_id', '=', 'e.order_id')
            ->where('e.id', $event)->select('e.*', 's.hub_id', 's.seller_id')->firstOrFail();
        $this->authorizeRecord($request->user(), $record);
        abort_unless($record->proof_path && Storage::disk('r2')->exists($record->proof_path), 404);

        return Storage::disk('r2')->download($record->proof_path, basename($record->proof_path), ['Cache-Control' => 'private, no-store']);
    }

    private function authorizeRecord(User $user, object $record): void
    {
        abort_unless($user->role === 'super_admin' || ($user->role === 'seller' && $user->id === (int) $record->seller_id)
            || ($user->role === 'logistics' && $user->logisticsProfile?->id === (int) $record->hub_id), 403);
    }

    private function recordEvidence(Request $request, \Closure $action, string $message): RedirectResponse
    {
        $data = $request->validate(['transfer_reference' => ['required', 'string', 'max:150'], 'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $path = $request->file('proof')->store('seller-settlements', 'r2');
        abort_unless(is_string($path) && $path !== '', 503, 'Proof upload failed.');
        try {
            $action($data['transfer_reference'], $path);
            if (! DB::table('seller_settlement_events')->where('proof_path', $path)->exists()
                && ! DB::table('refund_payouts')->where('proof_path', $path)->exists()) {
                Storage::disk('r2')->delete($path);
            }
        } catch (\Throwable $exception) {
            Storage::disk('r2')->delete($path);
            throw $exception;
        }

        return back()->with('status', $message);
    }
}
