<?php

namespace App\Policies;

use App\Models\RengiatEntry;
use App\Models\Unit;
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

    public function create(User $user, ?int $targetUnitId = null): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        $operatorSubditId = $this->resolveOperatorSubditId($user);

        if (! $user->isOperator() || $operatorSubditId === null || $targetUnitId === null) {
            return false;
        }

        $targetSubditId = Unit::query()
            ->whereKey($targetUnitId)
            ->value('subdit_id');

        return $targetSubditId !== null && (int) $targetSubditId === $operatorSubditId;
    }

    public function update(User $user, RengiatEntry $rengiatEntry): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if (! $user->isOperator()) {
            return false;
        }

        $operatorSubditId = $this->resolveOperatorSubditId($user);

        if ($operatorSubditId === null) {
            return false;
        }

        $entrySubditId = $rengiatEntry->unit()->value('subdit_id');

        return $entrySubditId !== null && (int) $entrySubditId === $operatorSubditId;
    }

    public function delete(User $user, RengiatEntry $rengiatEntry): bool
    {
        return $this->update($user, $rengiatEntry);
    }

    private function resolveOperatorSubditId(User $user): ?int
    {
        if ($user->subdit_id !== null) {
            return (int) $user->subdit_id;
        }

        if ($user->unit_id === null) {
            return null;
        }

        $fallbackSubditId = Unit::query()
            ->whereKey($user->unit_id)
            ->value('subdit_id');

        return $fallbackSubditId !== null ? (int) $fallbackSubditId : null;
    }
}
