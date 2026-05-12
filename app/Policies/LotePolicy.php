<?php

namespace App\Policies;

use App\Models\Lote;
use App\Models\User;

class LotePolicy
{
    public function view(User $user, Lote $lote): bool
    {
        return $user->isSuperUser() || $lote->created_by === $user->id;
    }

    public function update(User $user, Lote $lote): bool
    {
        return $user->isSuperUser() || $lote->created_by === $user->id;
    }

    public function delete(User $user, Lote $lote): bool
    {
        return $user->isSuperUser();
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }
}