<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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

    /**
     * What the bell needs to refresh itself (roadmap 1.11): the unread count,
     * the button's label, the newest unread notification (announced when it
     * is new) and the dropdown, rendered by the same partial as the page.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $unread = $user->unreadNotifications()->count();
        $newest = $unread ? $user->unreadNotifications()->first() : null;

        return response()->json([
            'unread' => $unread,
            'label'  => 'Notifications'.($unread ? ', '.$unread.' unread' : ''),
            'newest' => $newest ? [
                'id'    => $newest->id,
                'title' => $newest->data['title'] ?? 'Notification',
                'body'  => $newest->data['body'] ?? '',
            ] : null,
            'menu'   => view('platform::partials.bell-menu', ['unread' => $unread])->render(),
        ]);
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

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return $this->summary($request);
        }

        return back()->with('status', 'All notifications marked as read.');
    }
}
