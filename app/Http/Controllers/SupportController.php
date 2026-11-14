<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Services\MessageAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Help Center for buyers, sellers and logistics centers.
 * A ticket is a conversation with the support admin, marked by `category`.
 */
class SupportController extends Controller
{
    use LoadsCartSummary;

    private const ROLES = ['buyer', 'seller', 'logistics'];

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeRole($user);

        $topics = $this->topicsFor($user->role);

        $orders = $this->orderQuery($user)
            ->with('items')
            ->latest()
            ->take(15)
            ->get()
            ->map(function (Order $order) {
                $first = $order->items->first();
                $extra = $order->items->count() - 1;

                return [
                    'id' => $order->order_number,
                    'label' => "Order #{$order->order_number} · ".Str::limit($first?->product_name ?? 'Order', 40)
                        .($extra > 0 ? " +{$extra} more" : '')
                        ." · {$order->status_label} · ".$order->created_at->format('M d, Y'),
                ];
            })
            ->values();

        $tickets = $user->conversations()
            ->wherePivotNull('hidden_at')
            ->whereNotNull('category')
            ->with('messages.sender')
            ->orderByDesc('last_message_at')
            ->get();

        $recentTickets = $tickets->take(3);
        $unreadTickets = $tickets->filter(fn ($c) => $c->unreadCountFor($user->id) > 0)->count();

        $view = match ($user->role) {
            'buyer' => 'Buyer.help.index',
            'seller' => 'seller.help.index',
            'logistics' => 'logistics.help.index',
        };

        $extra = $user->role === 'buyer' ? $this->cartSummary($request) : [];

        return view($view, array_merge(
            compact('topics', 'orders', 'recentTickets', 'unreadTickets'),
            ['role' => $user->role],
            $extra
        ));
    }

    /** AJAX (multipart) — create a ticket from the guided form. */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeRole($user);

        $topics = $this->topicsFor($user->role);

        $data = $request->validate(array_merge([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys($topics))],
            'issue' => ['required', 'string', 'max:120'],
            'follow_up' => ['nullable', 'in:yes,no'],
            'order_id' => ['nullable', 'string', 'max:32'],
            'details' => ['required', 'string', 'min:10', 'max:1500'],
        ], MessageAttachmentService::rules()));

        $topic = $topics[$data['category']];

        $issue = collect($topic['issues'])->firstWhere('label', $data['issue']);
        abort_unless($issue, 422, 'Please choose one of the listed issues.');

        $orderId = $topic['order'] === 'none' ? null : ($data['order_id'] ?? null);
        if ($topic['order'] === 'required' && ! $orderId) {
            abort(422, 'Please select the order this is about.');
        }

        $order = null;
        if ($orderId) {
            // Ownership check: the order must belong to this user's role scope.
            $order = $this->orderQuery($user)->with('items')->matchingPublicCode($orderId)->first();
            abort_unless($order, 422, 'We could not find that order.');
        }

        $admin = User::where('role', 'super_admin')->first();
        abort_unless($admin, 503, 'Support is currently unavailable.');

        $lines = ["Topic: {$topic['label']}", "Issue: {$issue['label']}"];
        if (! empty($issue['ask']) && ! empty($data['follow_up'])) {
            $lines[] = $issue['ask'].' '.ucfirst($data['follow_up']);
        }
        if ($order) {
            $lines[] = "Order: #{$order->order_number} — {$order->items->first()?->product_name}";
        }
        $lines[] = '';
        $lines[] = 'Details:';
        $lines[] = $data['details'];

        $conversation = DB::transaction(function () use ($user, $admin, $data, $order, $lines, $request) {
            $conversation = new Conversation;
            $conversation->forceFill([
                'category' => $data['category'],
                'subject' => $data['issue'],
                'order_id' => $order?->id,
                'product_id' => $order?->items->first()?->product_id,
                'created_by' => $user->id,
                'status' => 'open',
                'last_message_at' => now(),
            ])->save();

            $conversation->participants()->attach([$user->id, $admin->id]);

            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => implode("\n", $lines),
            ]);

            if ($request->hasFile('images')) {
                MessageAttachmentService::store($message, $request->file('images'));
            }

            $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

            return $conversation;
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'ticket_number' => sprintf('SL-%06d', $conversation->id),
        ]);
    }

    private function authorizeRole(User $user): void
    {
        abort_unless(in_array($user->role, self::ROLES, true), 403);
    }

    private function topicsFor(string $role): array
    {
        return collect(config('support.topics'))
            ->filter(fn ($t) => $t['role'] === $role)
            ->all();
    }

    /** Orders this user may raise a ticket about. */
    private function orderQuery(User $user)
    {
        return match ($user->role) {
            'buyer' => Order::where('buyer_id', $user->id),
            'seller' => Order::where('seller_id', $user->id),
            'logistics' => Order::where('logistics_profile_id', $user->logisticsProfile?->id ?? 0),
        };
    }
}
