<?php

return [
    'document_expiry' => [
        'reminders' => [
            'schedule_enabled' => (bool) env('COMPLIANCE_DOCUMENT_REMINDERS_ENABLED', false),
            'daily_at' => env('COMPLIANCE_DOCUMENT_REMINDERS_DAILY_AT', '05:55'),
            'days_ahead' => (int) env('COMPLIANCE_DOCUMENT_REMINDERS_DAYS_AHEAD', 30),
        ],
    ],

    'performance' => [
        'query_budget' => [
            'dashboard_build' => (int) env('COMPLIANCE_QUERY_BUDGET_DASHBOARD', 35),
            'filtered_rows_build' => (int) env('COMPLIANCE_QUERY_BUDGET_FILTERED_ROWS', 20),
            'reminder_rows_build' => (int) env('COMPLIANCE_QUERY_BUDGET_REMINDERS', 20),
        ],
    ],
];
