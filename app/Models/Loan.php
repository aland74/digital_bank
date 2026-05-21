<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes, SyncsWithHQ;

    protected $fillable = [
        'user_id', 'account_id', 'loan_number', 'loan_type', 'amount',
        'interest_rate', 'term_months', 'monthly_payment', 'total_interest',
        'total_paid', 'remaining_balance', 'status', 'purpose',
        'collateral_value', 'collateral_description', 'approved_by',
        'applied_at', 'approved_at', 'disbursed_at', 'maturity_date',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'monthly_payment' => 'decimal:2',
            'total_interest' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'collateral_value' => 'decimal:2',
            'applied_at' => 'datetime',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'maturity_date' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->amount <= 0) return 0;
        return round(($this->total_paid / ($this->amount + $this->total_interest)) * 100, 1);
    }

    public static function generateLoanNumber(): string
    {
        do {
            $number = 'LN' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (static::where('loan_number', $number)->exists());
        return $number;
    }
}
