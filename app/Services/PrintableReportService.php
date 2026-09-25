<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class PrintableReportService
{
    /**
     * @param  array<string, string>  $values
     */
    public function pdf(
        string $template,
        array $values,
        string $filename,
        string $title = 'Relatório',
    ): Response {
        $pdf = Pdf::loadView('reports.printable', [
            'title' => $title,
            'content' => $this->render($template, $values),
        ]);

        $pdf->setPaper('a4');

        return $pdf->stream($filename);
    }

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        $templatePath = resource_path($template);

        if (! File::exists($templatePath)) {
            throw new RuntimeException("Printable report template [{$template}] was not found.");
        }

        $contents = File::get($templatePath);
        $tokens = [];

        foreach ($values as $key => $value) {
            $token = 'PRINTABLE_FIELD_'.strtoupper($key).'_TOKEN';
            $tokens[$token] = e($value);
            $contents = str_replace('{{'.$key.'}}', $token, $contents);
        }

        $remainingTokens = [];
        preg_match_all('/{{\s*([a-z0-9_]+)\s*}}/i', $contents, $remainingTokens);

        if ($remainingTokens[1] !== []) {
            throw new RuntimeException("Printable report template [{$template}] contains unresolved fields.");
        }

        $html = Str::markdown($contents);

        return strtr($html, $tokens);
    }
}
