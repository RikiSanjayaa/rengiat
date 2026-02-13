<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RengiatEntry;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportGeneratorPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_date_entries_are_shown_when_end_date_is_missing(): void
    {
        $date = now()->toDateString();
        $unit = Unit::factory()->create([
            'name' => 'UNIT 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $creator = User::factory()->create([
            'role' => UserRole::Operator,
            'unit_id' => $unit->id,
        ]);

        $entry = RengiatEntry::query()->create([
            'unit_id' => $unit->id,
            'entry_date' => $date,
            'time_start' => '09:00',
            'description' => 'Aktivitas pada tanggal mulai',
            'case_number' => null,
            'created_by' => $creator->id,
            'updated_by' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('reports.index', [
            'start_date' => $date,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('report.days.0.date', $date)
            ->where('report.days.0.columns.0.entries.0.id', $entry->id)
        );
    }

    public function test_start_date_entries_are_shown_when_end_date_equals_start_date(): void
    {
        $date = now()->subDay()->toDateString();
        $unit = Unit::factory()->create([
            'name' => 'UNIT 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $creator = User::factory()->create([
            'role' => UserRole::Operator,
            'unit_id' => $unit->id,
        ]);

        $entry = RengiatEntry::query()->create([
            'unit_id' => $unit->id,
            'entry_date' => $date,
            'time_start' => '10:30',
            'description' => 'Aktivitas dengan tanggal akhir sama',
            'case_number' => null,
            'created_by' => $creator->id,
            'updated_by' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('reports.index', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('report.days.0.date', $date)
            ->where('report.days.0.columns.0.entries.0.id', $entry->id)
        );
    }

    public function test_preview_skips_days_without_any_activity(): void
    {
        $startDate = now()->subDay()->toDateString();
        $endDate = now()->toDateString();

        $unit = Unit::factory()->create([
            'name' => 'UNIT 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'unit_id' => $unit->id,
        ]);

        RengiatEntry::query()->create([
            'unit_id' => $unit->id,
            'entry_date' => $endDate,
            'time_start' => '08:30',
            'description' => 'Aktivitas hanya di tanggal akhir',
            'case_number' => null,
            'created_by' => $operator->id,
            'updated_by' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('reports.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('report.days', fn ($days): bool => count($days) === 1)
            ->where('report.days.0.date', $endDate)
        );
    }
}
