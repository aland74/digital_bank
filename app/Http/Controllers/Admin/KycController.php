<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\Notification;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function index()
    {
        $documents = KycDocument::pending()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return view('admin.kyc', compact('documents'));
    }

    public function verify(KycDocument $document)
    {
        $document->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $user = $document->user;

        // Check if user now has both passport and national_id verified
        $hasPassport = $user->kycDocuments()->where('document_type', 'passport')->where('status', 'verified')->exists();
        $hasNationalId = $user->kycDocuments()->where('document_type', 'national_id')->where('status', 'verified')->exists();

        if ($hasPassport && $hasNationalId) {
            // Auto-activate user
            if ($user->status === 'pending_verification') {
                $user->update(['status' => 'active']);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Account Fully Verified! 🎉',
                    'message' => 'Both your Passport and National ID have been verified. Your account is now fully active!',
                    'type' => 'success',
                    'icon' => '✅',
                    'action_url' => route('dashboard'),
                ]);
            }

            // Activate any pending cards
            $pendingCards = $user->cards()->where('status', 'inactive')->whereNull('activated_at')->get();
            foreach ($pendingCards as $card) {
                $card->update(['status' => 'active', 'activated_at' => now()]);
                Notification::cardActivated($card);
            }
        } else {
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Document Verified ✅',
                'message' => "Your {$document->document_type_label} has been verified." .
                    (!$hasPassport ? ' Please also upload your Passport.' : '') .
                    (!$hasNationalId ? ' Please also upload your National ID.' : ''),
                'type' => 'success',
                'icon' => '📄',
                'action_url' => route('profile.kyc'),
            ]);
        }

        return back()->with('success', 'KYC document verified.');
    }

    public function reject(Request $request, KycDocument $document)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        Notification::create([
            'user_id' => $document->user_id,
            'title' => 'Document Rejected',
            'message' => "Your {$document->document_type_label} was rejected: {$validated['rejection_reason']}. Please re-upload.",
            'type' => 'danger',
            'icon' => '❌',
            'action_url' => route('profile.kyc'),
        ]);

        return back()->with('success', 'KYC document rejected.');
    }
}
