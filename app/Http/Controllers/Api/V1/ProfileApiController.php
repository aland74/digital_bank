<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;

class ProfileApiController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profileData = $user->only(['id', 'name', 'email', 'phone', 'date_of_birth', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code', 'status', 'two_factor_enabled', 'last_login_at', 'branch']);
        $profileData['branch_display'] = $user->branch_display_name ?? ($user->branch ? ucfirst($user->branch) : 'N/A');
        $profileData['role'] = $user->role ?? 'customer';
        $profileData['is_kyc_verified'] = $user->isKycVerified();
        return response()->json(['profile' => $profileData]);
    }

    public function update(Request $request)
    {
        $v = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address_line_1' => 'sometimes|string',
            'address_line_2' => 'sometimes|string|nullable',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'country' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
            'branch' => 'sometimes|string|nullable|max:100',
        ]);
        $user = $request->user();
        $user->update($v);
        $user->refresh();
        $profileData = $user->only(['id', 'name', 'email', 'phone', 'date_of_birth', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code', 'status', 'two_factor_enabled', 'last_login_at', 'branch']);
        $profileData['branch_display'] = $user->branch_display_name ?? ($user->branch ? ucfirst($user->branch) : 'N/A');
        $profileData['role'] = $user->role ?? 'customer';
        $profileData['is_kyc_verified'] = $user->isKycVerified();
        return response()->json(['message' => 'Profile updated.', 'profile' => $profileData]);
    }

    /**
     * Show recent security audit logs.
     */
    public function security(Request $request)
    {
        $user = $request->user();
        $recentLogs = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'audit_logs' => $recentLogs,
        ]);
    }

    /**
     * Change the user's password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'min:8', Password::min(8)->mixedCase()->numbers()->symbols()],
            'confirm_password' => 'required|same:new_password',
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $request->user()->update(['password' => $validated['new_password']]);

        AuditLog::log('password_changed', ['severity' => 'high']);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /**
     * Upload a KYC document.
     */
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

        return response()->json([
            'message' => 'Document uploaded successfully! It will be reviewed within 24-48 hours.',
            'document' => $document,
        ], 201);
    }

    /**
     * Get KYC documents status.
     */
    public function kycStatus(Request $request)
    {
        $documents = $request->user()->kycDocuments()->latest()->get();

        $hasPassport = $documents->where('document_type', 'passport')
            ->whereIn('status', ['pending', 'under_review', 'verified'])
            ->isNotEmpty();

        $hasNationalId = $documents->where('document_type', 'national_id')
            ->whereIn('status', ['pending', 'under_review', 'verified'])
            ->isNotEmpty();

        return response()->json([
            'documents' => $documents,
            'has_passport' => $hasPassport,
            'has_national_id' => $hasNationalId,
            'is_kyc_verified' => $request->user()->isKycVerified(),
        ]);
    }

    /**
     * Get 2FA setup info.
     */
    public function showTwoFactor(Request $request)
    {
        $user = $request->user();
        $qrCodeUrl = null;
        $secretKey = null;

        if (!$user->two_factor_enabled) {
            $google2fa = new Google2FA();
            $secretKey = $google2fa->generateSecretKey();
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secretKey
            );
        }

        return response()->json([
            'two_factor_enabled' => $user->two_factor_enabled,
            'qr_code_url' => $qrCodeUrl,
            'secret_key' => $secretKey,
        ]);
    }

    /**
     * Enable 2FA with verification code.
     */
    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'secret' => 'required|string',
        ]);

        $user = $request->user();
        $secretKey = $request->secret;

        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($secretKey, $request->code)) {
            return response()->json(['message' => 'Invalid verification code. Please try again.'], 422);
        }

        $user->update([
            'two_factor_secret' => encrypt($secretKey),
            'two_factor_enabled' => true,
        ]);

        AuditLog::log('two_factor_enabled', ['severity' => 'high']);

        return response()->json(['message' => 'Two-factor authentication has been enabled successfully!']);
    }

    /**
     * Disable 2FA (requires password).
     */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        AuditLog::log('two_factor_disabled', ['severity' => 'high']);

        return response()->json(['message' => 'Two-factor authentication has been disabled.']);
    }
}
