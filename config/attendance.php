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
        'overtime_request_stale_days' => (int) env('ATTENDANCE_OVERTIME_REQUEST_STALE_DAYS', 3),
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
    'calendar' => [
        'weekend_auto_seed' => [
            'schedule_enabled' => (bool) env('ATTENDANCE_WEEKEND_AUTO_SEED_SCHEDULE_ENABLED', true),
            'schedule_at' => env('ATTENDANCE_WEEKEND_AUTO_SEED_SCHEDULE_AT', '00:05'),
        ],
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
            'overview_build' => (int) env('ATTENDANCE_QUERY_BUDGET_OVERVIEW', 15),
            'daily_monitor_load' => (int) env('ATTENDANCE_QUERY_BUDGET_DAILY_MONITOR', 10),
            'puantaj_grid_load' => (int) env('ATTENDANCE_QUERY_BUDGET_PUANTAJ', 8),
            'history_log_load' => (int) env('ATTENDANCE_QUERY_BUDGET_HISTORY', 8),
            'month_close_status_load' => (int) env('ATTENDANCE_QUERY_BUDGET_MONTH_CLOSE', 8),
        ],
        'render_budget' => [
            'manual_entries_render' => [
                'response_bytes' => (int) env('ATTENDANCE_RENDER_BUDGET_MANUAL_RESPONSE', 220000),
                'render_ms' => (int) env('ATTENDANCE_RENDER_BUDGET_MANUAL_MS', 220),
            ],
            'overtime_board_render' => [
                'response_bytes' => (int) env('ATTENDANCE_RENDER_BUDGET_OVERTIME_RESPONSE', 220000),
                'render_ms' => (int) env('ATTENDANCE_RENDER_BUDGET_OVERTIME_MS', 220),
            ],
            'shift_management_render' => [
                'response_bytes' => (int) env('ATTENDANCE_RENDER_BUDGET_SHIFTS_RESPONSE', 220000),
                'render_ms' => (int) env('ATTENDANCE_RENDER_BUDGET_SHIFTS_MS', 220),
            ],
            'calendar_regimes_render' => [
                'response_bytes' => (int) env('ATTENDANCE_RENDER_BUDGET_CALENDAR_RESPONSE', 180000),
                'render_ms' => (int) env('ATTENDANCE_RENDER_BUDGET_CALENDAR_MS', 180),
            ],
            'month_close_render' => [
                'response_bytes' => (int) env('ATTENDANCE_RENDER_BUDGET_MONTH_CLOSE_RESPONSE', 160000),
                'render_ms' => (int) env('ATTENDANCE_RENDER_BUDGET_MONTH_CLOSE_MS', 180),
            ],
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
