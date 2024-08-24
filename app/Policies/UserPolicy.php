<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can permanently delete the model.
     */
    public function showAdmin(User $user): bool
    {
        return $user->admin;
    }
}
