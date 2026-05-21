<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use SyncsWithHQ;

    protected $fillable = [
        'user_id', 'ticket_number', 'subject', 'message', 'category',
        'priority', 'status', 'assigned_to', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }

    public function publicReplies()
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->where('is_internal', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'awaiting_response']);
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => '#ef4444',
            'high' => '#f97316',
            'medium' => '#f59e0b',
            'low' => '#10b981',
            default => '#6b7280',
        };
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT' . str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (static::where('ticket_number', $number)->exists());
        return $number;
    }
}
