<?php

use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Livewire\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/reports', Dashboard::class)->name('reports');

    Route::get('/reports/print/standard', function (Request $request, ReportsAccessService $access, StandardReportService $reports) {
        $access->authorizeExport();

        $payload = $reports->build(
            (string) $request->string('report', 'headcount'),
            [
                'year' => $request->integer('year'),
                'month' => $request->integer('month'),
                'structure_id' => $request->integer('structure_id') ?: null,
            ]
        );

        return response()->view('reports::print.report', [
            'title' => $payload['title'],
            'description' => $payload['description'],
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
        ]);
    })->name('reports.print-standard');

    Route::get('/reports/print/dynamic', function (Request $request, ReportsAccessService $access, DynamicReportBuilderService $builder) {
        $access->authorizeExport();

        $payload = $builder->build(
            source: (string) $request->string('source', 'personnel'),
            groupBy: (string) $request->string('group_by', 'structure'),
            metric: (string) $request->string('metric', 'count'),
            filters: [
                'year' => $request->integer('year'),
                'month' => $request->integer('month'),
                'structure_id' => $request->integer('structure_id') ?: null,
            ],
        );

        return response()->view('reports::print.report', [
            'title' => $payload['title'],
            'description' => $payload['description'],
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
        ]);
    })->name('reports.print-dynamic');
});
