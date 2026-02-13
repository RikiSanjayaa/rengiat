<?php

namespace App\Services;

use App\Models\RengiatEntry;
use App\Models\Subdit;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RengiatReportBuilder
{
    /**
     * @return array{
     *   units: array<int, array{id:int,name:string,order_index:int}>,
     *   days: array<int, array{
     *      date:string,
     *      header_line:string,
     *      rows: array<int, array{
     *          subdit_id:int,
     *          subdit_name:string,
     *          cells: array<int, array{
     *              unit_id:int,
     *              entries: array<int, array{
     *                  id:int,
     *                  time_start:string|null,
     *                  description:string,
     *                  has_attachment:bool
     *              }>
     *          }>
     *      }>
     *   }>,
     *   title:string
     * }
     */
    public function build(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $subditId = null,
        ?int $unitId = null,
        ?string $keyword = null,
    ): array {
        $subdits = Subdit::query()
            ->when($subditId !== null, fn ($query) => $query->whereKey($subditId))
            ->ordered()
            ->get(['id', 'name', 'order_index']);

        $units = Unit::query()
            ->active()
            ->when($unitId !== null, fn ($query) => $query->whereKey($unitId))
            ->ordered()
            ->get(['id', 'name', 'order_index']);

        $entries = $this->fetchEntries($startDate, $endDate, $subdits, $units, $keyword);

        $entriesByDateSubditAndUnit = [];

        foreach ($entries as $entry) {
            $dateKey = $entry->entry_date->toDateString();
            $entriesByDateSubditAndUnit[$dateKey][$entry->subdit_id][$entry->unit_id][] = [
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

            foreach ($subdits as $subdit) {
                $cells = [];

                foreach ($units as $unit) {
                    $cells[] = [
                        'unit_id' => $unit->id,
                        'entries' => $entriesByDateSubditAndUnit[$dateKey][$subdit->id][$unit->id] ?? [],
                    ];
                }

                $rows[] = [
                    'subdit_id' => $subdit->id,
                    'subdit_name' => $subdit->name,
                    'cells' => $cells,
                ];
            }

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
                    'name' => sprintf('Unit %d', $unit->order_index),
                    'order_index' => $unit->order_index,
                ])
                ->values()
                ->all(),
            'days' => $days,
            'title' => $this->formatReportTitle($startDate, $endDate),
        ];
    }

    /**
     * @param  Collection<int, Subdit>  $subdits
     * @param  Collection<int, Unit>  $units
     * @return Collection<int, RengiatEntry>
     */
    private function fetchEntries(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        Collection $subdits,
        Collection $units,
        ?string $keyword = null,
    ): Collection {
        if ($subdits->isEmpty() || $units->isEmpty()) {
            return collect();
        }

        return RengiatEntry::query()
            ->whereDate('entry_date', '>=', $startDate->toDateString())
            ->whereDate('entry_date', '<=', $endDate->toDateString())
            ->whereIn('subdit_id', $subdits->pluck('id'))
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
