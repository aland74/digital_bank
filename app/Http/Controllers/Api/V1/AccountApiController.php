<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\Request;

class AccountApiController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->get()->map(fn($a) => [
            'id' => $a->id, 'account_number' => $a->account_number, 'account_name' => $a->account_name,
            'account_type' => $a->account_type, 'balance' => $a->balance, 'available_balance' => $a->available_balance,
            'currency' => $a->currency, 'status' => $a->status, 'is_primary' => $a->is_primary,
        ]);
        return response()->json(['accounts' => $accounts]);
    }

    public function show(Request $request, $id)
    {
        $account = $request->user()->accounts()->findOrFail($id);
        $transactions = $account->transactions()->orderBy('created_at', 'desc')->limit(20)->get();

        return response()->json([
            'account' => $account->only(['id', 'account_number', 'account_name', 'account_type', 'balance', 'available_balance', 'currency', 'status', 'is_primary', 'interest_rate', 'opened_at']),
            'recent_transactions' => $transactions->map(fn($t) => [
                'id' => $t->id, 'reference' => $t->reference_number, 'type' => $t->type,
                'amount' => $t->amount, 'description' => $t->description, 'status' => $t->status,
                'created_at' => $t->created_at->toISOString(),
            ]),
        ]);
    }

    public function store(Request $request, AccountService $accountService)
    {
        $validated = $request->validate([
            'account_type' => 'required|in:savings,checking,business,fixed_deposit',
            'currency' => 'required|string|size:3',
        ]);
        $account = $accountService->createAccount($request->user(), $validated['account_type'], $validated['currency']);
        return response()->json(['message' => 'Account created.', 'account_number' => $account->account_number], 201);
    }
}
