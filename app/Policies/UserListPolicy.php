<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserList;

class UserListPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserList $userList): bool
    {
        return $user->id === $userList->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserList $userList): bool
    {
        return $user->id === $userList->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserList $userList): bool
    {
        return $user->id === $userList->user_id;
    }
}
