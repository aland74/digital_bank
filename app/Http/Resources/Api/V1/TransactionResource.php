<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'account_id'            => $this->account_id,
            'reference_number'      => $this->reference_number,
            'type'                  => $this->type,
            'amount'                => (float) $this->amount,
            'currency'              => $this->currency,
            'balance_before'        => (float) $this->balance_before,
            'balance_after'         => (float) $this->balance_after,
            'status'                => $this->status,
            'description'           => $this->description,
            'recipient_account_id'  => $this->recipient_account_id,
            'recipient_name'        => $this->recipient_name,
            'recipient_bank'        => $this->recipient_bank,
            'recipient_account_number' => $this->recipient_account_number,
            'channel'               => $this->channel,
            'ip_address'            => $this->ip_address,
            'metadata'              => $this->metadata,
            'completed_at'          => $this->completed_at?->toIso8601String(),
            'created_at'            => $this->created_at?->toIso8601String(),
            'account'               => new AccountResource($this->whenLoaded('account')),
        ];
    }
}
