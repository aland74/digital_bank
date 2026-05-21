<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;

class CardApiController extends Controller
{
    public function index(Request $request) {
        return response()->json(['cards' => $request->user()->cards()->with('account:id,account_number')->get()->map(fn($c) => [
            'id'=>$c->id,'card_type'=>$c->card_type,'card_brand'=>$c->card_brand,'last4'=>$c->card_number_last4,
            'cardholder'=>$c->cardholder_name,'expiry'=>$c->expiry_date,'status'=>$c->status,
            'daily_limit'=>$c->daily_limit,'monthly_limit'=>$c->monthly_limit,'account'=>$c->account->account_number,
        ])]);
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
