<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\CourierProfile;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Order;
use App\Models\User;
use App\Services\MessageAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /** People the rider may start a chat with. */
    public function contacts(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');

        return response()->json([
            'data' => $this->allowedContacts($courier)->map(fn($c) => [
                'id' => $c['user']->id,
                'name' => $c['user']->messaging_name,
                'role' => $c['user']->role,
                'reason' => $c['reason'],
            ])->values(),
        ]);
    }

    /** Find or create the chat with an allowed contact. */
    public function open(Request $request): JsonResponse
    {
        $courier = $request->attributes->get('courier');
        $data = $request->validate(['recipient_id' => ['required', 'integer', 'exists:users,id']]);

        $allowed = $this->allowedContacts($courier)->first(fn($c) => $c['user']->id === (int) $data['recipient_id']);

        abort_unless($allowed, 403, 'You can only message your sorting center, the seller or buyer of an active job, or support.');

        return response()->json(['conversation_id' => $this->findOrCreate($request->user(), $allowed['user'])->id]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->wherePivotNull('hidden_at')
            ->whereNotNull('last_message_at')
            ->with(['participants.sellerProfile', 'participants.logisticsProfile', 'participants.courierProfile'])
            ->orderByDesc('last_message_at')
            ->get();

        $ids = $conversations->pluck('id');

        $last = Message::whereIn('id', Message::selectRaw('MAX(id)')->whereIn('conversation_id', $ids)->groupBy('conversation_id'))
            ->get()->keyBy('conversation_id');

        $withAttachment = MessageAttachment::whereIn('message_id', $last->pluck('id'))
            ->get(['message_id', 'mime'])->groupBy('message_id');

        $unread = $this->unreadByConversation($user, $ids);

        return response()->json(['data' => $conversations->map(function (Conversation $c) use ($user, $last, $withAttachment, $unread) {
            $other = $c->otherParticipantFor($user->id);
            $m = $last->get($c->id);

            $preview = null;
            if ($m) {
                $preview = filled($m->body)
                    ? $m->body
                    : ($withAttachment->get($m->id)?->contains(fn($a) => str_starts_with((string) $a->mime, 'video/')) ? '🎥 Video' : '📷 Photo');
            }

            return [
                'conversation_id' => $c->id,
                'name' => $c->category
                    ? ($c->subject ?: 'Support ticket')
                    : ($other?->role === 'super_admin' ? 'Shopleap Support' : ($other?->messaging_name ?? 'Unknown')),
                'other_role' => $other?->role,
                'last_message' => $preview,
                'last_message_at' => $c->last_message_at?->toIso8601String(),
                'unread_count' => (int) ($unread[$c->id] ?? 0),
                'status' => $c->status,
            ];
        })->values()]);
    }

    /** Messages in a thread. The app passes `after` (the last id it has) when polling. */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        if ($request->filled('after')) {
            $rows = $conversation->messages()->with('sender')
                ->where('id', '>', (int) $request->query('after'))
                ->limit(200)->get();
        } else {
            $rows = $conversation->messages()->with('sender')
                ->reorder('id', 'desc')->limit(100)->get()->reverse()->values();
        }

        $attachments = $this->attachmentsFor($rows->pluck('id'));

        return response()->json([
            'messages' => $rows->map(fn($m) => $this->payload($m, $user, $attachments->get($m->id, collect())->all())),
            'status' => $conversation->status,
        ]);
    }

    /** Send a message, optionally with photos or videos (multipart, field `images[]`). */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $conversation);

        $request->validate(array_merge([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:images'],
        ], MessageAttachmentService::rules(allowVideo: true)));

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->input('body') ?? '',
        ]);

        if ($request->hasFile('images')) {
            MessageAttachmentService::store($message, $request->file('images'));
        }

        $conversation->update(['last_message_at' => now(), 'status' => 'open']);
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->update(['hidden_at' => null]);

        $message->setRelation('sender', $user);

        return response()->json(
            $this->payload($message, $user, $this->attachmentsFor([$message->id])->get($message->id, collect())->all()),
            201
        );
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);
        $conversation->participants()->updateExistingPivot($request->user()->id, ['hidden_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $ids = DB::table('conversation_participants')
            ->where('user_id', $user->id)->whereNull('hidden_at')->pluck('conversation_id');

        return response()->json(['count' => (int) $this->unreadByConversation($user, $ids)->sum()]);
    }

    // ── internals ──

    /** @return Collection<int, array{user: User, reason: string}> */
    private function allowedContacts(CourierProfile $courier): Collection
    {
        $out = collect();

        if ($hub = $courier->logisticsProfile?->user) {
            $out->push(['user' => $hub, 'reason' => 'Your sorting center']);
        }

        $sellerIds = Order::where('pickup_rider_id', $courier->id)
            ->where('status', Order::STATUS_READY_FOR_PICKUP)->pluck('seller_id')->unique();

        User::with('sellerProfile')->whereIn('id', $sellerIds)->get()
            ->each(fn($u) => $out->push(['user' => $u, 'reason' => 'Seller on an active pickup']));

        $buyerIds = Order::where('delivery_rider_id', $courier->id)
            ->whereIn('status', [Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])
            ->whereNotNull('buyer_id')->pluck('buyer_id')->unique();

        User::whereIn('id', $buyerIds)->get()
            ->each(fn($u) => $out->push(['user' => $u, 'reason' => 'Buyer on an active delivery']));

        if ($admin = User::where('role', 'super_admin')->where('account_status', 'active')->first()) {
            $out->push(['user' => $admin, 'reason' => 'Shopleap support']);
        }

        return $out->unique(fn($c) => $c['user']->id)->values();
    }

    private function findOrCreate(User $a, User $b): Conversation
    {
        $existing = Conversation::with('participants')
            ->whereNull('category')
            ->whereHas('participants', fn($q) => $q->where('users.id', $a->id))
            ->whereHas('participants', fn($q) => $q->where('users.id', $b->id))
            ->get()
            ->first(fn($c) => $c->participants->count() === 2);

        if ($existing) {
            return $existing;
        }

        $c = Conversation::create(['created_by' => $a->id]);
        $c->participants()->attach([$a->id, $b->id]);

        return $c;
    }

    private function authorizeParticipant(User $user, Conversation $conversation): void
    {
        abort_unless($conversation->participants()->where('users.id', $user->id)->exists(), 403);
    }

    /** One query for every thread's unread count. */
    private function unreadByConversation(User $user, $conversationIds): Collection
    {
        if (collect($conversationIds)->isEmpty()) {
            return collect();
        }

        return DB::table('messages')
            ->join('conversation_participants as cp', function ($j) use ($user) {
                $j->on('cp.conversation_id', '=', 'messages.conversation_id')->where('cp.user_id', $user->id);
            })
            ->whereIn('messages.conversation_id', $conversationIds)
            ->where('messages.sender_id', '!=', $user->id)
            ->where(fn($q) => $q->whereNull('cp.last_read_at')->orWhereColumn('messages.created_at', '>', 'cp.last_read_at'))
            ->groupBy('messages.conversation_id')
            ->selectRaw('messages.conversation_id, COUNT(*) as n')
            ->pluck('n', 'conversation_id');
    }

    /** Attachments with a ready-to-use URL, so the app never needs the web session route. */
    private function attachmentsFor($messageIds): Collection
    {
        return MessageAttachment::whereIn('message_id', $messageIds)->get()
            ->map(fn($a) => [
                'message_id' => $a->message_id,
                'id' => $a->id,
                'type' => str_starts_with((string) $a->mime, 'video/') ? 'video' : 'image',
                'name' => $a->original_name,
                'mime' => $a->mime,
                'size' => (int) $a->size,
                'url' => $this->signedUrl($a),
            ])
            ->groupBy('message_id');
    }

    private function signedUrl(MessageAttachment $a): ?string
    {
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk($a->disk);
            $config = config("filesystems.disks.{$a->disk}", []);

            if (($config['visibility'] ?? null) === 'public') {
                return $disk->url($a->path);
            }

            if (($config['driver'] ?? null) === 's3') {
                return $disk->temporaryUrl($a->path, now()->addMinutes(30));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function payload(Message $m, User $viewer, array $attachments): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender?->messaging_name,
            'is_mine' => $m->sender_id === $viewer->id,
            'created_at' => $m->created_at->toIso8601String(),
            'attachments' => $attachments,
        ];
    }
}
