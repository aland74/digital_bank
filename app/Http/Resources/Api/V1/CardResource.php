<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'account_id'                => $this->account_id,
            'user_id'                   => $this->user_id,
            'card_type'                 => $this->card_type,
            'card_brand'                => $this->card_brand,
            'last4'                     => $this->card_number_last4,
            'cardholder_name'           => $this->cardholder_name,
            'expiry'                    => $this->expiry_date,
            'status'                    => $this->status,
            'daily_limit'               => (float) $this->daily_limit,
            'monthly_limit'             => (float) $this->monthly_limit,
            'daily_spent'               => (float) $this->daily_spent,
            'monthly_spent'             => (float) $this->monthly_spent,
            'is_contactless'            => $this->is_contactless,
            'is_online_enabled'         => $this->is_online_enabled,
            'is_international_enabled'  => $this->is_international_enabled,
            'activated_at'              => $this->activated_at?->toIso8601String(),
            'last_used_at'              => $this->last_used_at?->toIso8601String(),
            'account'                   => new AccountResource($this->whenLoaded('account')),
        ];
    }
}
