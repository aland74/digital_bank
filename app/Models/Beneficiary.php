<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes, SyncsWithHQ;

    protected $fillable = [
        'user_id', 'name', 'nickname', 'bank_name', 'account_number',
        'routing_number', 'swift_code', 'iban', 'type', 'currency',
        'is_favorite', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scheduledPayments()
    {
        return $this->hasMany(ScheduledPayment::class);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->name;
    }

    public function getMaskedAccountAttribute(): string
    {
        return '****' . substr($this->account_number, -4);
    }
}
