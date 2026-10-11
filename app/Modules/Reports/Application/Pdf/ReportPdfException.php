<?php

namespace App\Modules\Reports\Application\Pdf;

use RuntimeException;

/**
 * PDF yaradıla bilmədi: mühərrik yoxdur, vaxt bitdi və ya çevirmə xəta verdi.
 */
class ReportPdfException extends RuntimeException
{
    public const UNAVAILABLE = 1;

    public const FAILED = 2;

    public static function unavailable(): self
    {
        return new self('Report PDF engine (LibreOffice) is not available.', self::UNAVAILABLE);
    }

    public static function failed(string $reason): self
    {
        return new self('Report PDF conversion failed: '.$reason, self::FAILED);
    }

    public function isUnavailable(): bool
    {
        return $this->getCode() === self::UNAVAILABLE;
    }

    /**
     * İstifadəçiyə göstəriləcək tərcümə olunmuş mesaj.
     */
    public function userMessage(): string
    {
        return $this->isUnavailable()
            ? __('reports::pdf.errors.unavailable')
            : __('reports::pdf.errors.failed');
    }
}
