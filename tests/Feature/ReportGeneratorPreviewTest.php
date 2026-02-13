<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RengiatEntry;
use App\Models\Subdit;
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
        $subdit = Subdit::factory()->create(['order_index' => 1]);
        $unit = Unit::factory()->create([
            'name' => 'Unit 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $creator = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subdit->id,
            'unit_id' => null,
        ]);

        $entry = RengiatEntry::query()->create([
            'subdit_id' => $subdit->id,
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
            ->where('report.days.0.rows', function ($rows) use ($entry, $subdit): bool {
                $rows = collect($rows)->all();

                foreach ($rows as $row) {
                    if (($row['subdit_id'] ?? null) !== $subdit->id) {
                        continue;
                    }

                    foreach ($row['cells'] as $cell) {
                        foreach ($cell['entries'] as $cellEntry) {
                            if (($cellEntry['id'] ?? null) === $entry->id) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            })
        );
    }

    public function test_start_date_entries_are_shown_when_end_date_equals_start_date(): void
    {
        $date = now()->subDay()->toDateString();
        $subdit = Subdit::factory()->create(['order_index' => 1]);
        $unit = Unit::factory()->create([
            'name' => 'Unit 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $creator = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subdit->id,
            'unit_id' => null,
        ]);

        $entry = RengiatEntry::query()->create([
            'subdit_id' => $subdit->id,
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
            ->where('report.days.0.rows', function ($rows) use ($entry, $subdit): bool {
                $rows = collect($rows)->all();

                foreach ($rows as $row) {
                    if (($row['subdit_id'] ?? null) !== $subdit->id) {
                        continue;
                    }

                    foreach ($row['cells'] as $cell) {
                        foreach ($cell['entries'] as $cellEntry) {
                            if (($cellEntry['id'] ?? null) === $entry->id) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            })
        );
    }

    public function test_preview_skips_days_without_any_activity(): void
    {
        $startDate = now()->subDay()->toDateString();
        $endDate = now()->toDateString();

        $subdit = Subdit::factory()->create(['order_index' => 1]);
        $unit = Unit::factory()->create([
            'name' => 'Unit 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subdit->id,
            'unit_id' => null,
        ]);

        RengiatEntry::query()->create([
            'subdit_id' => $subdit->id,
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

    public function test_report_can_be_filtered_by_subdit(): void
    {
        $date = now()->toDateString();

        $subditA = Subdit::factory()->create(['order_index' => 1]);
        $subditB = Subdit::factory()->create(['order_index' => 2]);
        $unit = Unit::factory()->create([
            'name' => 'Unit 1',
            'order_index' => 1,
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'unit_id' => null,
        ]);

        $operatorA = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subditA->id,
            'unit_id' => null,
        ]);

        $operatorB = User::factory()->create([
            'role' => UserRole::Operator,
            'subdit_id' => $subditB->id,
            'unit_id' => null,
        ]);

        RengiatEntry::factory()->create([
            'subdit_id' => $subditA->id,
            'unit_id' => $unit->id,
            'entry_date' => $date,
            'description' => 'Aktivitas Subdit A',
            'created_by' => $operatorA->id,
        ]);

        RengiatEntry::factory()->create([
            'subdit_id' => $subditB->id,
            'unit_id' => $unit->id,
            'entry_date' => $date,
            'description' => 'Aktivitas Subdit B',
            'created_by' => $operatorB->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('reports.index', [
            'start_date' => $date,
            'subdit_id' => $subditA->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('filters.subdit_id', $subditA->id)
            ->where('report.days.0.rows', fn ($rows): bool => collect($rows)->count() === 1)
            ->where('report.days.0.rows.0.subdit_id', $subditA->id)
        );
    }
}
