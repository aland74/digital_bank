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
}
