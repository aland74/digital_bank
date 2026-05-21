<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return $user->id === $beneficiary->user_id;
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return $user->id === $beneficiary->user_id;
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->id === $beneficiary->user_id;
    }
}
