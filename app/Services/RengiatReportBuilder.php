<?php

namespace App\Services;

use App\Models\RengiatEntry;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RengiatReportBuilder
{
    /**
     * @return array{
     *   units: array<int, array{id:int,subdit_id:int|null,subdit_name:string|null,name:string,order_index:int}>,
     *   days: array<int, array{
     *      date:string,
     *      header_line:string,
     *      rows: array<int, array{
     *          subdit_id:int|null,
     *          subdit_name:string,
     *          show_subdit:bool,
     *          subdit_rowspan:int,
     *          unit_id:int,
     *          unit_name:string,
     *          entries: array<int, array{
     *              id:int,
     *              time_start:string|null,
     *              description:string,
     *              has_attachment:bool
     *          }>
     *      }>
     *   }>,
     *   title:string
     * }
     */
    public function build(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $unitId = null,
        ?string $keyword = null,
    ): array {
        $units = Unit::query()
            ->with('subdit:id,name,order_index')
            ->active()
            ->when($unitId !== null, fn ($query) => $query->whereKey($unitId))
            ->ordered()
            ->get();

        $entries = $this->fetchEntries($startDate, $endDate, $units, $keyword);

        $entriesByDateAndUnit = [];

        foreach ($entries as $entry) {
            $dateKey = $entry->entry_date->toDateString();
            $entriesByDateAndUnit[$dateKey][$entry->unit_id][] = [
                'id' => $entry->id,
                'time_start' => $entry->time_start ? substr($entry->time_start, 0, 5) : null,
                'description' => $this->resolveDescription($entry),
                'has_attachment' => $entry->attachments_count > 0,
            ];
        }

        $days = [];

        for ($cursor = $startDate; $cursor->lte($endDate); $cursor = $cursor->addDay()) {
            $dateKey = $cursor->toDateString();
            $rows = [];

            $units
                ->groupBy(fn (Unit $unit): string => (string) ($unit->subdit_id ?? 0))
                ->each(function (Collection $subditUnits) use (&$rows, $entriesByDateAndUnit, $dateKey): void {
                    $subditName = $subditUnits->first()?->subdit?->name ?? 'Tanpa Subdit';
                    $subditId = $subditUnits->first()?->subdit_id;
                    $subditRowspan = $subditUnits->count();

                    foreach ($subditUnits as $index => $unit) {
                        $rows[] = [
                            'subdit_id' => $subditId,
                            'subdit_name' => $subditName,
                            'show_subdit' => $index === 0,
                            'subdit_rowspan' => $subditRowspan,
                            'unit_id' => $unit->id,
                            'unit_name' => $unit->name,
                            'entries' => $entriesByDateAndUnit[$dateKey][$unit->id] ?? [],
                        ];
                    }
                });

            $days[] = [
                'date' => $dateKey,
                'header_line' => $this->formatDayHeader($cursor),
                'rows' => $rows,
            ];
        }

        return [
            'units' => $units
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'subdit_id' => $unit->subdit_id,
                    'subdit_name' => $unit->subdit?->name,
                    'name' => $unit->name,
                    'order_index' => $unit->order_index,
                ])
                ->values()
                ->all(),
            'days' => $days,
            'title' => $this->formatReportTitle($startDate, $endDate),
        ];
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @return Collection<int, RengiatEntry>
     */
    private function fetchEntries(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        Collection $units,
        ?string $keyword = null,
    ): Collection {
        if ($units->isEmpty()) {
            return collect();
        }

        return RengiatEntry::query()
            ->whereDate('entry_date', '>=', $startDate->toDateString())
            ->whereDate('entry_date', '<=', $endDate->toDateString())
            ->whereIn('unit_id', $units->pluck('id'))
            ->when($keyword !== null && $keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('description', 'like', "%{$keyword}%")
                        ->orWhere('case_number', 'like', "%{$keyword}%");
                });
            })
            ->withCount('attachments')
            ->orderBy('entry_date')
            ->chronological()
            ->get();
    }

    public function formatDayHeader(CarbonImmutable $date): string
    {
        return strtoupper($date->locale('id')->translatedFormat('l, d F Y'));
    }

    public function formatReportTitle(CarbonImmutable $startDate, CarbonImmutable $endDate): string
    {
        if ($startDate->equalTo($endDate)) {
            return sprintf(
                'RENGIAT DITRES PPA DAN PPO POLDA NTB HARI %s TANGGAL %s',
                strtoupper($startDate->locale('id')->translatedFormat('l')),
                strtoupper($startDate->locale('id')->translatedFormat('d F Y')),
            );
        }

        return sprintf(
            'RENGIAT DITRES PPA DAN PPO POLDA NTB TANGGAL %s s/d %s',
            strtoupper($startDate->locale('id')->translatedFormat('d F Y')),
            strtoupper($endDate->locale('id')->translatedFormat('d F Y')),
        );
    }

    private function resolveDescription(RengiatEntry $entry): string
    {
        if ($entry->case_number === null || trim($entry->case_number) === '') {
            return $entry->description;
        }

        return sprintf('%s (No. Kasus: %s)', $entry->description, $entry->case_number);
    }
}
