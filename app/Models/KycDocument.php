<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    use HasFactory, SyncsWithHQ;

    protected $fillable = [
        'user_id', 'document_type', 'document_number', 'file_path',
        'file_name', 'mime_type', 'file_size', 'status', 'verified_by',
        'verified_at', 'rejection_reason', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expiry_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'passport' => 'Passport',
            'national_id' => 'National ID',
            'drivers_license' => "Driver's License",
            'utility_bill' => 'Utility Bill',
            'bank_statement' => 'Bank Statement',
            'tax_return' => 'Tax Return',
            default => ucfirst($this->document_type),
        };
    }
}
