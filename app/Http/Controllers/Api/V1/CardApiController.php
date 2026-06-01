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

        if (!$user->isKycVerified()) {
            return response()->json([
                'message' => 'You must complete KYC verification before ordering a card. Please upload your Passport and National ID.',
            ], 422);
        }

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
            'status'                   => 'pending_approval',
            'daily_limit'              => 5000.00,
            'monthly_limit'            => 25000.00,
            'is_contactless'           => true,
            'is_online_enabled'        => true,
            'is_international_enabled' => false,
            'activated_at'             => null,
            'pin_attempts'             => 0,
        ]);

        // Notify user of pending card order
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Card Order Submitted 💳',
            'message' => "Your {$validated['card_brand']} {$validated['card_type']} card order has been submitted and is pending admin approval.",
            'type'    => 'info',
            'icon'    => '💳',
        ]);

        // Notify admins
        $admins = \App\Models\User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'New Card Order Request 📋',
                'message' => "{$user->name} has ordered a {$validated['card_brand']} {$validated['card_type']} card. Please review and approve.",
                'type'    => 'warning',
                'icon'    => '📋',
            ]);
        }

        AuditLog::log('card_ordered', [
            'model_type' => 'Card',
            'model_id'   => $card->id,
            'severity'   => 'medium',
            'new_values' => [
                'card_type'  => $validated['card_type'],
                'card_brand' => $validated['card_brand'],
                'last4'      => $card->card_number_last4,
                'status'     => 'pending_approval',
            ],
        ]);

        return response()->json([
            'message' => 'Card order submitted successfully! It is pending admin approval.',
            'card'    => [
                'id'         => $card->id,
                'card_type'  => $card->card_type,
                'card_brand' => $card->card_brand,
                'last4'      => $card->card_number_last4,
                'status'     => $card->status,
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
