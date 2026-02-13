<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RengiatEntry;
use App\Models\Subdit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_unit_cascades_related_entries(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $subdit = Subdit::factory()->create();
        $unit = Unit::factory()->create();

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subdit->id,
            'unit_id' => null,
        ]);

        $entry = RengiatEntry::factory()->create([
            'subdit_id' => $subdit->id,
            'unit_id' => $unit->id,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.units.destroy', $unit))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('units', [
            'id' => $unit->id,
        ]);

        $this->assertDatabaseMissing('rengiat_entries', [
            'id' => $entry->id,
        ]);
    }
}
