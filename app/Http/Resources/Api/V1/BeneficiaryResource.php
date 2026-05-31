<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
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
            'name'            => $this->name,
            'nickname'        => $this->nickname,
            'bank_name'       => $this->bank_name,
            'account_number'  => $this->account_number,
            'routing_number'  => $this->routing_number,
            'swift_code'      => $this->swift_code,
            'iban'            => $this->iban,
            'type'            => $this->type,
            'currency'        => $this->currency,
            'is_favorite'     => $this->is_favorite,
            'is_verified'     => $this->is_verified,
            'display_name'    => $this->display_name,
            'masked_account'  => $this->masked_account,
        ];
    }
}
