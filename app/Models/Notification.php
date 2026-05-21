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
        if (app()->getLocale() !== 'ckb') return $msg;

        $translated = __($msg);
        if ($translated !== $msg) return $translated;

        if (preg_match('/Your (.+?) (.+?) card has been activated and is ready to use\./', $msg, $matches)) {
            return "کارتەکەت لە جۆری {$matches[1]} {$matches[2]} چالاککراوە و ئامادەیە بۆ بەکارهێنان.";
        }
        if (preg_match('/Your card ending in (\w+) has been frozen\./', $msg, $matches)) {
            return "کارتەکەت کە کۆتایی دێت بە {$matches[1]} سڕکراوە.";
        }
        if (preg_match('/The PIN for your card ending in (\w+) has been changed successfully\./', $msg, $matches)) {
            return "پین کۆدی کارتەکەت کە کۆتایی دێت بە {$matches[1]} بە سەرکەوتوویی گۆڕدرا.";
        }
        if (preg_match('/Your (.+?) loan application for \$([0-9.,]+) has been submitted and is under review\./', $msg, $matches)) {
            return "داواکاری قەرزەکەت لە جۆری {$matches[1]} بە بڕی \${$matches[2]} نێردراوە و لە ژێر پێداچوونەوەدایە.";
        }
        if (preg_match('/Your transfer of \$([0-9.,]+) to (.+?) is pending acceptance\. Funds have been held\./', $msg, $matches)) {
            return "گواستنەوەکەت بە بڕی \${$matches[1]} بۆ {$matches[2]} هەڵواسراوە بۆ پەسەندکردن. پارەکە هێڵراوەتەوە.";
        }
        if (preg_match('/(.+?) cancelled their transfer of \$([0-9.,]+)\./', $msg, $matches)) {
            return "{$matches[1]} گواستنەوەکەی بە بڕی \${$matches[2]} هەڵوەشاندەوە.";
        }
        if (preg_match('/Your pending transfer of \$([0-9.,]+) to (.+?) has expired\. Funds have been released\./', $msg, $matches)) {
            return "گواستنەوە هەڵواسراوەکەت بە بڕی \${$matches[1]} بۆ {$matches[2]} بەسەرچووە. پارەکە ئازادکراوە.";
        }
        if (preg_match('/Your account has been created at the (.+?) branch\. To activate all features, please upload your identity documents\./', $msg, $matches)) {
            return "هەژمارەکەت لە لقی {$matches[1]} دروستکراوە. بۆ چالاککردنی هەموو تایبەتمەندییەکان، تکایە بەڵگەنامەکانی ناسنامەت باربکە.";
        }
        if (preg_match('/Your card ending in (\w+) has been frozen due to (\d+) incorrect PIN attempts\. Please contact support\./', $msg, $matches)) {
            return "کارتەکەت کە کۆتایی دێت بە {$matches[1]} سڕکراوە بەهۆی {$matches[2]} هەوڵی هەڵەی پین کۆد. تکایە پەیوەندی بە پاڵپشتییەوە بکە.";
        }
        if (preg_match('/Your (.+?) was rejected: (.+?)\. Please re-upload\./', $msg, $matches)) {
            return "بەڵگەنامەکەت ({$matches[1]}) ڕەتکرایەوە: {$matches[2]}. تکایە دووبارە باری بکەرەوە.";
        }
        if (preg_match('/Your (.+?) has been verified\.(.*)/', $msg, $matches)) {
            $extra = trim($matches[2]);
            $kurdishExtra = "";
            if ($extra == "Please also upload your Passport.") $kurdishExtra = " تکایە پاسپۆرتەکەشت باربکە.";
            if ($extra == "Please also upload your National ID.") $kurdishExtra = " تکایە پێناسەی نیشتیمانیشت باربکە.";
            if ($extra == "Please also upload your Passport. Please also upload your National ID.") $kurdishExtra = " تکایە پاسپۆرت و پێناسەی نیشتیمانیشت باربکە.";
            return "بەڵگەنامەکەت ({$matches[1]}) پەسەندکرا.{$kurdishExtra}";
        }
        if (preg_match('/Your identity has been verified and your account is now fully active\. All features are unlocked\./', $msg, $matches)) {
            return "ناسنامەکەت پەسەندکرا و هەژمارەکەت ئێستا بە تەواوی چالاکە. هەموو تایبەتمەندییەکان کراونەتەوە.";
        }
        if (preg_match('/Both your Passport and National ID have been verified\. Your account is now fully active!/', $msg, $matches)) {
            return "هەردوو پاسپۆرت و پێناسەی نیشتیمانیت پەسەندکراون. هەژمارەکەت ئێستا بە تەواوی چالاکە!";
        }
        if (preg_match('/Your (.+?) loan of \$([0-9.,]+) has been approved!/', $msg, $matches)) {
            return "قەرزەکەت لە جۆری {$matches[1]} بە بڕی \${$matches[2]} پەسەندکرا!";
        }
        if (preg_match('/Your (.+?) loan application has been rejected\. Reason: (.+)/', $msg, $matches)) {
            return "داواکاری قەرزەکەت لە جۆری {$matches[1]} ڕەتکرایەوە. هۆکار: {$matches[2]}";
        }
        if (preg_match('/(.+?) applied for a (.+?) loan of \$([0-9.,]+)\./', $msg, $matches)) {
            return "{$matches[1]} داواکاری پێشکەشکردووە بۆ قەرزێکی {$matches[2]} بە بڕی \${$matches[3]}.";
        }
        if (preg_match('/(.+?) uploaded a (.+?) for verification\./', $msg, $matches)) {
            return "{$matches[1]} بەڵگەنامەیەکی {$matches[2]}ی بارکردووە بۆ پەسەندکردن.";
        }
        if (preg_match('/You received \$([0-9.,]+) from (.+?)\./', $msg, $matches)) {
            return "بڕی \${$matches[1]} لەلایەن {$matches[2]}وە پێگەیشت.";
        }
        if (preg_match('/(.+?) accepted your transfer of \$([0-9.,]+)\./', $msg, $matches)) {
            return "{$matches[1]} گواستنەوەکەی بە بڕی \${$matches[2]} پەسەندکرد.";
        }
        if (preg_match('/(.+?) declined your transfer of \$([0-9.,]+)\. Funds have been released\./', $msg, $matches)) {
            return "{$matches[1]} گواستنەوەکەی بە بڕی \${$matches[2]} ڕەتکردەوە. پارەکە ئازادکراوە.";
        }
        if (preg_match('/(.+?) wants to send you \$([0-9.,]+)\. Accept or decline this transfer\./', $msg, $matches)) {
            return "{$matches[1]} دەیەوێت \${$matches[2]} بنێرێت بۆت. ئەم گواستنەوەیە پەسەند بکە یان ڕەتبکەرەوە.";
        }
        if (preg_match('/Your loan application for \$([0-9.,]+) could not be processed at this time\.(.*)/', $msg, $matches)) {
            return "داواکاری قەرزەکەت بە بڕی \${$matches[1]} لەم کاتەدا ناتوانرێت جێبەجێبکرێت. توانای قەرزدانی بانک بە کاتی گەیشتووەتە ئەوپەڕی. تکایە دواتر هەوڵبدەرەوە.";
        }
        if (preg_match('/Your card has been created but is inactive\.(.*)/', $msg, $matches)) {
            return "کارتەکەت دروستکراوە بەڵام ناچالاکە. تکایە پاسپۆرت و پێناسەی نیشتیمانیت باربکە بۆ چالاککردنی.";
        }
        if (preg_match('/Your card ending in (\w+) has been activated and is ready to use\./', $msg, $matches)) {
            return "کارتەکەت کە کۆتایی دێت بە {$matches[1]} چالاککراوە و ئامادەیە بۆ بەکارهێنان.";
        }

        if (preg_match('/You have successfully withdrawn \$([0-9.,]+) from your account\./', $msg, $matches)) {
            return "بە سەرکەوتوویی بڕی \${$matches[1]} ت لە هەژمارەکەت ڕاکێشا.";
        }
        if (preg_match('/A cash deposit of \$([0-9.,]+) has been added to your account\./', $msg, $matches)) {
            return "بڕی \${$matches[1]} وەک کاش خرایە سەر هەژمارەکەت.";
        }
        if (preg_match('/A cash withdrawal of \$([0-9.,]+) was processed at the branch\./', $msg, $matches)) {
            return "بڕی \${$matches[1]} کاش لە لقەکەمان ڕاکێشرا.";
        }

        return $msg;
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
