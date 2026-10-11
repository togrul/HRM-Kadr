<?php

return [
    'performance' => [
        'query_budget' => [
            'overview_build' => (int) env('REPORTS_QUERY_BUDGET_OVERVIEW', 12),
            'standard_headcount_build' => (int) env('REPORTS_QUERY_BUDGET_STANDARD', 12),
            'dynamic_build' => (int) env('REPORTS_QUERY_BUDGET_DYNAMIC', 12),
            'comparisons_build' => (int) env('REPORTS_QUERY_BUDGET_COMPARISONS', 12),
        ],
        'render_budget' => [
            'overview_render' => [
                'response_bytes' => (int) env('REPORTS_RENDER_BUDGET_OVERVIEW_RESPONSE', 240000),
                'render_ms' => (int) env('REPORTS_RENDER_BUDGET_OVERVIEW_MS', 240),
            ],
            'standard_reports_render' => [
                'response_bytes' => (int) env('REPORTS_RENDER_BUDGET_STANDARD_RESPONSE', 220000),
                'render_ms' => (int) env('REPORTS_RENDER_BUDGET_STANDARD_MS', 220),
            ],
            'dynamic_builder_render' => [
                'response_bytes' => (int) env('REPORTS_RENDER_BUDGET_DYNAMIC_RESPONSE', 220000),
                'render_ms' => (int) env('REPORTS_RENDER_BUDGET_DYNAMIC_MS', 220),
            ],
            'comparisons_render' => [
                'response_bytes' => (int) env('REPORTS_RENDER_BUDGET_COMPARISONS_RESPONSE', 180000),
                'render_ms' => (int) env('REPORTS_RENDER_BUDGET_COMPARISONS_MS', 180),
            ],
        ],
    ],

    /*
     * Planlı hesabatlar: `reports:send-scheduled` hər 5 dəqiqədən bir vaxtı çatmış planları
     * tapır və SendScheduledReportJob göndərir (QUEUE_CONNECTION=sync-də dərhal icra olunur).
     */
    'schedules' => [
        'enabled' => (bool) env('REPORTS_SCHEDULES_ENABLED', true),
        'queue' => env('REPORTS_SCHEDULES_QUEUE', 'default'),
        'max_recipients' => (int) env('REPORTS_SCHEDULES_MAX_RECIPIENTS', 20),
        'batch_limit' => (int) env('REPORTS_SCHEDULES_BATCH_LIMIT', 100),
    ],

    /*
     * Server-side PDF: hesabat HTML-i başsız LibreOffice ilə PDF-ə çevrilir (Docker imicində
     * /usr/bin/soffice və DejaVu/Noto şriftləri var). Boş yol: ORDERS_SOFFICE_PATH, sonra
     * standart quraşdırma yerləri və PATH.
     */
    'pdf' => [
        'soffice_path' => env('REPORTS_SOFFICE_PATH', env('ORDERS_SOFFICE_PATH')),
        'timeout' => (int) env('REPORTS_PDF_TIMEOUT', 90),
        'font_family' => env('REPORTS_PDF_FONT', 'DejaVu Sans'),
    ],
];
