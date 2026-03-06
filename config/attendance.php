<?php

return [
    'ingest' => [
        'token' => env('ATTENDANCE_INGEST_TOKEN'),
        'default_source' => env('ATTENDANCE_INGEST_DEFAULT_SOURCE', 'api'),
        'auto_process' => (bool) env('ATTENDANCE_INGEST_AUTO_PROCESS', false),
    ],
    'processing' => [
        'default_window_hours' => (int) env('ATTENDANCE_PROCESS_DEFAULT_WINDOW_HOURS', 48),
        'schedule_enabled' => (bool) env('ATTENDANCE_PROCESS_SCHEDULE_ENABLED', false),
        'schedule_every_minutes' => (int) env('ATTENDANCE_PROCESS_SCHEDULE_EVERY_MINUTES', 10),
        'policy_defaults' => [
            'overtime_policy' => env('ATTENDANCE_POLICY_OVERTIME', 'by_approval'),
            'rounding_policy' => env('ATTENDANCE_POLICY_ROUNDING', 'none'),
            'rounding_step_minutes' => (int) env('ATTENDANCE_POLICY_ROUNDING_STEP_MINUTES', 5),
        ],
    ],
    'snapshot' => [
        'schedule_enabled' => (bool) env('ATTENDANCE_SNAPSHOT_SCHEDULE_ENABLED', false),
        'schedule_day' => (int) env('ATTENDANCE_SNAPSHOT_SCHEDULE_DAY', 1),
        'schedule_at' => env('ATTENDANCE_SNAPSHOT_SCHEDULE_AT', '01:30'),
        'schedule_lock' => (bool) env('ATTENDANCE_SNAPSHOT_SCHEDULE_LOCK', false),
    ],
    'exports' => [
        'payroll' => [
            'csv' => [
                'delimiter' => env('ATTENDANCE_EXPORT_CSV_DELIMITER', ';'),
                'enclosure' => env('ATTENDANCE_EXPORT_CSV_ENCLOSURE', '"'),
                'line_ending' => env('ATTENDANCE_EXPORT_CSV_LINE_ENDING', PHP_EOL),
                'use_bom' => (bool) env('ATTENDANCE_EXPORT_CSV_USE_BOM', true),
                'output_encoding' => env('ATTENDANCE_EXPORT_CSV_OUTPUT_ENCODING', 'UTF-8'),
            ],
        ],
    ],
    'performance' => [
        'overview_cache_minutes' => (int) env('ATTENDANCE_OVERVIEW_CACHE_MINUTES', 10),
        'query_budget' => [
            'overview_build' => (int) env('ATTENDANCE_QUERY_BUDGET_OVERVIEW', 20),
            'daily_monitor_load' => (int) env('ATTENDANCE_QUERY_BUDGET_DAILY_MONITOR', 25),
            'puantaj_grid_load' => (int) env('ATTENDANCE_QUERY_BUDGET_PUANTAJ', 30),
        ],
    ],
    'observability' => [
        'reports' => [
            'enabled' => (bool) env('ATTENDANCE_REPORTS_ENABLED', false),
            'daily_at' => env('ATTENDANCE_REPORT_DAILY_AT', '08:30'),
            'weekly_day' => (int) env('ATTENDANCE_REPORT_WEEKLY_DAY', 1),
            'weekly_at' => env('ATTENDANCE_REPORT_WEEKLY_AT', '08:30'),
            'append_output' => (bool) env('ATTENDANCE_REPORT_APPEND_OUTPUT', true),
            'output_file' => env('ATTENDANCE_REPORT_OUTPUT_FILE', 'logs/attendance-query-budget.log'),
        ],
        'thresholds' => [
            'max_over_budget_probes' => (int) env('ATTENDANCE_REPORT_MAX_OVER_BUDGET_PROBES', 0),
            'max_failed_probes' => (int) env('ATTENDANCE_REPORT_MAX_FAILED_PROBES', 0),
        ],
    ],
];
