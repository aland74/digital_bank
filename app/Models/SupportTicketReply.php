<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    use SyncsWithHQ;

    protected $fillable = [
        'ticket_id', 'user_id', 'message', 'is_staff_reply', 'is_internal', 'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'is_staff_reply' => 'boolean',
            'is_internal' => 'boolean',
        ];
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
