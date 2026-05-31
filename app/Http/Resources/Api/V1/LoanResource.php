<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
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
            'account_id'            => $this->account_id,
            'loan_number'           => $this->loan_number,
            'loan_type'             => $this->loan_type,
            'amount'                => (float) $this->amount,
            'interest_rate'         => (float) $this->interest_rate,
            'term_months'           => $this->term_months,
            'monthly_payment'       => (float) $this->monthly_payment,
            'total_interest'        => (float) $this->total_interest,
            'total_paid'            => (float) $this->total_paid,
            'remaining_balance'     => (float) $this->remaining_balance,
            'status'                => $this->status,
            'purpose'               => $this->purpose,
            'collateral_value'      => (float) $this->collateral_value,
            'collateral_description' => $this->collateral_description,
            'approved_by'           => $this->approved_by,
            'applied_at'            => $this->applied_at?->toIso8601String(),
            'approved_at'           => $this->approved_at?->toIso8601String(),
            'disbursed_at'          => $this->disbursed_at?->toIso8601String(),
            'maturity_date'         => $this->maturity_date?->toIso8601String(),
            'rejection_reason'      => $this->rejection_reason,
            'progress_percentage'   => $this->progress_percentage,
            'repayments'            => LoanRepaymentResource::collection($this->whenLoaded('repayments')),
            'account'               => new AccountResource($this->whenLoaded('account')),
        ];
    }
}
