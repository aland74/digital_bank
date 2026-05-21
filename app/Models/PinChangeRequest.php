<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class PinChangeRequest extends Model
{
    use SyncsWithHQ;

    protected $fillable = [
        'user_id', 'card_id', 'reason', 'description',
        'status', 'processed_by', 'processed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getReasonLabelAttribute(): string
    {
        return match($this->reason) {
            'forgotten' => 'Forgot PIN',
            'stolen' => 'PIN Stolen / Compromised',
            'compromised' => 'Security Concern',
            'other' => 'Other',
            default => ucfirst($this->reason),
        };
    }

    public function getReasonIconAttribute(): string
    {
        return match($this->reason) {
            'forgotten' => '🔑',
            'stolen' => '🚨',
            'compromised' => '⚠️',
            'other' => '📝',
            default => '🔐',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-info',
        };
    }
}
