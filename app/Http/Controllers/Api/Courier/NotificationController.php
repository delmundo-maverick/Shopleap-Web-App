<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Support\OrderCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadOnly = $request->query('filter') === 'unread';

        $page = ($unreadOnly ? $user->unreadNotifications() : $user->notifications())->paginate(20);

        OrderCodes::presentNotifications($page->getCollection());

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'data' => $page->getCollection()->map(fn ($n) => [
                'id' => $n->id,
                'kind' => $n->data['kind'] ?? '',
                'title' => $n->data['title'] ?? 'Update',
                'body' => $n->data['body'] ?? '',
                'url' => $n->data['url'] ?? null,
                'meta' => $n->data['meta'] ?? [],      // order_id etc., for opening the right screen
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['unread' => 0]);
    }
}
