<?php

namespace App\Modules\Orders\Application\Document;

/**
 * Turns a filled .docx into a PDF. Bound to DocxToPdfConverter (headless LibreOffice);
 * tests bind a fake. A host without a converter answers isAvailable() = false and
 * callers degrade instead of failing.
 */
interface PdfConverter
{
    public function isAvailable(): bool;

    /**
     * @return string|null absolute path to the generated PDF (the caller owns and removes it), or null on failure
     */
    public function convert(string $docxPath): ?string;
}
