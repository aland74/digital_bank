<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total'      => $user->notifications()->count(),
            'unread'     => $user->unreadNotificationsCount(),
            'actionable' => $user->notifications()
                ->where('type', 'transfer_request')
                ->where('is_read', false)
                ->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * AJAX endpoint: get unread notification count for polling.
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotificationsCount();
        $actionable = $request->user()->notifications()
            ->where('type', 'transfer_request')
            ->where('is_read', false)
            ->count();

        return response()->json([
            'count' => $count,
            'actionable' => $actionable,
        ]);
    }

    /**
     * AJAX endpoint: get latest notifications for dropdown.
     */
    public function latest(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'message' => \Str::limit($notif->message, 60),
                    'type' => $notif->type,
                    'type_icon' => $notif->type_icon,
                    'is_read' => $notif->is_read,
                    'requires_action' => $notif->requiresAction(),
                    'action_url' => $notif->action_url,
                    'time' => $notif->created_at->diffForHumans(),
                    'read_url' => route('notifications.read', $notif),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotificationsCount(),
        ]);
    }
}
