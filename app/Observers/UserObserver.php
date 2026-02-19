<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * @var array<int, string>
     */
    private array $auditableFields = [
        'name',
        'username',
        'role',
        'subdit_id',
        'unit_id',
    ];

    public function created(User $user): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Created,
            'auditable_type' => 'user',
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => Arr::only($user->toArray(), $this->auditableFields),
            'created_at' => now(),
        ]);
    }

    public function updated(User $user): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        $changes = Arr::only($user->getChanges(), $this->auditableFields);

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $user->getOriginal($field);
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Updated,
            'auditable_type' => 'user',
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $changes,
            'created_at' => now(),
        ]);
    }

    public function deleted(User $user): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Deleted,
            'auditable_type' => 'user',
            'auditable_id' => $user->id,
            'old_values' => Arr::only($user->toArray(), $this->auditableFields),
            'new_values' => null,
            'created_at' => now(),
        ]);
    }
}
