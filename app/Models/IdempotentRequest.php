<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotentRequest extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'path',
        'method',
        'response_code',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
        ];
    }
}
