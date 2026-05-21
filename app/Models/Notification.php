<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use App\Services\DistributedDatabaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    use SyncsWithHQ;
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'icon',
        'action_url', 'is_read', 'read_at', 'data',
    ];

    public function getTranslatedTitleAttribute(): string
    {
        return __($this->title);
    }

    public function getTranslatedMessageAttribute(): string
    {
        $msg = $this->message;
        $data = $this->data ?? [];

        // Map notification types to translation keys
        $translationKey = $this->detectTranslationKey($msg, $data);

        if ($translationKey) {
            $translated = __('notifications.' . $translationKey['key'], $translationKey['params']);
            if ($translated !== 'notifications.' . $translationKey['key']) {
                return $translated;
            }
        }

        // Fallback: try direct translation
        $direct = __($msg);
        return $direct !== $msg ? $direct : $msg;
    }

    /**
     * Detect the translation key and parameters from a notification message.
     */
    private function detectTranslationKey(string $msg, array $data): ?array
    {
        // Card notifications
        if (preg_match('/Your (.+?) (.+?) card has been activated and is ready to use\./', $msg, $m)) {
            return ['key' => 'card_activated', 'params' => ['type' => $m[1], 'brand' => $m[2], 'last4' => '']];
        }
        if (preg_match('/Your card ending in (\w+) has been frozen due to (\d+) incorrect PIN attempts/', $msg, $m)) {
            return ['key' => 'card_pin_frozen', 'params' => ['last4' => $m[1], 'attempts' => $m[2]]];
        }
        if (preg_match('/Your card ending in (\w+) has been frozen\./', $msg, $m)) {
            return ['key' => 'card_frozen', 'params' => ['last4' => $m[1]]];
        }
        if (preg_match('/The PIN for your card ending in (\w+) has been changed/', $msg, $m)) {
            return ['key' => 'card_pin_changed', 'params' => ['last4' => $m[1]]];
        }
        if (preg_match('/Your card ending in (\w+) has been activated/', $msg, $m)) {
            return ['key' => 'card_activated_last4', 'params' => ['last4' => $m[1]]];
        }
        if (preg_match('/Your card has been created but is inactive/', $msg)) {
            return ['key' => 'card_created_inactive', 'params' => []];
        }

        // Loan notifications
        if (preg_match('/Your (.+?) loan of \$([0-9.,]+) has been approved/', $msg, $m)) {
            return ['key' => 'loan_approved', 'params' => ['type' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/Your (.+?) loan application has been rejected\. Reason: (.+)/', $msg, $m)) {
            return ['key' => 'loan_rejected', 'params' => ['type' => $m[1], 'reason' => $m[2]]];
        }
        if (preg_match('/Your (.+?) loan application for \$([0-9.,]+) has been submitted/', $msg, $m)) {
            return ['key' => 'loan_submitted', 'params' => ['type' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/Your loan application for \$([0-9.,]+) could not be processed/', $msg, $m)) {
            return ['key' => 'loan_rejected_reserves', 'params' => ['amount' => $m[1]]];
        }

        // Transfer notifications
        if (preg_match('/(.+?) wants to send you \$([0-9.,]+)\./', $msg, $m)) {
            return ['key' => 'transfer_pending', 'params' => ['name' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/(.+?) accepted your transfer of \$([0-9.,]+)/', $msg, $m)) {
            return ['key' => 'transfer_accepted', 'params' => ['name' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/(.+?) declined your transfer of \$([0-9.,]+)/', $msg, $m)) {
            return ['key' => 'transfer_declined', 'params' => ['name' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/(.+?) cancelled their transfer of \$([0-9.,]+)/', $msg, $m)) {
            return ['key' => 'transfer_cancelled', 'params' => ['name' => $m[1], 'amount' => $m[2]]];
        }
        if (preg_match('/Your pending transfer of \$([0-9.,]+) to (.+?) has expired/', $msg, $m)) {
            return ['key' => 'transfer_expired', 'params' => ['amount' => $m[1], 'name' => $m[2]]];
        }
        if (preg_match('/You received \$([0-9.,]+) from (.+?)\./', $msg, $m)) {
            return ['key' => 'transfer_received', 'params' => ['amount' => $m[1], 'name' => $m[2]]];
        }

        // KYC notifications
        if (preg_match('/Your account has been created at the (.+?) branch/', $msg, $m)) {
            return ['key' => 'kyc_account_created', 'params' => ['branch' => $m[1]]];
        }
        if (preg_match('/Your (.+?) was rejected: (.+?)\. Please re-upload/', $msg, $m)) {
            return ['key' => 'kyc_document_rejected', 'params' => ['type' => $m[1], 'reason' => $m[2]]];
        }
        if (preg_match('/Your identity has been verified and your account is now fully active/', $msg)) {
            return ['key' => 'kyc_identity_verified', 'params' => []];
        }
        if (preg_match('/Both your Passport and National ID have been verified/', $msg)) {
            return ['key' => 'kyc_both_verified', 'params' => []];
        }
        if (preg_match('/Your (.+?) has been verified\.(.*)/', $msg, $m)) {
            $extra = trim($m[2]);
            $key = 'kyc_document_verified';
            $suffix = '';
            if (str_contains($extra, 'Passport') && str_contains($extra, 'National ID')) {
                $suffix = 'kyc_both_needed';
            } elseif (str_contains($extra, 'Passport')) {
                $suffix = 'kyc_passport_needed';
            } elseif (str_contains($extra, 'National ID')) {
                $suffix = 'kyc_national_id_needed';
            }
            $result = __('notifications.' . $key, ['type' => $m[1]]);
            if ($suffix) {
                $result .= __('notifications.' . $suffix);
            }
            return ['key' => $key, 'params' => ['type' => $m[1]]]; // Will be handled specially
        }

        // Cash/ATM notifications
        if (preg_match('/You have successfully withdrawn \$([0-9.,]+)/', $msg, $m)) {
            return ['key' => 'cash_withdrawal', 'params' => ['amount' => $m[1]]];
        }
        if (preg_match('/A cash deposit of \$([0-9.,]+) has been added/', $msg, $m)) {
            return ['key' => 'cash_deposit', 'params' => ['amount' => $m[1]]];
        }
        if (preg_match('/A cash withdrawal of \$([0-9.,]+) was processed/', $msg, $m)) {
            return ['key' => 'branch_withdrawal', 'params' => ['amount' => $m[1]]];
        }
        if (preg_match('/A deposit of \$([0-9.,]+) was made to your account/', $msg, $m)) {
            return ['key' => 'branch_deposit', 'params' => ['amount' => $m[1], 'account' => '']];
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActionable($query)
    {
        return $query->where('is_read', false)->whereNotNull('data');
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Check if this notification requires user action (e.g., accept/decline transfer).
     */
    public function requiresAction(): bool
    {
        if ($this->is_read) return false;
        $data = $this->data ?? [];
        return isset($data['action']) && in_array($data['action'], [
            'transfer_accept_decline',
            'kyc_required',
        ]);
    }

    /**
     * Get the pending transfer ID if this is a transfer request notification.
     */
    public function getPendingTransferId(): ?int
    {
        $data = $this->data ?? [];
        return $data['pending_transfer_id'] ?? null;
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'transaction' => '#00d4ff',
            'security' => '#f97316',
            'promotion' => '#8b5cf6',
            'system' => '#6b7280',
            'transfer_request' => '#a855f7',
            default => '#3b82f6',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'success' => '✅',
            'warning' => '⚠️',
            'danger' => '❌',
            'transaction' => '💸',
            'security' => '🔐',
            'promotion' => '🎁',
            'system' => '⚙️',
            'transfer_request' => '📨',
            default => '🔔',
        };
    }

    /**
     * Create a transfer request notification for the receiver.
     */
    public static function transferRequest(PendingTransfer $transfer): self
    {
        return static::create([
            'user_id' => $transfer->receiver_user_id,
            'title' => 'Incoming Transfer Request',
            'message' => "{$transfer->senderUser->name} wants to send you \${$transfer->amount}. Accept or decline this transfer.",
            'type' => 'transfer_request',
            'icon' => '📨',
            'action_url' => route('transfers.pending'),
            'data' => [
                'action' => 'transfer_accept_decline',
                'pending_transfer_id' => $transfer->id,
                'sender_name' => $transfer->senderUser->name,
                'amount' => $transfer->amount,
            ],
        ]);
    }

    /**
     * Write a notification directly to a specific user's branch DB and HQ.
     * This ensures cross-branch delivery.
     */
    public static function notifyUserOnBranch(int $userId, array $data): void
    {
        $branch = DistributedDatabaseService::findUserBranchById($userId);

        // Write to user's branch DB
        if ($branch) {
            $branchConnection = DistributedDatabaseService::connectionForBranch($branch);
            try {
                DB::connection($branchConnection)->table('notifications')->insert(array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } catch (\Exception $e) {
                \Log::warning("Failed to write notification to branch {$branch}: {$e->getMessage()}");
            }
        }

        // Also write to HQ (ensures visibility for admins and as backup)
        try {
            DB::connection(DistributedDatabaseService::getHqConnection())->table('notifications')->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Exception $e) {
            \Log::warning("Failed to write notification to HQ: {$e->getMessage()}");
        }
    }

    /**
     * Notify about transfer acceptance.
     */
    public static function transferAccepted(PendingTransfer $transfer): void
    {
        $currency = Currency::where('code', $transfer->currency)->first();
        $symbol = $currency?->symbol ?? $transfer->currency;
        $decimals = $currency?->decimal_places ?? 2;
        $amountFormatted = $symbol . ' ' . number_format($transfer->amount, $decimals);

        $senderData = [
            'user_id' => $transfer->sender_user_id,
            'title' => 'Transfer Accepted ✅',
            'message' => "{$transfer->receiverUser->name} accepted your transfer of {$amountFormatted}.",
            'type' => 'success',
            'icon' => '✅',
            'action_url' => route('transactions.index'),
            'is_read' => false,
            'data' => json_encode(['pending_transfer_id' => $transfer->id]),
        ];

        $receiverData = [
            'user_id' => $transfer->receiver_user_id,
            'title' => 'Transfer Received 💰',
            'message' => "You received {$amountFormatted} from {$transfer->senderUser->name}.",
            'type' => 'transaction',
            'icon' => '💰',
            'action_url' => route('transactions.index'),
            'is_read' => false,
            'data' => json_encode(['pending_transfer_id' => $transfer->id]),
        ];

        // Write sender notification to sender's branch (cross-branch safe)
        static::notifyUserOnBranch($transfer->sender_user_id, $senderData);

        // Write receiver notification to current branch (they're already here)
        static::create($receiverData);
    }

    /**
     * Notify about transfer decline.
     */
    public static function transferDeclined(PendingTransfer $transfer): void
    {
        $currency = Currency::where('code', $transfer->currency)->first();
        $symbol = $currency?->symbol ?? $transfer->currency;
        $decimals = $currency?->decimal_places ?? 2;
        $amountFormatted = $symbol . ' ' . number_format($transfer->amount, $decimals);

        $senderData = [
            'user_id' => $transfer->sender_user_id,
            'title' => 'Transfer Declined',
            'message' => "{$transfer->receiverUser->name} declined your transfer of {$amountFormatted}. Funds have been released.",
            'type' => 'warning',
            'icon' => '↩️',
            'action_url' => route('transfers.pending'),
            'is_read' => false,
            'data' => json_encode(['pending_transfer_id' => $transfer->id]),
        ];

        // Write sender notification to sender's branch (cross-branch safe)
        static::notifyUserOnBranch($transfer->sender_user_id, $senderData);
    }

    /**
     * Notify about card activation.
     */
    public static function cardActivated(Card $card): void
    {
        static::create([
            'user_id' => $card->user_id,
            'title' => 'Card Activated! 🎉',
            'message' => "Your card ending in {$card->card_number_last4} has been activated and is ready to use.",
            'type' => 'success',
            'icon' => '💳',
            'action_url' => route('cards.index'),
        ]);
    }

    /**
     * Notify about loan rejection due to insufficient reserves.
     */
    public static function loanRejectedReserves(int $userId, float $amount): void
    {
        static::create([
            'user_id' => $userId,
            'title' => 'Loan Application Unavailable',
            'message' => "Your loan application for \${$amount} could not be processed at this time. The bank's lending capacity has been temporarily reached. Please try again later.",
            'type' => 'warning',
            'icon' => '🏦',
            'action_url' => route('loans.index'),
        ]);
    }
}
