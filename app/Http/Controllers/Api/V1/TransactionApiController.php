<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionApiController extends Controller
{
    public function index(Request $request)
    {
        $accountIds = $request->user()->accounts()->pluck('id');
        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $accountIds = $request->user()->accounts()->pluck('id')->toArray();
        if (!in_array($transaction->account_id, $accountIds)) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        return response()->json(['transaction' => $transaction]);
    }

    public function transfer(Request $request, TransactionService $transactionService)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $fromAccount = $request->user()->accounts()->findOrFail($validated['from_account_id']);
        $toAccount = Account::where('account_number', $validated['to_account_number'])->firstOrFail();

        try {
            $result = $transactionService->transfer($fromAccount, $toAccount, $validated['amount'], $validated['description'] ?? '', 'mobile');
            return response()->json(['message' => 'Transfer successful.', 'reference' => $result['reference']]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
