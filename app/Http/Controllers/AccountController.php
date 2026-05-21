<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->withCount('transactions')->get();
        return view('accounts.index', compact('accounts'));
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $transactions = $account->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('accounts.show', compact('account', 'transactions'));
    }
}
