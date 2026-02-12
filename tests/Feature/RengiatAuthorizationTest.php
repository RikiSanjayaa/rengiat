<?php

namespace Tests\Feature;

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Models\RengiatEntry;
use App\Models\Unit;
use App\Models\User;
use App\Services\RengiatPdfExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class RengiatAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_cannot_edit_other_units_entries(): void
    {
        $unitA = Unit::factory()->create();
        $unitB = Unit::factory()->create();

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'unit_id' => $unitA->id,
        ]);

        $entry = RengiatEntry::factory()->create([
            'unit_id' => $unitB->id,
        ]);

        $response = $this->actingAs($operator)->put(route('entries.update', $entry), [
            'entry_date' => now()->toDateString(),
            'time_start' => '09:00',
            'description' => 'Tidak boleh diubah',
            'unit_id' => $unitB->id,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_create_update_or_delete_entries(): void
    {
        $unit = Unit::factory()->create();
        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $entry = RengiatEntry::factory()->create([
            'unit_id' => $unit->id,
        ]);

        $this->actingAs($viewer)->post(route('entries.store'), [
            'entry_date' => now()->toDateString(),
            'description' => 'Tidak boleh dibuat',
            'unit_id' => $unit->id,
        ])->assertForbidden();

        $this->actingAs($viewer)->put(route('entries.update', $entry), [
            'entry_date' => now()->toDateString(),
            'description' => 'Tidak boleh diubah',
            'unit_id' => $unit->id,
        ])->assertForbidden();

        $this->actingAs($viewer)->delete(route('entries.destroy', $entry))
            ->assertForbidden();
    }

    public function test_admin_can_export_report(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'unit_id' => null,
        ]);

        Unit::factory()->create();

        $this->mock(RengiatPdfExporter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('download')
                ->once()
                ->andReturn(response('pdf-content', 200, [
                    'Content-Type' => 'application/pdf',
                ]));
        });

        $response = $this->actingAs($admin)->get(route('reports.export', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_audit_logs_created_on_create_update_delete(): void
    {
        $unit = Unit::factory()->create();
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'unit_id' => $unit->id,
        ]);

        $this->actingAs($operator)->post(route('entries.store'), [
            'entry_date' => now()->toDateString(),
            'time_start' => '08:30',
            'description' => 'Aktivitas awal',
            'unit_id' => $unit->id,
        ])->assertSessionDoesntHaveErrors();

        $entry = RengiatEntry::query()->latest('id')->firstOrFail();

        $this->actingAs($operator)->put(route('entries.update', $entry), [
            'entry_date' => now()->toDateString(),
            'time_start' => '09:30',
            'description' => 'Aktivitas diubah',
            'unit_id' => $unit->id,
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($operator)->delete(route('entries.destroy', $entry))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::Created->value,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $entry->id,
            'actor_user_id' => $operator->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::Updated->value,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $entry->id,
            'actor_user_id' => $operator->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::Deleted->value,
            'auditable_type' => 'rengiat_entry',
            'auditable_id' => $entry->id,
            'actor_user_id' => $operator->id,
        ]);
    }
}
