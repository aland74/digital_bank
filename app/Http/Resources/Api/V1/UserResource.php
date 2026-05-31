<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name'                  => $this->name,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'national_id'           => $this->national_id,
            'date_of_birth'         => $this->date_of_birth?->toDateString(),
            'address_line_1'        => $this->address_line_1,
            'address_line_2'        => $this->address_line_2,
            'city'                  => $this->city,
            'state'                 => $this->state,
            'country'               => $this->country,
            'postal_code'           => $this->postal_code,
            'full_address'          => $this->full_address,
            'branch'                => $this->branch,
            'branch_display_name'   => $this->branch_display_name,
            'role'                  => $this->role,
            'status'                => $this->status,
            'avatar'                => $this->avatar,
            'two_factor_enabled'    => $this->two_factor_enabled,
            'last_login_at'         => $this->last_login_at?->toIso8601String(),
            'email_verified_at'     => $this->email_verified_at?->toIso8601String(),
            'kyc_verified'          => $this->isKycVerified(),
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
