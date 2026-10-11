<?php

namespace App\Modules\Reports\Application\Pdf;

/**
 * Hesabat payload-ını PDF-ə çevirən mühərrik. Standart icra LibreOfficeReportPdfRenderer-dir;
 * testlər saxta mühərrik bağlaya bilər.
 */
interface ReportPdfRenderer
{
    public function isAvailable(): bool;

    /**
     * @param  array<string,mixed>  $payload  title, description, columns, rows, summary
     * @param  array<int,array{label:string,value:string}>  $meta
     * @return string PDF baytları
     *
     * @throws ReportPdfException
     */
    public function render(array $payload, array $meta = []): string;
}
