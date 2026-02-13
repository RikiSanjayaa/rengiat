<?php

namespace App\Policies;

use App\Models\RengiatEntry;
use App\Models\User;

class RengiatEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RengiatEntry $rengiatEntry): bool
    {
        return true;
    }

    public function create(User $user, ?int $targetSubditId = null): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if (! $user->isOperator() || $user->subdit_id === null || $targetSubditId === null) {
            return false;
        }

        return (int) $targetSubditId === (int) $user->subdit_id;
    }

    public function update(User $user, RengiatEntry $rengiatEntry): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if (! $user->isOperator()) {
            return false;
        }

        if ($user->subdit_id === null || $rengiatEntry->subdit_id === null) {
            return false;
        }

        return (int) $rengiatEntry->subdit_id === (int) $user->subdit_id;
    }

    public function delete(User $user, RengiatEntry $rengiatEntry): bool
    {
        return $this->update($user, $rengiatEntry);
    }
}
