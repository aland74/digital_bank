<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'reference_number'    => $this->reference_number,
            'sender_account_id'   => $this->sender_account_id,
            'receiver_account_id' => $this->receiver_account_id,
            'sender_user_id'      => $this->sender_user_id,
            'receiver_user_id'    => $this->receiver_user_id,
            'amount'              => (float) $this->amount,
            'currency'            => $this->currency,
            'exchange_rate'       => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'description'         => $this->description,
            'status'              => $this->status,
            'expires_at'          => $this->expires_at?->toIso8601String(),
            'accepted_at'         => $this->accepted_at?->toIso8601String(),
            'declined_at'         => $this->declined_at?->toIso8601String(),
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),
            'time_remaining'      => $this->time_remaining,
            'senderAccount'       => new AccountResource($this->whenLoaded('senderAccount')),
            'receiverAccount'     => new AccountResource($this->whenLoaded('receiverAccount')),
            'senderUser'          => new UserResource($this->whenLoaded('senderUser')),
            'receiverUser'        => new UserResource($this->whenLoaded('receiverUser')),
        ];
    }
}
