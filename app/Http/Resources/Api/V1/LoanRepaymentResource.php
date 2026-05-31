<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRepaymentResource extends JsonResource
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
            'loan_id'               => $this->loan_id,
            'installment_number'    => $this->installment_number,
            'amount'                => (float) $this->amount,
            'principal'             => (float) $this->principal,
            'interest'              => (float) $this->interest,
            'penalty'               => (float) $this->penalty,
            'remaining_balance'     => (float) $this->remaining_balance,
            'due_date'              => $this->due_date?->toDateString(),
            'paid_at'               => $this->paid_at?->toIso8601String(),
            'status'                => $this->status,
            'transaction_reference' => $this->transaction_reference,
        ];
    }
}
