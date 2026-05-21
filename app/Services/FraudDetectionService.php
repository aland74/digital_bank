<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Evaluate a transfer to check for fraudulent behavior.
     * Uses multiple heuristic algorithms for risk scoring.
     * Returns an array with 'status' => 'blocked' | 'flagged' | 'allowed'
     */
    public function evaluateTransfer(User $user, float $amount): array
    {
        $riskScore = 0;
        $reasons = [];

        // ── Rule 1: Amount Threshold (Manual Review for >$10k) ─────
        if ($amount > 10000) {
            return [
                'status' => 'blocked',
                'reason' => 'MANUAL_REVIEW',
                'message' => 'Transfer amount exceeds automatic processing limits. An administrator must approve this transaction.',
                'risk_score' => 100,
            ];
        }

        // ── Rule 2: Velocity Check (≥5 transfers in 15 minutes) ────
        $userAccountIds = $user->accounts()->pluck('id');

        $recentTransfers = Transaction::whereIn('account_id', $userAccountIds)
            ->where('type', 'transfer_out')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentTransfers >= 5) {
            $user->update(['status' => 'frozen']);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'account_frozen_velocity',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'critical',
                'new_values' => [
                    'reason' => 'Transaction velocity limit exceeded',
                    'transfers_in_15min' => $recentTransfers,
                ],
            ]);

            Log::warning("FraudEngine: User {$user->id} frozen due to transaction velocity. Count: {$recentTransfers} transfers in 15min.");

            return [
                'status' => 'blocked',
                'reason' => 'VELOCITY_FREEZE',
                'message' => 'Your account has been temporarily frozen due to unusual activity. Please contact support.',
                'risk_score' => 95,
            ];
        }

        if ($recentTransfers >= 3) {
            $riskScore += 30;
            $reasons[] = 'High transfer frequency';
        }

        // ── Rule 3: Time-Based Anomaly (2am-5am, amounts > $1000) ──
        $currentHour = (int) now()->format('H');
        if ($currentHour >= 2 && $currentHour <= 5 && $amount > 1000) {
            $riskScore += 25;
            $reasons[] = 'Unusual hour transfer';
            Log::info("FraudEngine: User {$user->id} transferring \${$amount} at unusual hour ({$currentHour}:00).");
        }

        // ── Rule 4: Amount Deviation (>3x average) ─────────────────
        $avgAmount = Transaction::whereIn('account_id', $userAccountIds)
            ->where('type', 'transfer_out')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('amount');

        if ($avgAmount && $avgAmount > 0 && $amount > ($avgAmount * 3)) {
            $riskScore += 20;
            $reasons[] = "Amount {$amount} exceeds 3x average ({$avgAmount})";
            Log::info("FraudEngine: User {$user->id} amount deviation. Amount: {$amount}, Avg: {$avgAmount}.");
        }

        // ── Rule 5: New Account Transfer (account < 7 days, > $2000) ─
        if ($user->created_at->diffInDays(now()) < 7 && $amount > 2000) {
            $riskScore += 25;
            $reasons[] = 'New account with high-value transfer';
        }

        // ── Rule 6: Daily Cumulative Check ─────────────────────────
        $dailyTotal = Transaction::whereIn('account_id', $userAccountIds)
            ->where('type', 'transfer_out')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($dailyTotal + $amount) > 25000) {
            $riskScore += 30;
            $reasons[] = "Daily cumulative exceeds $25,000 (current: {$dailyTotal})";
        }

        // ── Decision ───────────────────────────────────────────────
        if ($riskScore >= 70) {
            Log::warning("FraudEngine: BLOCKED User {$user->id}. Score: {$riskScore}. Reasons: " . implode(', ', $reasons));

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'fraud_transfer_blocked',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'high',
                'new_values' => [
                    'risk_score' => $riskScore,
                    'reasons' => $reasons,
                    'amount' => $amount,
                ],
            ]);

            return [
                'status' => 'blocked',
                'reason' => 'HIGH_RISK',
                'message' => 'This transfer has been flagged by our security system. Please try again later or contact support.',
                'risk_score' => $riskScore,
            ];
        }

        if ($riskScore >= 40) {
            Log::info("FraudEngine: FLAGGED User {$user->id}. Score: {$riskScore}. Reasons: " . implode(', ', $reasons));

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'fraud_transfer_flagged',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'medium',
                'new_values' => [
                    'risk_score' => $riskScore,
                    'reasons' => $reasons,
                    'amount' => $amount,
                ],
            ]);
        }

        return [
            'status' => 'allowed',
            'risk_score' => $riskScore,
        ];
    }
}
