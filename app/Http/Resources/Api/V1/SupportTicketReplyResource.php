<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketReplyResource extends JsonResource
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
            'ticket_id'       => $this->ticket_id,
            'user_id'         => $this->user_id,
            'message'         => $this->message,
            'is_staff_reply'  => $this->is_staff_reply,
            'is_internal'     => $this->is_internal,
            'attachment_path' => $this->attachment_path,
            'attachment_url'  => $this->attachment_url,
            'created_at'      => $this->created_at?->toIso8601String(),
            'user'            => new UserResource($this->whenLoaded('user')),
        ];
    }
}
