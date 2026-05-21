<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class PendingTransfer extends Model
{
    use SyncsWithHQ;

    protected $fillable = [
        'reference_number', 'sender_account_id', 'receiver_account_id',
        'sender_user_id', 'receiver_user_id', 'amount', 'currency',
        'exchange_rate', 'description', 'status', 'expires_at', 'accepted_at',
        'declined_at', 'cancelled_at', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function senderAccount()
    {
        return $this->belongsTo(Account::class, 'sender_account_id');
    }

    public function receiverAccount()
    {
        return $this->belongsTo(Account::class, 'receiver_account_id');
    }

    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function receiverUser()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_user_id', $userId)
              ->orWhere('receiver_user_id', $userId);
        });
    }

    public function scopeIncoming($query, int $userId)
    {
        return $query->where('receiver_user_id', $userId);
    }

    public function scopeOutgoing($query, int $userId)
    {
        return $query->where('sender_user_id', $userId);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')->where('expires_at', '<=', now());
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    public function getTimeRemainingAttribute(): string
    {
        if (!$this->isPending()) return 'Expired';
        return $this->expires_at->diffForHumans(['parts' => 2]);
    }

    /**
     * Generate a unique reference number for pending transfers.
     */
    public static function generateReference(): string
    {
        return 'PTX' . strtoupper(bin2hex(random_bytes(10)));
    }
}
