<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Account extends Model
{
    use HasFactory, SoftDeletes, SyncsWithHQ;

    protected $fillable = [
        'user_id', 'account_number', 'account_name', 'account_type',
        'currency', 'balance', 'available_balance', 'hold_amount',
        'status', 'is_primary', 'daily_transfer_limit', 'monthly_transfer_limit',
        'interest_rate', 'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'hold_amount' => 'decimal:2',
            'daily_transfer_limit' => 'decimal:2',
            'monthly_transfer_limit' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'is_primary' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function scheduledPayments()
    {
        return $this->hasMany(ScheduledPayment::class);
    }

    public function billPayments()
    {
        return $this->hasMany(BillPayment::class);
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function currencyModel(): ?Currency
    {
        return Currency::getByCode($this->currency);
    }

    public function getFormattedBalanceAttribute(): string
    {
        $currency = $this->currencyModel();
        if ($currency) {
            return $currency->symbol . ' ' . number_format($this->balance, $currency->decimal_places);
        }
        return $this->currency . ' ' . number_format($this->balance, 2);
    }

    public function getAccountTypeIconAttribute(): string
    {
        return match($this->account_type) {
            'savings' => 'piggy-bank',
            'checking' => 'wallet',
            'business' => 'briefcase',
            'fixed_deposit' => 'lock',
            default => 'credit-card',
        };
    }

    public static function generateAccountNumber(): string
    {
        do {
            $number = 'NXB' . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            // Check HQ for global uniqueness across all branches
            $exists = \Illuminate\Support\Facades\DB::connection(\App\Services\DistributedDatabaseService::getHqConnection())
                ->table('accounts')
                ->where('account_number', $number)
                ->exists();
        } while ($exists || static::where('account_number', $number)->exists());
        return $number;
    }
}
