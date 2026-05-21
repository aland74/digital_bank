<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BeneficiaryApiController extends Controller
{
    public function index(Request $request) { return response()->json(['beneficiaries' => $request->user()->beneficiaries()->orderBy('is_favorite', 'desc')->get()]); }
    public function store(Request $request) {
        $v = $request->validate(['name'=>'required|string','account_number'=>'required|string','type'=>'required|in:internal,domestic,international','currency'=>'required|string|size:3']);
        $b = $request->user()->beneficiaries()->create($v);
        return response()->json(['message'=>'Created.','beneficiary'=>$b], 201);
    }
    public function show(Request $request, $id) { return response()->json($request->user()->beneficiaries()->findOrFail($id)); }
    public function update(Request $request, $id) {
        $b = $request->user()->beneficiaries()->findOrFail($id);
        $b->update($request->only(['name','nickname','account_number','bank_name']));
        return response()->json(['message'=>'Updated.']);
    }
    public function destroy(Request $request, $id) {
        $request->user()->beneficiaries()->findOrFail($id)->delete();
        return response()->json(['message'=>'Deleted.']);
    }
}
