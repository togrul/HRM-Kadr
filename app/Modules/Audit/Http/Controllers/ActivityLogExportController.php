<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Modules\Audit\Exports\ActivityLogExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityLogExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('show-audit-logs'), 403);

        $format = in_array($request->query('format'), ['csv', 'xlsx'], true)
            ? (string) $request->query('format')
            : 'xlsx';

        $filters = $request->only(['search', 'log_name', 'event', 'date_from', 'date_to']);
        $fileName = 'audit-logs-'.now()->format('Ymd-His').'.'.$format;
        $writer = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new ActivityLogExport($filters), $fileName, $writer);
    }
}
