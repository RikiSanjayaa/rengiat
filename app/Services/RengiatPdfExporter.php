<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RengiatPdfExporter
{
    /**
     * @param  array<string, mixed>  $viewData
     */
    public function download(array $viewData, string $fileName): Response
    {
        $html = view('pdf.rengiat-report', $viewData)->render();

        try {
            $content = Browsershot::html($html)
                ->showBackground()
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->pdf();

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return DomPdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }
}
