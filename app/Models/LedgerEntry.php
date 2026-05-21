<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use SyncsWithHQ;
    const UPDATED_AT = null; // Immutable ledger, no updated_at

    protected $fillable = [
        'transaction_reference',
        'account_id',
        'type',
        'amount',
        'currency',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
