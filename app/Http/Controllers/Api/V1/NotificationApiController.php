<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request) {
        return response()->json($request->user()->notifications()->orderBy('created_at','desc')->paginate(20));
    }
    public function markAsRead(Request $request, Notification $notification) {
        if ($notification->user_id !== $request->user()->id) abort(403);
        $notification->markAsRead();
        return response()->json(['message'=>'Marked as read.']);
    }
    public function markAllAsRead(Request $request) {
        $request->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['message'=>'All notifications marked as read.']);
    }
    public function unreadCount(Request $request) {
        $count = $request->user()->unreadNotificationsCount();
        return response()->json([
            'count' => $count,
        ]);
    }
    public function latest(Request $request) {
        $notifications = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'icon' => $notif->icon,
                    'action_url' => $notif->action_url,
                    'is_read' => $notif->is_read,
                    'read_at' => $notif->read_at ? $notif->read_at->toISOString() : null,
                    'created_at' => $notif->created_at->toISOString(),
                ];
            });
        return response()->json($notifications);
    }
}
