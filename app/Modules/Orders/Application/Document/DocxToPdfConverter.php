<?php

namespace App\Modules\Orders\Application\Document;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Converts a .docx to PDF via headless LibreOffice so the composer can show a 100%
 * faithful in-browser preview of the generated order (PhpWord's HTML reader is lossy).
 * Degrades gracefully: if no LibreOffice binary is present (the production image ships
 * none), isAvailable() is false and callers fall back to DocxToHtmlRenderer; a failed
 * conversion is logged with LibreOffice's own error output.
 */
class DocxToPdfConverter
{
    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    /**
     * @return string|null absolute path to the generated PDF, or null on failure
     */
    public function convert(string $docxPath): ?string
    {
        $binary = $this->binary();
        if ($binary === null || ! is_file($docxPath)) {
            return null;
        }

        $outDir = storage_path('app/tmp/pdf_'.Str::uuid()->toString());
        File::ensureDirectoryExists($outDir);

        // A throwaway user-profile dir lets concurrent conversions run without clashing.
        $profile = 'file://'.storage_path('app/tmp/lo_'.Str::uuid()->toString());

        $process = new Process([
            $binary,
            '-env:UserInstallation='.$profile,
            '--headless',
            '--norestore',
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $docxPath,
        ]);
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (Throwable $e) {
            // A timeout or a binary that cannot start must not take the page down.
            Log::warning('orders.docx_to_pdf.failed', ['docx' => $docxPath, 'error' => $e->getMessage()]);

            return null;
        }

        $pdf = $outDir.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';

        if (! is_file($pdf)) {
            Log::warning('orders.docx_to_pdf.failed', [
                'docx' => $docxPath,
                'exit_code' => $process->getExitCode(),
                'error' => trim($process->getErrorOutput()) ?: trim($process->getOutput()),
            ]);

            return null;
        }

        return $pdf;
    }

    /**
     * Locate a LibreOffice/soffice binary: configured path, then common install paths,
     * then the PATH.
     */
    private function binary(): ?string
    {
        $candidates = array_filter([
            config('orders.soffice_path'),
            '/opt/homebrew/bin/soffice',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // Fall back to whatever is on PATH.
        $which = new Process(['which', 'soffice']);
        $which->run();
        $path = trim($which->getOutput());

        return $which->isSuccessful() && $path !== '' ? $path : null;
    }
}
