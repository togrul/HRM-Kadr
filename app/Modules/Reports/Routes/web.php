<?php

use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Livewire\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/reports', Dashboard::class)->name('reports');

    Route::get('/reports/print/standard', function (Request $request, ReportsAccessService $access, ReportsAuditLogger $audit, StandardReportService $reports) {
        $access->authorizeExport();
        $report = (string) $request->string('report', 'headcount');
        $access->authorizeStandardReport($report);

        $filters = [
            'year' => $request->integer('year'),
            'month' => $request->integer('month'),
            'structure_id' => $request->integer('structure_id') ?: null,
        ];
        $audit->exported('print', ['tab' => 'standard', 'report' => $report] + $filters);

        $payload = $reports->build($report, $filters);

        return response()->view('reports::print.report', [
            'title' => $payload['title'],
            'description' => $payload['description'],
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
        ]);
    })->name('reports.print-standard');

    Route::get('/reports/print/dynamic', function (Request $request, ReportsAccessService $access, ReportsAuditLogger $audit, DynamicReportBuilderService $builder) {
        $access->authorizeExport();
        $source = (string) $request->string('source', 'personnel');
        $access->authorizeDynamicSource($source);

        $filters = [
            'year' => $request->integer('year'),
            'month' => $request->integer('month'),
            'structure_id' => $request->integer('structure_id') ?: null,
        ];
        $groupBy = (string) $request->string('group_by', 'structure');
        $metric = (string) $request->string('metric', 'count');
        $audit->exported('print', ['tab' => 'dynamic', 'source' => $source, 'group_by' => $groupBy, 'metric' => $metric] + $filters);

        $payload = $builder->build(source: $source, groupBy: $groupBy, metric: $metric, filters: $filters);

        return response()->view('reports::print.report', [
            'title' => $payload['title'],
            'description' => $payload['description'],
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
        ]);
    })->name('reports.print-dynamic');
});
