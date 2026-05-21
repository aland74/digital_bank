<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        $accounts = $user->accounts()->active()->get();

        return response()->json([
            'total_balance' => $accounts->sum('balance'),
            'accounts_count' => $accounts->count(),
            'active_cards' => $user->cards()->active()->count(),
            'active_loans' => $user->loans()->active()->count(),
            'unread_notifications' => $user->unreadNotificationsCount(),
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'account_number' => $a->account_number,
                'account_type' => $a->account_type,
                'balance' => $a->balance,
                'currency' => $a->currency,
                'is_primary' => $a->is_primary,
            ]),
        ]);
    }
}
