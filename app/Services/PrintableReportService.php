<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use RuntimeException;

class PrintableReportService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function pdf(
        string $template,
        array $data,
        string $filename,
        string $title = 'Relatório',
    ): Response {
        $pdf = Pdf::loadView('reports.printable', [
            'title' => $title,
            'content' => $this->render($template, $data),
        ]);

        $pdf->setPaper('a4');

        return $pdf->stream($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data): string
    {
        $templatePath = resource_path($template);

        if (! File::exists($templatePath)) {
            throw new RuntimeException("Printable report template [{$template}] was not found.");
        }

        return View::file($templatePath, $data)->render();
    }
}
