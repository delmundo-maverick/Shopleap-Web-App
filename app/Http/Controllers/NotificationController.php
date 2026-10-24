<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use LoadsCartSummary;

    /** The full list page ("View all"). */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->get('filter') === 'unread' ? 'unread' : 'all';

        $notifications = ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())
            ->paginate(20)
            ->withQueryString();

        $unread = $user->unreadNotifications()->count();

        // The buyer's page uses the shop header, which needs the cart summary.
        $extra = $user->role === 'buyer' ? $this->cartSummary($request) : [];

        return view('notifications.index', array_merge(compact('notifications', 'unread', 'filter'), $extra));
    }

    /** The bell's dropdown: the latest 15 and the unread count. */
    public function feed(Request $request)
    {
        $user = $request->user();

        $items = $user->notifications()
            ->limit(15)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'kind' => $n->data['kind'] ?? '',
                'title' => $n->data['title'] ?? 'Update',
                'body' => $n->data['body'] ?? '',
                'url' => $this->safeUrl($n->data['url'] ?? null),
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
            ])
            ->values();

        return response()
            ->json(['unread' => $user->unreadNotifications()->count(), 'items' => $items])
            ->header('Cache-Control', 'no-store');
    }

    /** Marks one notification read and goes to the page it points at. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $this->safeUrl($notification->data['url'] ?? null) ?? route('notifications.index', [], false);

        if ($request->expectsJson()) {
            return response()->json([
                'url' => $url,
                'unread' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return redirect($url);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['unread' => 0]);
        }

        return back();
    }

    /** Only paths inside this site, never an outside address. */
    private function safeUrl(mixed $url): ?string
    {
        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null;
    }
}
