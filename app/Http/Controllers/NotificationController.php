<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Buyer\Concerns\LoadsCartSummary;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use LoadsCartSummary;

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

    /** Marks one notification read and goes to the page it points at. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only paths inside this site, never an outside address.
        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return redirect($url);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
