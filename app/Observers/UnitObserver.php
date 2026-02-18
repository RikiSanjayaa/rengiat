<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\Unit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UnitObserver
{
    /**
     * @var array<int, string>
     */
    private array $auditableFields = [
        'name',
        'order_index',
        'active',
    ];

    public function created(Unit $unit): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Created,
            'auditable_type' => 'unit',
            'auditable_id' => $unit->id,
            'old_values' => null,
            'new_values' => Arr::only($unit->toArray(), $this->auditableFields),
            'created_at' => now(),
        ]);
    }

    public function updated(Unit $unit): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        $changes = Arr::only($unit->getChanges(), $this->auditableFields);

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $unit->getOriginal($field);
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Updated,
            'auditable_type' => 'unit',
            'auditable_id' => $unit->id,
            'old_values' => $oldValues,
            'new_values' => $changes,
            'created_at' => now(),
        ]);
    }

    public function deleted(Unit $unit): void
    {
        $actorId = Auth::id();

        if ($actorId === null) {
            return;
        }

        AuditLog::create([
            'actor_user_id' => $actorId,
            'action' => AuditLogAction::Deleted,
            'auditable_type' => 'unit',
            'auditable_id' => $unit->id,
            'old_values' => Arr::only($unit->toArray(), $this->auditableFields),
            'new_values' => null,
            'created_at' => now(),
        ]);
    }
}
