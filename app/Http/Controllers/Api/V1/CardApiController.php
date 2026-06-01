<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CardApiController extends Controller
{
    public function index(Request $request) {
        return response()->json(['cards' => $request->user()->cards()->with('account:id,account_number')->get()->map(fn($c) => [
            'id'=>$c->id,'card_type'=>$c->card_type,'card_brand'=>$c->card_brand,'last4'=>$c->card_number_last4,
            'cardholder'=>$c->cardholder_name,'expiry'=>$c->expiry_date,'status'=>$c->status,
            'daily_limit'=>$c->daily_limit,'monthly_limit'=>$c->monthly_limit,'account'=>$c->account->account_number,
        ])]);
    }

    public function store(Request $request, AccountService $accountService) {
        $user = $request->user();

        // ── KYC Hard Block ──────────────────────────────────────────
        if (!$user->isKycVerified()) {
            return response()->json([
                'message'         => 'KYC verification required to create a card.',
                'error_code'      => 'KYC_REQUIRED',
                'required_action' => 'UPLOAD_KYC',
                'details'         => 'You must have a verified Passport and National ID before you can create or activate a card.',
            ], 403);
        }
        // ────────────────────────────────────────────────────────────

        // KYC is verified — card is always active
        $status      = 'active';
        $activatedAt = now();

        $validated = $request->validate([
            'card_type'       => 'required|in:debit,credit,virtual,prepaid',
            'card_brand'      => 'required|in:visa,mastercard',
            'cardholder_name' => 'required|string|max:255',
            'account_type'    => 'required|in:savings,checking,business,fixed_deposit',
            'currency'        => 'required|string|size:3',
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

        $card = Card::create([
            'account_id'               => $account->id,
            'user_id'                  => $user->id,
            'card_number_last4'        => substr($cardNumber, -4),
            'card_number_encrypted'    => Crypt::encryptString($cardNumber),
            'card_type'                => $validated['card_type'],
            'card_brand'               => $validated['card_brand'],
            'cardholder_name'          => strtoupper($validated['cardholder_name']),
            'expiry_month'             => $expiry->format('m'),
            'expiry_year'              => $expiry->format('Y'),
            'cvv_encrypted'            => Crypt::encryptString($cvv),
            'pin_hash'                 => $pinData['hash'],
            'status'                   => $status,
            'daily_limit'              => 5000.00,
            'monthly_limit'            => 25000.00,
            'is_contactless'           => true,
            'is_online_enabled'        => true,
            'is_international_enabled' => false,
            'activated_at'             => $activatedAt,
            'pin_attempts'             => 0,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Card Activated! 🎉',
            'message' => "Your card ending in {$card->card_number_last4} has been activated and is ready to use.",
            'type'    => 'success',
            'icon'    => '💳',
        ]);

        AuditLog::log('card_ordered', [
            'model_type' => 'Card',
            'model_id'   => $card->id,
            'severity'   => 'medium',
            'new_values' => [
                'card_type'  => $validated['card_type'],
                'card_brand' => $validated['card_brand'],
                'last4'      => $card->card_number_last4,
                'status'     => $status,
            ],
        ]);

        return response()->json([
            'message' => 'Card created successfully and is active.',
            'card'    => [
                'id'         => $card->id,
                'card_type'  => $card->card_type,
                'card_brand' => $card->card_brand,
                'last4'      => $card->card_number_last4,
                'status'     => $card->status,
                'pin'        => $pinData['plain'],
            ],
        ], 201);
    }

    public function freeze(Request $request, Card $card) {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['status'=>'frozen']);
        return response()->json(['message'=>'Card frozen.']);
    }
    public function unfreeze(Request $request, Card $card) {
        if ($card->user_id !== $request->user()->id) abort(403);
        $card->update(['status'=>'active']);
        return response()->json(['message'=>'Card unfrozen.']);
    }
}
