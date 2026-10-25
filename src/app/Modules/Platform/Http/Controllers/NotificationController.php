<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's own notifications. Every lookup goes through
 * $request->user()->notifications(), so nobody can open someone else's.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('platform::notifications.index', compact('notifications'));
    }

    /** Mark one notification read and go to the page it is about. */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        // Only follow links into this application.
        $url = $item->data['url'] ?? route('notifications.index');

        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('notifications.index'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
