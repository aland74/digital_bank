<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'account_number'        => $this->account_number,
            'account_name'          => $this->account_name,
            'account_type'          => $this->account_type,
            'currency'              => $this->currency,
            'balance'               => (float) $this->balance,
            'available_balance'     => (float) $this->available_balance,
            'hold_amount'           => (float) $this->hold_amount,
            'status'                => $this->status,
            'is_primary'            => $this->is_primary,
            'daily_transfer_limit'  => (float) $this->daily_transfer_limit,
            'monthly_transfer_limit' => (float) $this->monthly_transfer_limit,
            'interest_rate'         => (float) $this->interest_rate,
            'opened_at'             => $this->opened_at?->toIso8601String(),
            'user'                  => new UserResource($this->whenLoaded('user')),
        ];
    }
}
