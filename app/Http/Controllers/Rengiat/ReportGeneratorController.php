<?php

namespace App\Http\Controllers\Rengiat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rengiat\ReportFilterRequest;
use App\Models\ReportSetting;
use App\Models\Subdit;
use App\Models\Unit;
use App\Services\RengiatPdfExporter;
use App\Services\RengiatReportBuilder;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportGeneratorController extends Controller
{
    public function __construct(
        private readonly RengiatReportBuilder $reportBuilder,
        private readonly RengiatPdfExporter $pdfExporter,
    ) {}

    public function index(ReportFilterRequest $request): InertiaResponse
    {
        $filters = $this->normalizeFilters($request);

        $report = $this->reportBuilder->build(
            $filters['start_date'],
            $filters['end_date'],
            $filters['subdit_id'],
            $filters['unit_id'],
            $filters['keyword'],
        );
        $report['days'] = $this->filterDaysWithEntries($report['days']);

        return Inertia::render('reports/index', [
            'filters' => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->equalTo($filters['start_date'])
                    ? null
                    : $filters['end_date']->toDateString(),
                'subdit_id' => $filters['subdit_id'],
                'unit_id' => $filters['unit_id'],
                'keyword' => $filters['keyword'],
            ],
            'filterSubdits' => Subdit::query()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Subdit $subdit) => [
                    'id' => $subdit->id,
                    'name' => $subdit->name,
                ])
                ->values(),
            'filterUnits' => Unit::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'order_index'])
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'name' => sprintf('Unit %d', $unit->order_index),
                ])
                ->values(),
            'report' => $report,
            'canExport' => $request->user()->can('export-rengiat'),
            'exportUrl' => route('reports.export', [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->equalTo($filters['start_date'])
                    ? null
                    : $filters['end_date']->toDateString(),
                'subdit_id' => $filters['subdit_id'],
                'unit_id' => $filters['unit_id'],
                'keyword' => $filters['keyword'],
            ]),
        ]);
    }

    public function export(ReportFilterRequest $request): Response
    {
        abort_unless($request->user()->can('export-rengiat'), 403);

        $filters = $this->normalizeFilters($request);

        $report = $this->reportBuilder->build(
            $filters['start_date'],
            $filters['end_date'],
            $filters['subdit_id'],
            $filters['unit_id'],
            $filters['keyword'],
        );
        $report['days'] = $this->filterDaysWithEntries($report['days']);

        $fileName = $filters['start_date']->equalTo($filters['end_date'])
            ? sprintf('rengiat-%s.pdf', $filters['start_date']->format('Ymd'))
            : sprintf(
                'rengiat-%s-%s.pdf',
                $filters['start_date']->format('Ymd'),
                $filters['end_date']->format('Ymd'),
            );

        $reportSetting = ReportSetting::where('user_id', $request->user()->id)->first();

        return $this->pdfExporter->download([
            'title' => $report['title'],
            'units' => $report['units'],
            'days' => $report['days'],
            'generated_at' => now()->setTimezone('Asia/Singapore')->format('d-m-Y H:i:s').' UTC+8',
            'tdd' => $reportSetting && $reportSetting->hasTdd() ? [
                'atas_nama' => $reportSetting->atas_nama,
                'jabatan' => $reportSetting->jabatan,
                'nama_penandatangan' => $reportSetting->nama_penandatangan,
                'pangkat_nrp' => $reportSetting->pangkat_nrp,
            ] : null,
        ], $fileName);
    }

    /**
     * @return array{
     *   start_date: CarbonImmutable,
     *   end_date: CarbonImmutable,
     *   subdit_id: int|null,
     *   unit_id: int|null,
     *   keyword: string|null
     * }
     */
    private function normalizeFilters(ReportFilterRequest $request): array
    {
        $validated = $request->validated();
        $startDate = CarbonImmutable::parse($validated['start_date'] ?? now()->toDateString());
        $endDate = CarbonImmutable::parse($validated['end_date'] ?? $startDate->toDateString());

        return [
            'start_date' => $startDate,
            'end_date' => $endDate->lt($startDate) ? $startDate : $endDate,
            'subdit_id' => isset($validated['subdit_id']) ? (int) $validated['subdit_id'] : null,
            'unit_id' => isset($validated['unit_id']) ? (int) $validated['unit_id'] : null,
            'keyword' => isset($validated['keyword']) && trim($validated['keyword']) !== ''
                ? trim((string) $validated['keyword'])
                : null,
        ];
    }

    /**
     * @param  array<int, array{
     *   date:string,
     *   header_line:string,
     *   rows: array<int, array{
     *      subdit_id:int,
     *      subdit_name:string,
     *      cells: array<int, array{
     *          unit_id:int,
     *          entries: array<int, array{
     *              id:int,
     *              time_start:string|null,
     *              description:string,
     *              has_attachment:bool
     *          }>
     *      }>
     *   }>
     * }>  $days
     * @return array<int, array{
     *   date:string,
     *   header_line:string,
     *   rows: array<int, array{
     *      subdit_id:int,
     *      subdit_name:string,
     *      cells: array<int, array{
     *          unit_id:int,
     *          entries: array<int, array{
     *              id:int,
     *              time_start:string|null,
     *              description:string,
     *              has_attachment:bool
     *          }>
     *      }>
     *   }>
     * }>
     */
    private function filterDaysWithEntries(array $days): array
    {
        return array_values(array_filter($days, function (array $day): bool {
            foreach ($day['rows'] as $row) {
                foreach ($row['cells'] as $cell) {
                    if (count($cell['entries']) > 0) {
                        return true;
                    }
                }
            }

            return false;
        }));
    }
}
