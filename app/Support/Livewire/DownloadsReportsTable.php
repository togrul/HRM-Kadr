<?php

namespace App\Support\Livewire;

use App\Modules\Reports\Exports\ReportsTableExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait DownloadsReportsTable
{
    protected function downloadReportTable(array $rows, array $columns, string $filename): BinaryFileResponse
    {
        return Excel::download(
            new ReportsTableExport(collect($rows), $columns),
            $filename
        );
    }
}
