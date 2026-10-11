<?php

namespace Tests\Feature\Reports\Fakes;

use App\Modules\Reports\Application\Pdf\ReportPdfException;
use App\Modules\Reports\Application\Pdf\ReportPdfRenderer;

/**
 * Test mühərriki: LibreOffice-siz PDF yolu; nə render olunduğunu yadda saxlayır.
 */
class FakeReportPdfRenderer implements ReportPdfRenderer
{
    /**
     * @var list<array{payload:array<string,mixed>,meta:array<int,array{label:string,value:string}>}>
     */
    public array $rendered = [];

    public function __construct(private ?ReportPdfException $failure = null) {}

    public function isAvailable(): bool
    {
        return $this->failure === null || ! $this->failure->isUnavailable();
    }

    public function render(array $payload, array $meta = []): string
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->rendered[] = ['payload' => $payload, 'meta' => $meta];

        return "%PDF-1.7\n% fake report\n";
    }
}
