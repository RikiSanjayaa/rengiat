<?php

namespace App\Http\Controllers\Rengiat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rengiat\DailyInputFilterRequest;
use App\Models\RengiatEntry;
use App\Models\Subdit;
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

        $activeSubdits = Subdit::query()
            ->ordered()
            ->get(['id', 'name']);

        $activeUnits = Unit::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'order_index']);

        $selectedSubditId = isset($validated['subdit_id'])
            ? (int) $validated['subdit_id']
            : ($user->subdit_id ?? $activeSubdits->first()?->id);

        $selectedUnitId = isset($validated['unit_id'])
            ? (int) $validated['unit_id']
            : $activeUnits->first()?->id;

        $entries = $selectedSubditId === null || $selectedUnitId === null
            ? collect()
            : RengiatEntry::query()
                ->whereDate('entry_date', $selectedDate)
                ->where('subdit_id', $selectedSubditId)
                ->where('unit_id', $selectedUnitId)
                ->with([
                    'subdit:id,name',
                    'unit:id,name,order_index',
                    'creator:id,name',
                    'updater:id,name',
                    'attachments:id,entry_id,path,mime_type,size_bytes',
                ])
                ->chronological()
                ->get();

        return Inertia::render('daily-input/index', [
            'selectedDate' => $selectedDate,
            'selectedSubditId' => $selectedSubditId,
            'selectedUnitId' => $selectedUnitId,
            'subdits' => $activeSubdits->map(fn (Subdit $subdit) => [
                'id' => $subdit->id,
                'name' => $subdit->name,
            ])->values(),
            'units' => $activeUnits->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'name' => sprintf('Unit %d', $unit->order_index),
                'order_index' => $unit->order_index,
            ])->values(),
            'entries' => $entries->map(fn (RengiatEntry $entry) => [
                'id' => $entry->id,
                'subdit_id' => $entry->subdit_id,
                'unit_id' => $entry->unit_id,
                'subdit_name' => $entry->subdit?->name,
                'unit_name' => $entry->unit?->order_index
                    ? sprintf('Unit %d', $entry->unit->order_index)
                    : $entry->unit?->name,
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
            'canCreate' => $selectedSubditId !== null
                ? $user->can('create', [RengiatEntry::class, $selectedSubditId])
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
