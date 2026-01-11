<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        if ($actor->role === 'administrator') {
            return true;
        }

        if ($actor->role === 'manager') {
            return $target->role === 'user';
        }

        return $actor->is($target);
    }
}

