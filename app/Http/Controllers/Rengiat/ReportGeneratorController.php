<?php

namespace App\Http\Controllers\Rengiat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rengiat\ReportFilterRequest;
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
            $filters['unit_id'],
            $filters['keyword'],
        );

        return Inertia::render('reports/index', [
            'filters' => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->equalTo($filters['start_date'])
                    ? null
                    : $filters['end_date']->toDateString(),
                'unit_id' => $filters['unit_id'],
                'keyword' => $filters['keyword'],
            ],
            'filterUnits' => Unit::query()
                ->active()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ])
                ->values(),
            'report' => $report,
            'canExport' => $request->user()->can('export-rengiat'),
            'exportUrl' => route('reports.export', [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->equalTo($filters['start_date'])
                    ? null
                    : $filters['end_date']->toDateString(),
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
            $filters['unit_id'],
            $filters['keyword'],
        );

        $fileName = $filters['start_date']->equalTo($filters['end_date'])
            ? sprintf('rengiat-%s.pdf', $filters['start_date']->format('Ymd'))
            : sprintf(
                'rengiat-%s-%s.pdf',
                $filters['start_date']->format('Ymd'),
                $filters['end_date']->format('Ymd'),
            );

        return $this->pdfExporter->download([
            'title' => $report['title'],
            'days' => $report['days'],
            'generated_at' => now()->format('d-m-Y H:i:s'),
        ], $fileName);
    }

    /**
     * @return array{
     *   start_date: CarbonImmutable,
     *   end_date: CarbonImmutable,
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
            'unit_id' => isset($validated['unit_id']) ? (int) $validated['unit_id'] : null,
            'keyword' => isset($validated['keyword']) && trim($validated['keyword']) !== ''
                ? trim((string) $validated['keyword'])
                : null,
        ];
    }
}
