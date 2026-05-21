<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accountIds = $user->accounts()->pluck('id');

        $query = Transaction::whereIn('account_id', $accountIds)
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference_number', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('recipient_name', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->paginate(20)->appends($request->query());
        $accounts = $user->accounts()->get();

        return view('transactions.index', compact('transactions', 'accounts'));
    }

    public function show(Transaction $transaction)
    {
        // Verify ownership
        $userAccountIds = auth()->user()->accounts()->pluck('id')->toArray();
        if (!in_array($transaction->account_id, $userAccountIds)) {
            abort(403);
        }

        return view('transactions.show', compact('transaction'));
    }
}
