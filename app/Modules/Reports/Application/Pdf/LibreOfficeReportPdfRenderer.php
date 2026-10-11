<?php

namespace App\Modules\Reports\Application\Pdf;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Hesabatın PDF-i: Blade ilə UTF-8 HTML qurulur və başsız LibreOffice Writer onu PDF-ə
 * çevirir (Docker imicində quraşdırılıb; sifariş sənədləri də eyni mühərriklə çevrilir).
 * Şrift DejaVu Sans-dır — Azərbaycan hərflərini (ə, ş, ç, ğ, ı, ö, ü) tam əhatə edir və
 * PDF-ə gömülür. Hər çevirmə öz müvəqqəti qovluğu və LibreOffice profili ilə işləyir ki,
 * paralel sorğular toqquşmasın; hər iki qovluq sonda silinir.
 */
class LibreOfficeReportPdfRenderer implements ReportPdfRenderer
{
    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    public function render(array $payload, array $meta = []): string
    {
        $binary = $this->binary();
        if ($binary === null) {
            throw ReportPdfException::unavailable();
        }

        $workDir = storage_path('app/tmp/report_pdf_'.Str::uuid()->toString());
        $profileDir = storage_path('app/tmp/lo_reports_'.Str::uuid()->toString());
        File::ensureDirectoryExists($workDir);

        try {
            $htmlPath = $workDir.'/report.html';
            File::put($htmlPath, $this->html($payload, $meta));

            $process = new Process([
                $binary,
                '-env:UserInstallation=file://'.$profileDir,
                '--headless',
                '--norestore',
                '--infilter=HTML (StarWriter)',
                '--convert-to', 'pdf:writer_pdf_Export',
                '--outdir', $workDir,
                $htmlPath,
            ]);
            $process->setTimeout(max(10, (int) config('reports.pdf.timeout', 90)));

            try {
                $process->run();
            } catch (Throwable $exception) {
                Log::warning('reports.pdf.failed', ['error' => $exception->getMessage()]);

                throw ReportPdfException::failed($exception->getMessage());
            }

            $pdfPath = $workDir.'/report.pdf';
            if (! is_file($pdfPath)) {
                $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());
                Log::warning('reports.pdf.failed', ['exit_code' => $process->getExitCode(), 'error' => $error]);

                throw ReportPdfException::failed($error !== '' ? $error : 'no output');
            }

            return (string) File::get($pdfPath);
        } finally {
            File::deleteDirectory($workDir);
            File::deleteDirectory($profileDir);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<int,array{label:string,value:string}>  $meta
     */
    public function html(array $payload, array $meta = []): string
    {
        return view('reports::pdf.report', [
            'title' => (string) ($payload['title'] ?? ''),
            'description' => (string) ($payload['description'] ?? ''),
            'columns' => is_array($payload['columns'] ?? null) ? $payload['columns'] : [],
            'rows' => is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'meta' => $meta,
            'fontFamily' => (string) config('reports.pdf.font_family', 'DejaVu Sans'),
            'generatedAt' => now()->format('d.m.Y H:i'),
        ])->render();
    }

    /**
     * LibreOffice: konfiq yolu, standart quraşdırma yerləri, sonra PATH.
     */
    private function binary(): ?string
    {
        $candidates = array_filter([
            config('reports.pdf.soffice_path'),
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            '/opt/homebrew/bin/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ], fn ($path): bool => is_string($path) && $path !== '');

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        try {
            $which = new Process(['which', 'soffice']);
            $which->run();
            $path = trim($which->getOutput());

            return $which->isSuccessful() && $path !== '' ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }
}
