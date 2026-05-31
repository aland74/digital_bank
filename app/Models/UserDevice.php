<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use SyncsWithHQ;

    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token',
        'platform',
        'ip_address',
        'user_agent',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
