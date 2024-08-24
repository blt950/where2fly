<?php

namespace App\Policies;

use App\Models\Scenery;
use App\Models\User;

class SceneryPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Scenery $scenery): bool
    {
        return $user->admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Scenery $scenery): bool
    {
        return $user->admin;
    }
}
