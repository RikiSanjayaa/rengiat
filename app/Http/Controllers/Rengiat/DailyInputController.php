<?php

namespace App\Http\Controllers\Rengiat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rengiat\DailyInputFilterRequest;
use App\Models\RengiatEntry;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DailyInputController extends Controller
{
    public function index(DailyInputFilterRequest $request): Response
    {
        $user = $request->user();
        $this->authorize('viewAny', RengiatEntry::class);

        $validated = $request->validated();
        $selectedDate = CarbonImmutable::parse($validated['date'] ?? now()->toDateString())->toDateString();

        $activeUnits = Unit::query()
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        if ($user->isOperator()) {
            $selectedUnitId = $user->unit_id;
            $units = $activeUnits->where('id', $selectedUnitId)->values();
        } else {
            $selectedUnitId = isset($validated['unit_id'])
                ? (int) $validated['unit_id']
                : $activeUnits->first()?->id;

            $units = $activeUnits;
        }

        $entries = $selectedUnitId === null
            ? collect()
            : RengiatEntry::query()
                ->whereDate('entry_date', $selectedDate)
                ->where('unit_id', $selectedUnitId)
                ->with([
                    'unit:id,name',
                    'creator:id,name',
                    'updater:id,name',
                    'attachments:id,entry_id,path,mime_type,size_bytes',
                ])
                ->chronological()
                ->get();

        return Inertia::render('daily-input/index', [
            'selectedDate' => $selectedDate,
            'selectedUnitId' => $selectedUnitId,
            'units' => $units->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
            ])->values(),
            'entries' => $entries->map(fn (RengiatEntry $entry) => [
                'id' => $entry->id,
                'unit_id' => $entry->unit_id,
                'unit_name' => $entry->unit?->name,
                'entry_date' => $entry->entry_date->toDateString(),
                'time_start' => $entry->time_start ? substr($entry->time_start, 0, 5) : null,
                'description' => $this->resolveDescription($entry),
                'created_at' => $entry->created_at?->toIso8601String(),
                'created_by_name' => $entry->creator?->name,
                'updated_by_name' => $entry->updater?->name,
                'can_update' => $user->can('update', $entry),
                'can_delete' => $user->can('delete', $entry),
                'attachments' => $entry->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'url' => Storage::disk('public')->url($attachment->path),
                    'mime_type' => $attachment->mime_type,
                ]),
            ])->values(),
            'canCreate' => $selectedUnitId !== null
                ? $user->can('create', [RengiatEntry::class, $selectedUnitId])
                : false,
            'attachmentsEnabled' => config('rengiat.enable_attachments'),
        ]);
    }

    private function resolveDescription(RengiatEntry $entry): string
    {
        if ($entry->case_number === null || trim($entry->case_number) === '') {
            return $entry->description;
        }

        return sprintf('%s (No. Kasus: %s)', $entry->description, $entry->case_number);
    }
}
