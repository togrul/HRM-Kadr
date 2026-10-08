<?php

namespace App\Modules\Orders\Application\Document;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use RuntimeException;
use Throwable;

/**
 * Renders a .docx to a standalone HTML page with PhpWord's HTML writer — the preview
 * fallback for hosts without LibreOffice (DocxToPdfConverter). Less faithful than the
 * PDF (page layout, tabs), but it needs no external binary, so a preview always shows.
 * The page is meant for a sandboxed iframe; text is HTML-escaped by the writer.
 */
class DocxToHtmlRenderer
{
    /**
     * @throws RuntimeException when the file is missing or PhpWord cannot read it
     */
    public function render(string $docxPath): string
    {
        if (! is_file($docxPath)) {
            throw new RuntimeException("DOCX file not found: {$docxPath}");
        }

        $escaping = Settings::isOutputEscapingEnabled();
        Settings::setOutputEscapingEnabled(true);

        try {
            $writer = IOFactory::createWriter(IOFactory::load($docxPath), 'HTML');

            ob_start();
            try {
                $writer->save('php://output');
            } finally {
                $html = (string) ob_get_clean();
            }
        } catch (Throwable $e) {
            throw new RuntimeException('DOCX → HTML render failed: '.$e->getMessage(), 0, $e);
        } finally {
            Settings::setOutputEscapingEnabled($escaping);
        }

        if (trim($html) === '') {
            throw new RuntimeException('DOCX → HTML render produced no output.');
        }

        return $html;
    }
}
