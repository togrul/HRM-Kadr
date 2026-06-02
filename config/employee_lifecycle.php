<?php

return [
    'reminders' => [
        'schedule_enabled' => (bool) env('EMPLOYEE_LIFECYCLE_REMINDERS_SCHEDULE_ENABLED', false),
        'daily_at' => (string) env('EMPLOYEE_LIFECYCLE_REMINDERS_DAILY_AT', '05:40'),
        'days_ahead' => (int) env('EMPLOYEE_LIFECYCLE_REMINDERS_DAYS_AHEAD', 3),
        'cooldown_hours' => (int) env('EMPLOYEE_LIFECYCLE_REMINDERS_COOLDOWN_HOURS', 24),
        'max_per_run' => (int) env('EMPLOYEE_LIFECYCLE_REMINDERS_MAX_PER_RUN', 150),
    ],
    'order_integration' => [
        'movement_order_ids' => array_filter(array_map('intval', explode(',', (string) env('EMPLOYEE_LIFECYCLE_MOVEMENT_ORDER_IDS', '')))),
        'promotion_order_ids' => array_filter(array_map('intval', explode(',', (string) env('EMPLOYEE_LIFECYCLE_PROMOTION_ORDER_IDS', '')))),
        'transfer_order_ids' => array_filter(array_map('intval', explode(',', (string) env('EMPLOYEE_LIFECYCLE_TRANSFER_ORDER_IDS', '')))),
        'offboarding_order_ids' => array_filter(array_map('intval', explode(',', (string) env('EMPLOYEE_LIFECYCLE_OFFBOARDING_ORDER_IDS', '')))),
    ],

    'performance' => [
        'query_budget' => [
            'dashboard_build' => (int) env('EMPLOYEE_LIFECYCLE_QUERY_BUDGET_DASHBOARD', 45),
            'filtered_events_build' => (int) env('EMPLOYEE_LIFECYCLE_QUERY_BUDGET_EVENTS', 20),
        ],
    ],
];
