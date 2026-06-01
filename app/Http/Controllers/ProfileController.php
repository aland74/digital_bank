<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $kycDocuments = $user->kycDocuments()->latest()->get();
        $cards = $user->cards()->with('account')->get();

        return view('profile.edit', compact('user', 'kycDocuments', 'cards'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function security(Request $request)
    {
        $user = $request->user();
        $recentLogs = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('profile.security', compact('user', 'recentLogs'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $request->user()->update(['password' => $validated['password']]);

        AuditLog::log('password_changed', ['severity' => 'high']);

        return back()->with('success', 'Password changed successfully.');
    }

    // ── KYC Document Upload ────────────────────────────────────

    public function showKycUpload(Request $request)
    {
        $user = $request->user();
        $documents = $user->kycDocuments()->latest()->get();

        $hasPassport = $documents->where('document_type', 'passport')
            ->whereIn('status', ['pending', 'under_review', 'verified'])
            ->isNotEmpty();

        $hasNationalId = $documents->where('document_type', 'national_id')
            ->whereIn('status', ['pending', 'under_review', 'verified'])
            ->isNotEmpty();

        return view('profile.kyc-upload', compact('user', 'documents', 'hasPassport', 'hasNationalId'));
    }

    public function uploadKyc(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:passport,national_id',
            'document_number' => 'required|string|max:50',
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $file = $request->file('document_file');
        $path = $file->store('kyc-documents/' . $request->user()->id, 'local');

        $document = KycDocument::create([
            'user_id' => $request->user()->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        AuditLog::log('kyc_document_uploaded', [
            'model_type' => 'KycDocument',
            'model_id' => $document->id,
            'severity' => 'medium',
            'new_values' => [
                'type' => $validated['document_type'],
                'document_number' => substr($validated['document_number'], 0, 3) . '***',
            ],
        ]);

        // Notify admins about new KYC document
        $admins = \App\Models\User::on(\App\Services\DistributedDatabaseService::getHqConnection())->admins()->get();
        foreach ($admins as $admin) {
            \App\Models\Notification::notifyUserOnBranch($admin->id, [
                'user_id' => $admin->id,
                'title' => 'New KYC Document',
                'message' => "{$request->user()->name} uploaded a {$document->document_type_label} for verification.",
                'type' => 'info',
                'icon' => '📄',
                'action_url' => route('admin.kyc'),
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Document uploaded successfully! It will be reviewed within 24-48 hours.');
    }
}
