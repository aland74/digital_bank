<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'title'       => $this->title,
            'message'     => $this->message,
            'type'        => $this->type,
            'icon'        => $this->icon,
            'action_url'  => $this->action_url,
            'is_read'     => $this->is_read,
            'read_at'     => $this->read_at?->toIso8601String(),
            'data'        => $this->data,
            'type_color'  => $this->type_color,
            'type_icon'   => $this->type_icon,
            'created_at'  => $this->created_at?->toIso8601String(),
            'time_ago'    => $this->created_at?->diffForHumans(),
        ];
    }
}
