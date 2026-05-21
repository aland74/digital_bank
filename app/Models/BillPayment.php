<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use HasFactory, SyncsWithHQ;

    protected $fillable = [
        'user_id', 'account_id', 'reference_number', 'biller_name',
        'biller_category', 'bill_number', 'amount', 'currency', 'status',
        'transaction_reference', 'paid_at', 'due_date', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'due_date' => 'datetime',
            'metadata' => 'array',
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

    public function getCategoryIconAttribute(): string
    {
        return match($this->biller_category) {
            'electricity' => '⚡',
            'water' => '💧',
            'gas' => '🔥',
            'internet' => '🌐',
            'phone' => '📱',
            'insurance' => '🛡️',
            'tax' => '🏛️',
            'education' => '🎓',
            default => '📄',
        };
    }

    public static function generateReference(): string
    {
        return 'BIL' . strtoupper(bin2hex(random_bytes(10)));
    }
}
