<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Account;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\PinChangeRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $cards = $request->user()->cards()->with('account')->get();
        return view('cards.index', compact('cards'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $kycVerified = $user->isKycVerified();

        return view('cards.create', compact('user', 'kycVerified'));
    }

    public function store(Request $request, AccountService $accountService)
    {
        $user = $request->user();

        // ── KYC Hard Block ─────────────────────────────────────────
        if (!$user->isKycVerified()) {
            return redirect()->route('profile.kyc')
                ->with('error', '🔒 KYC verification required. Please upload and get your Passport and National ID verified before creating a card.');
        }
        // ───────────────────────────────────────────────────────────

        $validated = $request->validate([
            'card_type' => 'required|in:debit,credit,virtual,prepaid',
            'card_brand' => 'required|in:visa,mastercard',
            'cardholder_name' => 'required|string|max:255',
            'account_type' => 'required|in:savings,checking,business,fixed_deposit',
            'currency' => 'required|string|size:3',
        ]);

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

        // Set expiry: 2 years from now
        $expiry = now()->addYears(2);

        // Status is always active here because we only reach this point if KYC is verified
        $status = 'active';
        $activatedAt = now();

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
            'status' => $status,
            'daily_limit' => 5000.00,
            'monthly_limit' => 25000.00,
            'is_contactless' => true,
            'is_online_enabled' => true,
            'is_international_enabled' => false,
            'activated_at' => $activatedAt,
            'pin_attempts' => 0,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Card Activated! 🎉',
            'message' => "Your card ending in {$card->card_number_last4} has been activated and is ready to use.",
            'type' => 'success',
            'icon' => '💳',
            'action_url' => route('cards.index'),
        ]);

        AuditLog::log('card_ordered', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
            'new_values' => [
                'card_type' => $validated['card_type'],
                'card_brand' => $validated['card_brand'],
                'last4' => $card->card_number_last4,
                'account_number' => $account->account_number,
                'status' => $status,
            ],
        ]);

        // Pass the plain PIN to the created page (shown ONCE)
        $message = 'Card created successfully and is active.';

        return redirect()->route('cards.created', ['card' => $card->id])
            ->with('card_pin', $pinData['plain'])
            ->with('success', $message);
    }

    public function created(Request $request, Card $card)
    {
        if ($card->user_id !== $request->user()->id) {
            abort(403);
        }

        $pin = session('card_pin');

        return view('cards.created', compact('card', 'pin'));
    }

    public function show(Card $card)
    {
        $this->authorize('view', $card);
        return view('cards.show', compact('card'));
    }

    public function reveal(Request $request, Card $card)
    {
        $this->authorize('view', $card);

        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        if (!$card->verifyPin($request->pin)) {
            $frozen = $card->recordFailedPinAttempt();
            if ($frozen) {
                return back()->with('error', 'Too many wrong attempts. Your card has been frozen.');
            }
            $remaining = \App\Models\BankSetting::maxPinAttempts() - ($card->fresh()->pin_attempts ?? 0);
            return back()->with('error', "Incorrect PIN. {$remaining} attempts remaining.");
        }

        try {
            $cvv = Crypt::decryptString($card->cvv_encrypted);
        } catch (\Exception $e) {
            $cvv = '***';
        }

        session()->flash('revealed_card_' . $card->id, [
            'number' => $card->decrypted_card_number,
            'cvv' => $cvv,
        ]);

        AuditLog::log('card_details_revealed', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'low',
        ]);

        return back()->with('success', 'Card details revealed temporarily.');
    }

    public function freeze(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['status' => 'frozen']);

        Notification::create([
            'user_id' => $card->user_id,
            'title' => 'Card Frozen ❄️',
            'message' => "Your card ending in {$card->card_number_last4} has been frozen.",
            'type' => 'warning',
            'icon' => '❄️',
        ]);

        AuditLog::log('card_frozen', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
        ]);

        return back()->with('success', 'Card frozen successfully.');
    }

    public function unfreeze(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['status' => 'active', 'pin_attempts' => 0]);

        Notification::create([
            'user_id' => $card->user_id,
            'title' => 'Card Unfrozen',
            'message' => "Your card ending in {$card->card_number_last4} has been unfrozen and is now active.",
            'type' => 'success',
            'icon' => '✅',
            'action_url' => route('cards.index'),
        ]);

        AuditLog::log('card_unfrozen', [
            'model_type' => 'Card',
            'model_id' => $card->id,
            'severity' => 'medium',
        ]);

        return back()->with('success', 'Card unfrozen successfully.');
    }

    // ── PIN Change Request (User sends request to Admin) ──────

    public function showRequestPinChange(Card $card)
    {
        $this->authorize('update', $card);

        // Check if there's already a pending request
        $pendingRequest = PinChangeRequest::where('card_id', $card->id)
            ->where('status', 'pending')
            ->first();

        return view('cards.request-pin-change', compact('card', 'pendingRequest'));
    }

    public function requestPinChange(Request $request, Card $card)
    {
        $this->authorize('update', $card);

        // Check for existing pending request
        $existing = PinChangeRequest::where('card_id', $card->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have a pending PIN change request for this card.');
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
                'title' => 'Card Frozen — Stolen PIN Report 🚨',
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
                'title' => 'PIN Change Request 🔐',
                'message' => "{$request->user()->name} has requested a PIN change for card ending in {$card->card_number_last4}. Reason: {$pinRequest->reason_label}.",
                'type' => 'warning',
                'icon' => '🔐',
                'action_url' => route('admin.pin-requests'),
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

        return redirect()->route('cards.index')
            ->with('success', 'PIN change request submitted successfully. An admin will review it shortly.');
    }

    public function updateLimits(Request $request, Card $card)
    {
        $this->authorize('update', $card);

        $validated = $request->validate([
            'daily_limit' => 'required|numeric|min:0|max:100000',
            'monthly_limit' => 'required|numeric|min:0|max:500000',
        ]);

        $card->update($validated);

        return back()->with('success', 'Card limits updated.');
    }

    public function toggleContactless(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['is_contactless' => !$card->is_contactless]);

        return back()->with('success', 'Contactless ' . ($card->is_contactless ? 'enabled' : 'disabled') . '.');
    }

    public function toggleOnline(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['is_online_enabled' => !$card->is_online_enabled]);

        return back()->with('success', 'Online transactions ' . ($card->is_online_enabled ? 'enabled' : 'disabled') . '.');
    }

    public function toggleInternational(Card $card)
    {
        $this->authorize('update', $card);
        $card->update(['is_international_enabled' => !$card->is_international_enabled]);

        return back()->with('success', 'International transactions ' . ($card->is_international_enabled ? 'enabled' : 'disabled') . '.');
    }
}
