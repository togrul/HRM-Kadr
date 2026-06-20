<?php

namespace App\Services\Orders\Document;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Converts a .docx to PDF via headless LibreOffice so the composer can show a 100%
 * faithful in-browser preview of the generated order (PhpWord's HTML reader is lossy).
 * Degrades gracefully: if no LibreOffice binary is present, isAvailable() is false and
 * the UI falls back to the exact Word download.
 */
class DocxToPdfConverter
{
    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    /**
     * @return string|null  absolute path to the generated PDF, or null on failure
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
        $process->run();

        $pdf = $outDir.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';

        return is_file($pdf) ? $pdf : null;
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
