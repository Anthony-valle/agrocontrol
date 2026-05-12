<?php

namespace App\Policies;

use App\Models\Cultivo;
use App\Models\User;

class CultivoPolicy
{
    public function view(User $user, Cultivo $cultivo): bool
    {
        return $user->isSuperUser() || $cultivo->created_by === $user->id;
    }

    public function update(User $user, Cultivo $cultivo): bool
    {
        return $user->isSuperUser() || $cultivo->created_by === $user->id;
    }

    public function delete(User $user, Cultivo $cultivo): bool
    {
        return $user->isSuperUser();
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }
}