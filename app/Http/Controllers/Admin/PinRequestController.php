<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\PinChangeRequest;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class PinRequestController extends Controller
{
    public function index()
    {
        $pendingRequests = PinChangeRequest::pending()
            ->with(['user', 'card'])
            ->orderBy('created_at')
            ->get();

        $processedRequests = PinChangeRequest::processed()
            ->with(['user', 'card', 'processedBy'])
            ->orderBy('processed_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.pin-requests', compact('pendingRequests', 'processedRequests'));
    }

    public function approve(Request $request, PinChangeRequest $pinRequest)
    {
        if (!$pinRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $card = $pinRequest->card;

        // Generate new PIN
        $pinData = Card::generatePin();
        $card->changePin($pinData['plain']);

        // If card was frozen due to stolen PIN, unfreeze it
        if ($card->status === 'frozen') {
            $card->update(['status' => 'active', 'pin_attempts' => 0]);
        }

        $pinRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        // Notify user with the new PIN
        Notification::create([
            'user_id' => $pinRequest->user_id,
            'title' => 'PIN Changed Successfully! 🔐',
            'message' => "Your PIN change request for card ending in {$card->card_number_last4} has been approved. Your new PIN is: {$pinData['plain']}. Please memorize it and do not share it with anyone.",
            'type' => 'security',
            'icon' => '🔐',
            'action_url' => route('cards.index'),
            'data' => ['action' => 'pin_changed', 'card_id' => $card->id],
        ]);

        AuditLog::log('admin_pin_change_approved', [
            'model_type' => 'PinChangeRequest',
            'model_id' => $pinRequest->id,
            'severity' => 'high',
            'new_values' => [
                'card_last4' => $card->card_number_last4,
                'user' => $pinRequest->user->name,
            ],
        ]);

        return back()->with('success', "PIN changed for card ending in {$card->card_number_last4}. User has been notified.");
    }

    public function reject(Request $request, PinChangeRequest $pinRequest)
    {
        if (!$pinRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $pinRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_note' => $validated['rejection_reason'],
        ]);

        Notification::create([
            'user_id' => $pinRequest->user_id,
            'title' => 'PIN Change Request Denied',
            'message' => "Your PIN change request for card ending in {$pinRequest->card->card_number_last4} was rejected. Reason: {$validated['rejection_reason']}",
            'type' => 'danger',
            'icon' => '❌',
            'action_url' => route('cards.index'),
        ]);

        return back()->with('success', 'PIN change request rejected.');
    }
}
