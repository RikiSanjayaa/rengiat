<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\RengiatEntry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class RengiatEntryObserver
{
    /**
     * @var array<int, string>
     */
    private array $auditableFields = [
        'subdit_id',
        'unit_id',
        'entry_date',
        'time_start',
        'description',
        'case_number',
        'created_by',
        'updated_by',
    ];

    public function created(RengiatEntry $rengiatEntry): void
    {
        AuditLog::create([
            'actor_user_id' => $this->resolveActorId($rengiatEntry, AuditLogAction::Created),
            'action' => AuditLogAction::Created,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $rengiatEntry->id,
            'old_values' => null,
            'new_values' => Arr::only($rengiatEntry->auditPayload(), $this->auditableFields),
            'created_at' => now(),
        ]);
    }

    public function updated(RengiatEntry $rengiatEntry): void
    {
        $changes = Arr::only($rengiatEntry->getChanges(), $this->auditableFields);

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $rengiatEntry->getOriginal($field);
        }

        AuditLog::create([
            'actor_user_id' => $this->resolveActorId($rengiatEntry, AuditLogAction::Updated),
            'action' => AuditLogAction::Updated,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $rengiatEntry->id,
            'old_values' => $oldValues,
            'new_values' => $changes,
            'created_at' => now(),
        ]);
    }

    public function deleted(RengiatEntry $rengiatEntry): void
    {
        AuditLog::create([
            'actor_user_id' => $this->resolveActorId($rengiatEntry, AuditLogAction::Deleted),
            'action' => AuditLogAction::Deleted,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $rengiatEntry->id,
            'old_values' => Arr::only($rengiatEntry->auditPayload(), $this->auditableFields),
            'new_values' => null,
            'created_at' => now(),
        ]);
    }

    private function resolveActorId(RengiatEntry $rengiatEntry, AuditLogAction $action): int
    {
        $actorId = Auth::id();

        if ($actorId !== null) {
            return $actorId;
        }

        return match ($action) {
            AuditLogAction::Created => $rengiatEntry->created_by,
            AuditLogAction::Updated => $rengiatEntry->updated_by ?? $rengiatEntry->created_by,
            AuditLogAction::Deleted => $rengiatEntry->updated_by ?? $rengiatEntry->created_by,
        };
    }
}
