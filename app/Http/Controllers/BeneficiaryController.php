<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        $beneficiaries = $request->user()->beneficiaries()
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();

        return view('beneficiaries.index', compact('beneficiaries'));
    }

    public function create()
    {
        return view('beneficiaries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:34',
            'routing_number' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:11',
            'iban' => 'nullable|string|max:34',
            'type' => 'required|in:internal,domestic,international',
            'currency' => 'required|string|size:3',
        ]);

        $request->user()->beneficiaries()->create($validated);

        return redirect()->route('beneficiaries.index')
            ->with('success', 'Beneficiary added successfully.');
    }

    public function edit(Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        return view('beneficiaries.edit', compact('beneficiary'));
    }

    public function update(Request $request, Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:34',
            'type' => 'required|in:internal,domestic,international',
        ]);

        $beneficiary->update($validated);

        return redirect()->route('beneficiaries.index')
            ->with('success', 'Beneficiary updated successfully.');
    }

    public function destroy(Beneficiary $beneficiary)
    {
        $this->authorize('delete', $beneficiary);
        $beneficiary->delete();

        return redirect()->route('beneficiaries.index')
            ->with('success', 'Beneficiary removed.');
    }

    public function toggleFavorite(Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        $beneficiary->update(['is_favorite' => !$beneficiary->is_favorite]);

        return back()->with('success', 'Beneficiary updated.');
    }
}
