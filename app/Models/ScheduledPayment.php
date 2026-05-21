<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledPayment extends Model
{
    use HasFactory, SyncsWithHQ;

    protected $fillable = [
        'account_id', 'beneficiary_id', 'user_id', 'name', 'amount',
        'currency', 'frequency', 'start_date', 'end_date',
        'next_execution_date', 'last_executed_at', 'execution_count',
        'max_executions', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_execution_date' => 'date',
            'last_executed_at' => 'date',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueToday($query)
    {
        return $query->where('next_execution_date', '<=', now()->toDateString())
                     ->where('status', 'active');
    }
}
