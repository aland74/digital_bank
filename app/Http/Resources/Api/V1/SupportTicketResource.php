<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'ticket_number'   => $this->ticket_number,
            'subject'         => $this->subject,
            'message'         => $this->message,
            'category'        => $this->category,
            'priority'        => $this->priority,
            'status'          => $this->status,
            'assigned_to'     => $this->assigned_to,
            'resolved_at'     => $this->resolved_at?->toIso8601String(),
            'priority_color'  => $this->priority_color,
            'created_at'      => $this->created_at?->toIso8601String(),
            'replies'         => SupportTicketReplyResource::collection($this->whenLoaded('replies')),
            'assignee'        => new UserResource($this->whenLoaded('assignee')),
        ];
    }
}
