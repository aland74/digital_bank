<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\Notification;
use App\Models\PinChangeRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class CardApiController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['cards' => $request->user()->cards()->with('account:id,account_number')->get()->map(fn($c) => [
            'id' => $c->id, 'card_type' => $c->card_type, 'card_brand' => $c->card_brand, 'last4' => $c->card_number_last4,
            'cardholder' => $c->cardholder_name, 'expiry' => $c->expiry_date, 'status' => $c->status,
            'daily_limit' => $c->daily_limit, 'monthly_limit' => $c->monthly_limit, 'account' => $c->account->account_number,
        ])]);
    }

    public function freeze(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['status' => 'frozen']);
        return response()->json(['message' => 'Card frozen.']);
    }

    public function unfreeze(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['status' => 'active', 'pin_attempts' => 0]);
        return response()->json(['message' => 'Card unfrozen.']);
    }

    /**
     * Order a new card (pending admin approval).
     */
    public function store(Request $request, AccountService $accountService)
    {
        $validated = $request->validate([
            'card_type' => 'required|in:debit,credit,virtual,prepaid',
            'card_brand' => 'required|in:visa,mastercard',
            'cardholder_name' => 'required|string|max:255',
            'account_type' => 'required|in:savings,checking,business,fixed_deposit',
            'currency' => 'required|string|size:3',
        ]);

        $user = $request->user();

        if (!$user->isKycVerified()) {
            return response()->json([
                'message' => 'KYC verification is required to order a card. Please upload your Passport and National ID first.'
            ], 403);
        }

        // Create primary account in selected currency
        $account = $accountService->createAccount(
            $user,
            $validated['account_type'],
            $validated['currency']
        );

        // Auto-create secondary account in the other currency
        $otherCurrency = $validated['currency'] === 'USD' ? 'IQD' : 'USD';
        $existingOther = $user->accounts()->where('currency', $otherCurrency)->first();
        if (!$existingOther) {
            $accountService->createAccount($user, $validated['account_type'], $otherCurrency, false);
        }

        // Generate card details
        $cardNumber = Card::generateCardNumber($validated['card_brand']);
        $cvv = Card::generateCvv($validated['card_brand']);
        $pinData = Card::generatePin();

        $expiry = now()->addYears(2);

        // Cards always start as pending_approval — admin must approve
        $card = Card::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'card_number_last4' => substr($cardNumber, -4),
            'card_number_encrypted' => Crypt::encryptString($cardNumber),
            'card_type' => $validated['card_type'],
            'card_brand' => $validated['card_brand'],
            'cardholder_name' => strtoupper($validated['cardholder_name']),
            'expiry_month' => $expiry->format('m'),
            'expiry_year' => $expiry->format('Y'),
            'cvv_encrypted' => Crypt::encryptString($cvv),
            'pin_hash' => $pinData['hash'],
            'status' => 'pending_approval',
            'daily_limit' => 5000.00,
            'monthly_limit' => 25000.00,
            'is_contactless' => true,
            'is_online_enabled' => true,
            'is_international_enabled' => false,
            'activated_at' => null,
            'pin_attempts' => 0,
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Card Order Submitted',
            'message' => "Your {$validated['card_brand']} {$validated['card_type']} card order has been submitted and is pending admin approval. You'll be notified once it's approved.",
            'type' => 'info',
            'icon' => '💳',
            'data' => ['card_id' => $card->id, 'action' => 'card_order_pending'],
        ]);

        // Notify admins
        $admins = \App\Models\User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Card Order Request',
                'message' => "{$user->name} has ordered a {$validated['card_brand']} {$validated['card_type']} card. Please review and approve.",
                'type' => 'warning',
                'icon' => '📋',
                'data' => ['card_id' => $card->id, 'action' => 'card_order_review'],
            ]);
        }

        AuditLog::log('card_ordered', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
            'new_values' => [
                'card_type' => $validated['card_type'],
                'card_brand' => $validated['card_brand'],
                'last4' => $card->card_number_last4,
                'account_number' => $account->account_number,
                'status' => 'pending_approval',
            ],
        ]);

        return response()->json([
            'message' => 'Card order submitted successfully! An admin will review and approve your request.',
            'card' => [
                'id' => $card->id,
                'card_type' => $card->card_type,
                'card_brand' => $card->card_brand,
                'last4' => $card->card_number_last4,
                'cardholder' => $card->cardholder_name,
                'expiry' => $card->expiry_date,
                'status' => $card->status,
                'account_number' => $account->account_number,
            ],
        ], 201);
    }

    /**
     * Admin approves a card order.
     */
    public function approve(Request $request, Card $card)
    {
        $user = $request->user();

        // Only admins can approve
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized. Only admins can approve card orders.'], 403);
        }

        if ($card->status !== 'pending_approval') {
            return response()->json(['message' => 'This card is not pending approval.'], 422);
        }

        $card->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Notify the card owner
        Notification::create([
            'user_id' => $card->user_id,
            'title' => 'Card Order Approved! 🎉',
            'message' => "Your {$card->card_brand} {$card->card_type} card ending in {$card->card_number_last4} has been approved and activated.",
            'type' => 'success',
            'icon' => '✅',
            'data' => ['card_id' => $card->id, 'action' => 'card_approved'],
        ]);

        AuditLog::log('card_approved', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
            'new_values' => ['status' => 'active', 'approved_by' => $user->id],
        ]);

        return response()->json(['message' => 'Card order approved and activated.']);
    }

    /**
     * Admin rejects a card order.
     */
    public function reject(Request $request, Card $card)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($card->status !== 'pending_approval') {
            return response()->json(['message' => 'This card is not pending approval.'], 422);
        }

        $request->validate(['reason' => 'nullable|string|max:500']);

        $card->update(['status' => 'rejected']);

        Notification::create([
            'user_id' => $card->user_id,
            'title' => 'Card Order Rejected',
            'message' => "Your {$card->card_brand} {$card->card_type} card order has been rejected." . ($request->reason ? " Reason: {$request->reason}" : ''),
            'type' => 'danger',
            'icon' => '❌',
            'data' => ['card_id' => $card->id, 'action' => 'card_rejected'],
        ]);

        AuditLog::log('card_rejected', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
            'new_values' => ['status' => 'rejected', 'rejected_by' => $user->id, 'reason' => $request->reason],
        ]);

        return response()->json(['message' => 'Card order rejected.']);
    }

    /**
     * Show a single card detail.
     */
    public function show(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);

        $card->load('account:id,account_number,currency');

        return response()->json([
            'card' => [
                'id' => $card->id,
                'card_type' => $card->card_type,
                'card_brand' => $card->card_brand,
                'last4' => $card->card_number_last4,
                'cardholder' => $card->cardholder_name,
                'expiry' => $card->expiry_date,
                'status' => $card->status,
                'status_label' => $card->status_label,
                'daily_limit' => $card->daily_limit,
                'monthly_limit' => $card->monthly_limit,
                'daily_spent' => $card->daily_spent,
                'monthly_spent' => $card->monthly_spent,
                'is_contactless' => $card->is_contactless,
                'is_online_enabled' => $card->is_online_enabled,
                'is_international_enabled' => $card->is_international_enabled,
                'activated_at' => $card->activated_at,
                'last_used_at' => $card->last_used_at,
                'account' => $card->account ? [
                    'account_number' => $card->account->account_number,
                    'currency' => $card->account->currency,
                ] : null,
            ],
        ]);
    }

    /**
     * Reveal card number and CVV (requires PIN verification).
     */
    public function reveal(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);

        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        if (!$card->verifyPin($request->pin)) {
            $frozen = $card->recordFailedPinAttempt();
            if ($frozen) {
                return response()->json(['message' => 'Too many wrong attempts. Your card has been frozen.'], 422);
            }
            $remaining = \App\Models\BankSetting::maxPinAttempts() - ($card->fresh()->pin_attempts ?? 0);
            return response()->json(['message' => "Incorrect PIN. {$remaining} attempts remaining."], 422);
        }

        try {
            $cvv = Crypt::decryptString($card->cvv_encrypted);
        } catch (\Exception $e) {
            $cvv = '***';
        }

        AuditLog::log('card_details_revealed', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'low',
        ]);

        return response()->json([
            'card_number' => $card->decrypted_card_number,
            'cvv' => $cvv,
        ]);
    }

    /**
     * Update daily and monthly card limits.
     */
    public function updateLimits(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);

        $validated = $request->validate([
            'daily_limit' => 'required|numeric|min:0|max:100000',
            'monthly_limit' => 'required|numeric|min:0|max:500000',
        ]);

        $card->update($validated);

        return response()->json([
            'message' => 'Card limits updated.',
            'daily_limit' => $card->daily_limit,
            'monthly_limit' => $card->monthly_limit,
        ]);
    }

    /**
     * Toggle contactless payments.
     */
    public function toggleContactless(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['is_contactless' => !$card->is_contactless]);

        return response()->json([
            'message' => 'Contactless ' . ($card->is_contactless ? 'enabled' : 'disabled') . '.',
            'is_contactless' => $card->is_contactless,
        ]);
    }

    /**
     * Toggle online transactions.
     */
    public function toggleOnline(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['is_online_enabled' => !$card->is_online_enabled]);

        return response()->json([
            'message' => 'Online transactions ' . ($card->is_online_enabled ? 'enabled' : 'disabled') . '.',
            'is_online_enabled' => $card->is_online_enabled,
        ]);
    }

    /**
     * Toggle international transactions.
     */
    public function toggleInternational(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['is_international_enabled' => !$card->is_international_enabled]);

        return response()->json([
            'message' => 'International transactions ' . ($card->is_international_enabled ? 'enabled' : 'disabled') . '.',
            'is_international_enabled' => $card->is_international_enabled,
        ]);
    }

    /**
     * Submit a PIN change request.
     */
    public function requestPinChange(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) abort(403);

        // Check for existing pending request
        $existing = PinChangeRequest::where('card_id', $card->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already have a pending PIN change request for this card.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|in:forgotten,stolen,compromised,other',
            'description' => 'nullable|string|max:500',
        ]);

        $pinRequest = PinChangeRequest::create([
            'user_id' => $request->user()->id,
            'card_id' => $card->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        // If reason is stolen, freeze the card immediately
        if ($validated['reason'] === 'stolen') {
            $card->update(['status' => 'frozen']);

            Notification::create([
                'user_id' => $card->user_id,
                'title' => 'Card Frozen — Stolen PIN Report',
                'message' => "Your card ending in {$card->card_number_last4} has been frozen for security after reporting a stolen PIN.",
                'type' => 'security',
                'icon' => '🚨',
            ]);
        }

        // Notify admins
        $admins = \App\Models\User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'PIN Change Request',
                'message' => "{$request->user()->name} has requested a PIN change for card ending in {$card->card_number_last4}. Reason: {$pinRequest->reason_label}.",
                'type' => 'warning',
                'icon' => '🔐',
                'data' => ['action' => 'pin_change_review', 'pin_request_id' => $pinRequest->id],
            ]);
        }

        AuditLog::log('pin_change_requested', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'high',
            'new_values' => [
                'reason' => $validated['reason'],
                'request_id' => $pinRequest->id,
            ],
        ]);

        return response()->json([
            'message' => 'PIN change request submitted successfully. An admin will review it shortly.',
            'pin_request' => $pinRequest,
        ]);
    }
}
