<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\MessageAttachmentService;
use App\Services\MessagingPolicy;
use App\Support\TicketPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
     * Admin's shared support inbox: filter by the other party's role, by
     * open/resolved, and by assignment (all / mine / unassigned tickets).
     */
    private function adminIndex(Request $request)
    {
        $user = $request->user();

        $role = $request->get('role', 'all');
        $view = $request->get('view', 'open');
        $assign = $request->get('assign', 'all');

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

        if ($assign === 'mine') {
            $query->where('assigned_to', $user->id);
        } elseif ($assign === 'unassigned') {
            $query->whereNotNull('category')->whereNull('assigned_to');
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

        return view('admin.messages.index', compact(
            'conversations',
            'activeConversation',
            'role',
            'view',
            'assign',
            'statusCounts',
            'roleCounts'
        ));
    }

    /** AJAX — search allowed contacts for the "New Message" picker. */
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

    /** Start a new conversation (or reuse an existing one) with a first message. */
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

    /** AJAX — messages newer than a given id; marks the thread read for the viewer. */
    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $query = $conversation->messages()->with('sender');

        if ($request->filled('after')) {
            $query->where('id', '>', $request->after);
        }

        $rows = $query->get();
        $attachments = MessageAttachmentService::forMessages($rows->pluck('id'));

        $messages = $rows->map(fn($m) => $this->messagePayload($m, $attachments[$m->id] ?? []));

        $conversation->loadMissing('product.images', 'assignedAgent');

        $ticket = null;
        if ($conversation->category) {
            $last = $conversation->messages()->with('sender')->reorder('id', 'desc')->first();
            [$label, $class] = TicketPresenter::state($conversation, $last, $user);

            $ticket = [
                'number' => TicketPresenter::number($conversation),
                'topic' => TicketPresenter::topic($conversation),
                'subject' => $conversation->subject,
                'order_id' => $conversation->order_id,
                'state' => ['label' => $label, 'class' => $class],
            ];
        }

        return response()->json([
            'messages' => $messages,
            'product' => $conversation->product ? [
                'id' => $conversation->product->id,
                'name' => $conversation->product->name,
                'image' => $conversation->product->main_image_url,
                'price' => (float) $conversation->product->display_price,
            ] : null,
            'ticket' => $ticket,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'assigned_name' => $conversation->assignedAgent?->name,
        ]);
    }

    /**
     * Admin-only: everything about a ticket/conversation in one payload
     * (ticket facts, people, order, product, original request, photos).
     */
    public function details(Request $request, Conversation $conversation)
    {
        $this->authorizeAdmin($request);
        $viewer = $request->user();

        $conversation->loadMissing([
            'participants',
            'creator',
            'assignedAgent',
            'product.images',
            'product.seller',
            'order.items',
            'order.seller',
            'order.events',
        ]);

        $messages = $conversation->messages()
            ->with('sender:id,role')
            ->get(['id', 'conversation_id', 'sender_id', 'body', 'created_at']);

        $first = $messages->first();
        $isStaff = fn($m) => $m->sender?->role === 'super_admin';
        $staffCount = $messages->filter($isStaff)->count();
        $firstReply = $first ? $messages->first(fn($m) => $isStaff($m) && $m->id > $first->id) : null;

        // ── ticket facts ──
        $ticket = null;
        if ($conversation->category) {
            $last = $conversation->messages()->with('sender')->reorder('id', 'desc')->first();
            [$label, $class] = TicketPresenter::state($conversation, $last, $viewer);

            $ticket = [
                'number' => TicketPresenter::number($conversation),
                'topic' => TicketPresenter::topic($conversation),
                'subject' => $conversation->subject,
                'state' => ['label' => $label, 'class' => $class],
            ];
        }

        // ── people (everyone except the admin looking at it) ──
        $people = $conversation->participants
            ->where('id', '!=', $viewer->id)
            ->values()
            ->map(fn($u) => $this->personPayload($u));

        $requester = $conversation->category && $conversation->creator
            ? $this->personPayload($conversation->creator)
            : null;

        // ── order ──
        $order = $conversation->order;
        $orderData = null;
        if ($order) {
            $ship = $order->ship_to;
            $orderData = [
                'id' => $order->id,
                'status' => $order->status_label,
                'placed_at' => $order->created_at?->format('M d, Y g:i A'),
                'total' => (float) $order->total_amount,
                'commission' => (float) $order->commission_amount,
                'seller' => $order->seller?->messaging_name,
                'buyer_name' => $order->buyer_name,
                'ship_to' => [
                    'name' => $ship['name'] ?? null,
                    'contact_no' => $ship['contact_no'] ?? null,
                    'address' => implode(', ', array_filter([
                        $ship['street'] ?? null,
                        $ship['barangay'] ?? null,
                        $ship['municipality'] ?? null,
                        $ship['province'] ?? null,
                    ])),
                ],
                'delivery_area' => $order->delivery_area,
                'delivery_attempts' => $order->delivery_attempts,
                'pickup_status' => $order->pickup_status,
                'pickup_date' => $order->pickup_date?->format('M d, Y'),
                'pickup_slot' => Order::PICKUP_SLOTS[$order->pickup_time_slot] ?? $order->pickup_time_slot,
                'pickup_decline_reason' => $order->pickup_decline_reason,
                'items' => $order->items->map(fn($i) => [
                    'name' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price_at_purchase,
                    'subtotal' => (float) $i->subtotal,
                    'options' => collect($i->selected_options ?? [])
                        ->map(fn($v, $k) => (is_int($k) ? '' : "{$k}: ") . (is_array($v) ? implode('/', $v) : $v))
                        ->implode(', '),
                ])->values(),
                'events' => $order->events->sortByDesc('id')->take(8)->map(fn($e) => [
                    'title' => $e->title,
                    'note' => $e->note,
                    'at' => $e->created_at?->format('M d, g:i A'),
                ])->values(),
            ];
        }

        // ── product ──
        $product = $conversation->product;
        $productData = $product ? [
            'name' => $product->name,
            'price' => (float) $product->display_price,
            'image' => $product->main_image_url,
            'seller' => $product->seller?->messaging_name,
        ] : null;

        // ── every photo in the thread ──
        $photos = collect(MessageAttachmentService::forMessages($messages->pluck('id')))
            ->flatten(1)
            ->values()
            ->all();

        return response()->json([
            'ticket' => $ticket,
            'facts' => [
                'opened_at' => $conversation->created_at?->format('M d, Y g:i A'),
                'opened_ago' => $conversation->created_at?->diffForHumans(),
                'last_activity' => $conversation->last_message_at?->diffForHumans(),
                'assigned_to' => $conversation->assignedAgent?->name,
                'status' => ucfirst($conversation->status ?? 'open'),
                'first_response' => $first && $firstReply
                    ? $firstReply->created_at->diffForHumans($first->created_at, true)
                    : null,
                'customer_messages' => $messages->count() - $staffCount,
                'staff_messages' => $staffCount,
                'photo_count' => count($photos),
            ],
            'requester' => $requester,
            'people' => $people,
            'order' => $orderData,
            'product' => $productData,
            'original_request' => $first?->body,
            'photos' => $photos,
        ]);
    }

    private function personPayload(User $u): array
    {
        $profile = match ($u->role) {
            'buyer' => $u->buyerProfile,
            'seller' => $u->sellerProfile,
            'logistics' => $u->logisticsProfile,
            default => null,
        };

        // getAttributes() avoids "missing attribute" errors on profiles that lack a column.
        $attr = fn($key) => $profile ? ($profile->getAttributes()[$key] ?? null) : null;

        return [
            'name' => $u->messaging_name,
            'role' => $u->role,
            'email' => $u->email,
            'contact' => $attr('contact_no') ?? $attr('phone'),
            'location' => implode(', ', array_filter([$attr('municipality'), $attr('province')])),
            'member_since' => $u->created_at?->format('M d, Y'),
        ];
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

    /** AJAX (multipart) — post a message, optionally with photos, into an existing conversation. */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $request->validate(array_merge([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:images'],
        ], MessageAttachmentService::rules()));

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->input('body') ?? '',
        ]);

        $files = $request->hasFile('images') ? $request->file('images') : [];
        if ($files) {
            MessageAttachmentService::store($message, $files);
        }

        $updates = ['last_message_at' => now()];
        if ($user->role !== 'super_admin') {
            // A customer reply reopens a resolved ticket.
            $updates['status'] = 'open';
        } elseif ($conversation->category && !$conversation->assigned_to) {
            // First admin reply on an unassigned ticket claims it.
            $updates['assigned_to'] = $user->id;
        }
        $conversation->update($updates);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        $this->unhideConversation($conversation);

        $message->setRelation('sender', $user);
        $attachments = MessageAttachmentService::forMessages([$message->id]);

        return response()->json($this->messagePayload($message, $attachments[$message->id] ?? []));
    }

    /** Streams an attachment, only to participants of its conversation. */
    public function attachment(Request $request, MessageAttachment $attachment)
    {
        $message = $attachment->message;
        abort_unless($message, 404);

        $conversation = Conversation::findOrFail($message->conversation_id);
        $this->authorizeParticipant($request->user(), $conversation);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Admin-only — forward a message's text into a (new or existing) conversation. */
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

    private function messagePayload(Message $m, array $attachments): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender?->messaging_name,
            'created_at' => $m->created_at->format('M d, g:i A'),
            'is_forwarded' => (bool) $m->forwarded_from_message_id,
            'attachments' => $attachments,
        ];
    }

    private function findOrCreateConversation(User $userA, User $userB, ?int $productId = null): Conversation
    {
        // Plain chats only. Tickets are never reused.
        $candidates = Conversation::with('participants')
            ->whereNull('category')
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
     * Buyer-only: find/create a chat without requiring a first message.
     * Used by the buyer's contact picker and the "Message Seller" deep link.
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

    /** Buyer-only: distinct products the buyer has ordered, with each product's seller. */
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
     * Buyer-only: data for the floating message widget (conversations + quick starts).
     */
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
                    'name' => $conversation->category
                        ? ($conversation->subject ?: 'Support ticket')
                        : ($other?->role === 'super_admin' ? 'Shopleap Support' : ($other?->messaging_name ?? 'Unknown')),
                    'last_message' => $lastMessage ? (filled($lastMessage->body) ? $lastMessage->body : '📷 Photo') : null,
                    'last_message_at' => $conversation->last_message_at?->diffForHumans(),
                    'unread_count' => $conversation->unreadCountFor($user->id),
                    'product' => $conversation->product ? [
                        'id' => $conversation->product->id,
                        'name' => $conversation->product->name,
                    ] : null,
                ];
            });

        $idsWithConversation = $conversations->pluck('other_id')->filter()->all();

        $quickStarts = [];

        $admin = User::where('role', 'super_admin')->first();
        if ($admin && !in_array($admin->id, $idsWithConversation, true)) {
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
            if (in_array($item->product->seller_id, $idsWithConversation, true)) {
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

    public function destroy(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, ['hidden_at' => now()]);

        return response()->json(['success' => true]);
    }

    /** Old "Help / Support" links now land on the Help Center. */
    public function openSupport(Request $request)
    {
        return redirect()->route('help.index');
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
}
