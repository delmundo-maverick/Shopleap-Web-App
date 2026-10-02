<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingPolicy;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    use LoadsCartSummary;

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return $this->adminIndex($request);
        }

        $conversations = $user->conversations()
            ->wherePivotNull('hidden_at')
            ->with([
                'participants.sellerProfile',
                'participants.logisticsProfile',
                'participants.courierProfile',
                'messages.sender',
                'product.images',
            ])
            ->orderByDesc('last_message_at')
            ->get();

        $activeConversation = null;
        if ($request->filled('conversation')) {
            $activeConversation = $conversations->firstWhere('id', (int) $request->conversation);
            if ($activeConversation) {
                $activeConversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
            }
        }

        $viewName = match ($user->role) {
            'buyer' => 'Buyer.messages.index',
            'seller' => 'seller.messages.index',
            'logistics' => 'logistics.messages.index',
            default => abort(403),
        };

        $extra = $user->role === 'buyer' ? $this->cartSummary($request) : [];

        return view($viewName, array_merge(compact('conversations', 'activeConversation'), $extra));
    }

    /**
     * Admin's shared support inbox — filterable by which role the other
     * participant is, and by open/resolved status. Any admin/staff account
     * can see and reply to any thread; "Claim" is a courtesy indicator,
     * not an access restriction, since this is a shared queue.
     */
    private function adminIndex(Request $request)
    {
        $user = $request->user();

        $role = $request->get('role', 'all');
        $view = $request->get('view', 'open');

        $base = $user->conversations()->wherePivotNull('hidden_at');

        $statusCounts = [
            'open' => (clone $base)->where('status', 'open')->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
        ];

        $roleCounts = ['all' => (clone $base)->where('status', $view)->count()];
        foreach (['buyer', 'seller', 'logistics'] as $r) {
            $roleCounts[$r] = (clone $base)->where('status', $view)
                ->whereHas('participants', fn($q) => $q->where('role', $r)->where('users.id', '!=', $user->id))
                ->count();
        }

        $query = $base->with([
            'participants.sellerProfile',
            'participants.logisticsProfile',
            'participants.courierProfile',
            'messages.sender',
            'product.images',
            'assignedAgent',
        ])
            ->where('status', $view)
            ->orderByDesc('last_message_at');

        if ($role !== 'all') {
            $query->whereHas('participants', fn($q) => $q->where('role', $role)->where('users.id', '!=', $user->id));
        }

        $conversations = $query->get();

        $activeConversation = null;
        if ($request->filled('conversation')) {
            $activeConversation = $conversations->firstWhere('id', (int) $request->conversation)
                ?? Conversation::with(['participants', 'messages.sender', 'product', 'assignedAgent'])->find($request->conversation);

            if ($activeConversation && $activeConversation->participants->contains('id', $user->id)) {
                $activeConversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
            }
        }

        return view('admin.messages.index', compact('conversations', 'activeConversation', 'role', 'view', 'statusCounts', 'roleCounts'));
    }

    /**
     * AJAX — search allowed contacts for the "New Message" picker.
     */
    public function contacts(Request $request)
    {
        $user = $request->user();
        $allowedRoles = MessagingPolicy::allowedRolesFor($user);

        $contacts = User::whereIn('role', $allowedRoles)
            ->where('id', '!=', $user->id)
            ->with(['sellerProfile', 'logisticsProfile', 'courierProfile'])
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->messaging_name,
                'role' => $u->role,
            ]);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $contacts = $contacts->filter(fn($c) => str_contains(strtolower($c['name']), $search))->values();
        }

        return response()->json($contacts);
    }

    /**
     * Start a new conversation (or reuse an existing one) with a first message.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $recipient = User::findOrFail($request->recipient_id);

        abort_unless(MessagingPolicy::canMessage($user, $recipient), 403, 'You are not permitted to message this user.');

        $conversation = $this->findOrCreateConversation($user, $recipient, $request->product_id);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->body,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        $this->unhideConversation($conversation);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    /**
     * AJAX — fetch messages newer than a given id, and mark the
     * conversation as read for the current user.
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $query = $conversation->messages()->with('sender');

        if ($request->filled('after')) {
            $query->where('id', '>', $request->after);
        }

        $messages = $query->get()->map(fn($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender->messaging_name,
            'created_at' => $m->created_at->format('M d, g:i A'),
            'is_forwarded' => (bool) $m->forwarded_from_message_id,
        ]);

        $conversation->loadMissing('product.images', 'assignedAgent');

        return response()->json([
            'messages' => $messages,
            'product' => $conversation->product ? [
                'id' => $conversation->product->id,
                'name' => $conversation->product->name,
                'image' => $conversation->product->main_image_url,
                'price' => (float) $conversation->product->display_price,
            ] : null,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'assigned_name' => $conversation->assignedAgent?->name,
        ]);
    }

    public function claim(Request $request, Conversation $conversation)
    {
        $this->authorizeAdmin($request);

        if ($conversation->assigned_to && $conversation->assigned_to !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Already assigned to another agent.'], 422);
        }

        $conversation->update(['assigned_to' => $request->user()->id]);

        return response()->json(['success' => true]);
    }

    public function unclaim(Request $request, Conversation $conversation)
    {
        $this->authorizeAdmin($request);

        if ($conversation->assigned_to !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this conversation.'], 422);
        }

        $conversation->update(['assigned_to' => null]);

        return response()->json(['success' => true]);
    }

    public function resolve(Request $request, Conversation $conversation)
    {
        $this->authorizeAdmin($request);
        $conversation->update(['status' => 'resolved']);

        return response()->json(['success' => true, 'status' => 'resolved']);
    }

    public function reopen(Request $request, Conversation $conversation)
    {
        $this->authorizeAdmin($request);
        $conversation->update(['status' => 'open']);

        return response()->json(['success' => true, 'status' => 'open']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'super_admin', 403);
    }

    /**
     * AJAX — post a message into an existing conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->body,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        $this->unhideConversation($conversation);

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $user->messaging_name,
            'created_at' => $message->created_at->format('M d, g:i A'),
            'is_forwarded' => false,
        ]);
    }

    /**
     * Admin-only — forward a message's content into a (new or existing)
     * conversation with a different user.
     */
    public function forward(Request $request, Message $message)
    {
        $user = $request->user();
        abort_unless($user->role === 'super_admin', 403, 'Only administrators can forward messages.');

        $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        abort_unless(MessagingPolicy::canMessage($user, $recipient), 403);

        $conversation = $this->findOrCreateConversation($user, $recipient);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $message->body,
            'forwarded_from_message_id' => $message->id,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    private function findOrCreateConversation(User $userA, User $userB, ?int $productId = null): Conversation
    {
        $candidates = Conversation::with('participants')
            ->whereHas('participants', fn($q) => $q->where('users.id', $userA->id))
            ->whereHas('participants', fn($q) => $q->where('users.id', $userB->id))
            ->get();

        $conversation = $candidates->first(fn($c) => $c->participants->count() === 2);

        if ($conversation) {
            if ($productId && $conversation->product_id !== $productId) {
                $conversation->update(['product_id' => $productId]);
            }
            return $conversation;
        }

        $conversation = Conversation::create([
            'product_id' => $productId,
            'created_by' => $userA->id,
        ]);

        $conversation->participants()->attach([$userA->id, $userB->id]);

        return $conversation;
    }

    private function authorizeParticipant(User $user, Conversation $conversation): void
    {
        abort_unless($conversation->participants()->where('users.id', $user->id)->exists(), 403);
    }

    /**
     * Buyer-only: find/create a conversation without requiring a first
     * message up front. Used by the homepage widget and the buyer's
     * curated contact picker, and by the "Message Seller" deep link.
     */
    public function open(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        abort_unless(MessagingPolicy::canMessage($user, $recipient), 403, 'You are not permitted to message this user.');

        $conversation = $this->findOrCreateConversation($user, $recipient, $request->product_id);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    /**
     * Buyer-only: distinct products the buyer has ordered, with each
     * product's seller, most recent first. Powers the curated contact
     * list that replaces a full seller directory search.
     */
    public function recentPurchases(Request $request)
    {
        $user = $request->user();

        $items = OrderItem::whereHas('order', fn($q) => $q->where('buyer_id', $user->id))
            ->with(['product.images', 'product.seller'])
            ->latest()
            ->get()
            ->filter(fn($item) => $item->product && $item->product->seller)
            ->unique('product_id')
            ->take(10)
            ->values();

        return response()->json($items->map(fn($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product->name,
            'product_image' => $item->product->main_image_url,
            'seller_id' => $item->product->seller_id,
            'seller_name' => $item->product->seller->messaging_name,
        ]));
    }

    /**
     * Buyer-only: the single admin contact used for the "Help" button,
     * replacing a searchable admin directory.
     */

    public function destroy(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, ['hidden_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function openSupport(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'buyer', 403);

        $admin = User::where('role', 'super_admin')->first();
        abort_unless($admin, 404, 'Support is currently unavailable.');

        $conversation = $this->findOrCreateConversation($user, $admin);
        $conversation->participants()->updateExistingPivot($user->id, ['hidden_at' => null]);

        return redirect()->route('messages.index', ['conversation' => $conversation->id]);
    }

    private function unhideConversation(Conversation $conversation): void
    {
        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->update(['hidden_at' => null]);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = $user->conversations()
            ->wherePivotNull('hidden_at')
            ->with('messages')
            ->get()
            ->sum(fn($c) => $c->unreadCountFor($user->id));

        return response()->json(['count' => $count]);
    }

    public function widgetData(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'buyer', 403);

        $conversations = $user->conversations()
            ->wherePivotNull('hidden_at')
            ->with(['participants', 'messages.sender', 'product'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conversation) use ($user) {
                $other = $conversation->otherParticipantFor($user->id);
                $lastMessage = $conversation->messages->last();

                return [
                    'conversation_id' => $conversation->id,
                    'other_id' => $other?->id,
                    'other_role' => $other?->role,
                    'name' => $other?->role === 'super_admin' ? 'Shopleap Support' : ($other?->messaging_name ?? 'Unknown'),
                    'last_message' => $lastMessage?->body,
                    'last_message_at' => $conversation->last_message_at?->diffForHumans(),
                    'unread_count' => $conversation->unreadCountFor($user->id),
                    'product' => $conversation->product ? [
                        'id' => $conversation->product->id,
                        'name' => $conversation->product->name,
                    ] : null,
                ];
            });

        $sellerIdsWithConversation = $conversations->pluck('other_id')->filter()->all();

        $quickStarts = [];

        $admin = User::where('role', 'super_admin')->first();
        if ($admin && !in_array($admin->id, $sellerIdsWithConversation, true)) {
            $quickStarts[] = [
                'recipient_id' => $admin->id,
                'product_id' => null,
                'name' => 'Contact Support',
                'subtitle' => 'Get help from Shopleap',
                'is_support' => true,
            ];
        }

        $items = OrderItem::whereHas('order', fn($q) => $q->where('buyer_id', $user->id))
            ->with(['product.images', 'product.seller'])
            ->latest()
            ->get()
            ->filter(fn($item) => $item->product && $item->product->seller)
            ->unique('product_id')
            ->take(10);

        foreach ($items as $item) {
            if (in_array($item->product->seller_id, $sellerIdsWithConversation, true)) {
                continue;
            }
            if (collect($quickStarts)->contains('recipient_id', $item->product->seller_id)) {
                continue;
            }

            $quickStarts[] = [
                'recipient_id' => $item->product->seller_id,
                'product_id' => $item->product_id,
                'name' => $item->product->seller->messaging_name,
                'subtitle' => "Re: {$item->product->name}",
                'is_support' => false,
            ];
        }

        return response()->json([
            'conversations' => $conversations->values(),
            'quick_starts' => $quickStarts,
        ]);
    }
}
