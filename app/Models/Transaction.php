<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Transaction extends Model
{
    use HasFactory, SyncsWithHQ;

    protected $fillable = [
        'account_id', 'reference_number', 'type', 'amount', 'currency',
        'balance_before', 'balance_after', 'status', 'description',
        'recipient_account_id', 'recipient_name', 'recipient_bank',
        'recipient_account_number', 'channel', 'ip_address', 'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function recipientAccount()
    {
        return $this->belongsTo(Account::class, 'recipient_account_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return in_array($this->type, ['deposit', 'transfer_in', 'refund', 'interest', 'loan_disbursement']);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['withdrawal', 'transfer_out', 'payment', 'fee', 'loan_repayment']);
    }

    public function getCurrencyModel(): ?Currency
    {
        return Cache::remember("currency_{$this->currency}", 3600, function () {
            return Currency::where('code', $this->currency)->first();
        });
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isCredit() ? '+' : '-';
        $currency = $this->getCurrencyModel();
        if ($currency) {
            return $prefix . $currency->symbol . ' ' . number_format($this->amount, $currency->decimal_places);
        }
        return $prefix . $this->currency . ' ' . number_format($this->amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'cancelled' => 'gray',
            'reversed' => 'orange',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'deposit' => 'arrow-down-circle',
            'withdrawal' => 'arrow-up-circle',
            'transfer_in' => 'arrow-down-left',
            'transfer_out' => 'arrow-up-right',
            'payment' => 'shopping-cart',
            'refund' => 'refresh-cw',
            'fee' => 'percent',
            'interest' => 'trending-up',
            'loan_disbursement' => 'dollar-sign',
            'loan_repayment' => 'credit-card',
            default => 'activity',
        };
    }

    public static function generateReference(): string
    {
        return 'TXN' . strtoupper(bin2hex(random_bytes(10)));
    }
}
