<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileApiController extends Controller
{
    public function show(Request $request) {
        $user = $request->user();
        return response()->json(['profile' => $user->only(['id','name','email','phone','date_of_birth','address_line_1','address_line_2','city','state','country','postal_code','status','two_factor_enabled','last_login_at'])]);
    }
    public function update(Request $request) {
        $v = $request->validate(['name'=>'sometimes|string|max:255','phone'=>'sometimes|string|max:20','address_line_1'=>'sometimes|string','city'=>'sometimes|string','state'=>'sometimes|string','country'=>'sometimes|string','postal_code'=>'sometimes|string']);
        $request->user()->update($v);
        return response()->json(['message'=>'Profile updated.']);
    }
}
