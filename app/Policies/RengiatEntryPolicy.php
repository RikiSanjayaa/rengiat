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
        if ($user->isAdminLike()) {
            return true;
        }

        if ($user->isOperator()) {
            return $user->unit_id === $rengiatEntry->unit_id;
        }

        return $user->isViewer();
    }

    public function create(User $user, ?int $targetUnitId = null): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if (! $user->isOperator() || $user->unit_id === null) {
            return false;
        }

        return $targetUnitId === null || $targetUnitId === $user->unit_id;
    }

    public function update(User $user, RengiatEntry $rengiatEntry): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        return $user->isOperator() && $user->unit_id === $rengiatEntry->unit_id;
    }

    public function delete(User $user, RengiatEntry $rengiatEntry): bool
    {
        return $this->update($user, $rengiatEntry);
    }
}
