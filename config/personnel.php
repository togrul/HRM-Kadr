<?php

return [
    'portfolio' => [
        'link_health' => [
            'schedule_enabled' => (bool) env('PERSONNEL_PORTFOLIO_LINK_HEALTH_SCHEDULE_ENABLED', false),
            'daily_at' => (string) env('PERSONNEL_PORTFOLIO_LINK_HEALTH_DAILY_AT', '03:15'),
            'timeout_seconds' => (int) env('PERSONNEL_PORTFOLIO_LINK_HEALTH_TIMEOUT', 8),
            'batch_limit' => (int) env('PERSONNEL_PORTFOLIO_LINK_HEALTH_BATCH_LIMIT', 100),
        ],
        'registry_sync' => [
            'schedule_enabled' => (bool) env('PERSONNEL_PORTFOLIO_REGISTRY_SYNC_SCHEDULE_ENABLED', false),
            'daily_at' => (string) env('PERSONNEL_PORTFOLIO_REGISTRY_SYNC_DAILY_AT', '03:45'),
        ],
        'policy' => [
            'schedule_enabled' => (bool) env('PERSONNEL_PORTFOLIO_POLICY_SCHEDULE_ENABLED', false),
            'daily_at' => (string) env('PERSONNEL_PORTFOLIO_POLICY_DAILY_AT', '04:15'),
            'require_archive_for_media_verification' => (bool) env('PERSONNEL_PORTFOLIO_REQUIRE_ARCHIVE_FOR_MEDIA_VERIFICATION', true),
            'require_healthy_archive_for_media_verification' => (bool) env('PERSONNEL_PORTFOLIO_REQUIRE_HEALTHY_ARCHIVE_FOR_MEDIA_VERIFICATION', true),
            'block_verification_when_link_broken' => (bool) env('PERSONNEL_PORTFOLIO_BLOCK_VERIFICATION_WHEN_LINK_BROKEN', true),
            'allow_manual_broken_without_url' => (bool) env('PERSONNEL_PORTFOLIO_ALLOW_MANUAL_BROKEN_WITHOUT_URL', false),
            'auto_archive_on_broken_link' => (bool) env('PERSONNEL_PORTFOLIO_AUTO_ARCHIVE_ON_BROKEN_LINK', true),
            'reject_media_without_archive' => (bool) env('PERSONNEL_PORTFOLIO_REJECT_MEDIA_WITHOUT_ARCHIVE', true),
            'reject_broken_media_without_archive' => (bool) env('PERSONNEL_PORTFOLIO_REJECT_BROKEN_MEDIA_WITHOUT_ARCHIVE', true),
            'auto_reject_stale_pending' => (bool) env('PERSONNEL_PORTFOLIO_AUTO_REJECT_STALE_PENDING', false),
            'stale_pending_days' => (int) env('PERSONNEL_PORTFOLIO_STALE_PENDING_DAYS', 30),
        ],
    ],
    'my_hr' => [
        'onboarding' => [
            'automation' => [
                'schedule_enabled' => (bool) env('MY_HR_ONBOARDING_AUTOMATION_SCHEDULE_ENABLED', true),
                'daily_at' => (string) env('MY_HR_ONBOARDING_AUTOMATION_DAILY_AT', '05:00'),
                'new_hire_lookback_days' => (int) env('MY_HR_ONBOARDING_NEW_HIRE_LOOKBACK_DAYS', 30),
                'default_due_days' => (int) env('MY_HR_ONBOARDING_DEFAULT_DUE_DAYS', 7),
                'reminder_days_ahead' => (int) env('MY_HR_ONBOARDING_REMINDER_DAYS_AHEAD', 2),
                'reminder_cooldown_hours' => (int) env('MY_HR_ONBOARDING_REMINDER_COOLDOWN_HOURS', 24),
                'max_reminders_per_run' => (int) env('MY_HR_ONBOARDING_MAX_REMINDERS_PER_RUN', 150),
            ],
        ],
        'learning' => [
            'automation' => [
                'schedule_enabled' => (bool) env('MY_HR_LEARNING_AUTOMATION_SCHEDULE_ENABLED', true),
                'daily_at' => (string) env('MY_HR_LEARNING_AUTOMATION_DAILY_AT', '05:20'),
                'new_hire_lookback_days' => (int) env('MY_HR_LEARNING_NEW_HIRE_LOOKBACK_DAYS', 30),
                'default_due_days' => (int) env('MY_HR_LEARNING_DEFAULT_DUE_DAYS', 14),
                'reminder_days_ahead' => (int) env('MY_HR_LEARNING_REMINDER_DAYS_AHEAD', 3),
                'reminder_cooldown_hours' => (int) env('MY_HR_LEARNING_REMINDER_COOLDOWN_HOURS', 24),
                'max_reminders_per_run' => (int) env('MY_HR_LEARNING_MAX_REMINDERS_PER_RUN', 150),
            ],
        ],
    ],
    'performance' => [
        'render_budget' => [
            'all_personnel_render' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_RENDER_RESPONSE', 220000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_RENDER_MS', 500),
            ],
            'all_personnel_initial_page' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_INITIAL_PAGE_RESPONSE', 420000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_INITIAL_PAGE_MS', 800),
            ],
            'personnel_table_render' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_TABLE_RENDER_RESPONSE', 180000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_TABLE_RENDER_MS', 560),
            ],
            'all_personnel_status_update' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_STATUS_RESPONSE', 190000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_STATUS_MS', 180),
            ],
            'personnel_table_status_render' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_TABLE_STATUS_RESPONSE', 190000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_TABLE_STATUS_MS', 280),
            ],
            'all_personnel_filter_open' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_FILTER_RESPONSE', 190000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_FILTER_MS', 180),
            ],
            'personnel_crud_render' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_CRUD_RENDER_RESPONSE', 320000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_CRUD_RENDER_MS', 800),
            ],
            'personnel_crud_step_change' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_CRUD_STEP_RESPONSE', 320000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_CRUD_STEP_MS', 600),
            ],
        ],
        'query_budget' => [
            'all_personnel_render' => (int) env('PERSONNEL_QUERY_BUDGET_RENDER', 18),
            'all_personnel_initial_page' => (int) env('PERSONNEL_QUERY_BUDGET_INITIAL_PAGE', 32),
            'personnel_table_render' => (int) env('PERSONNEL_QUERY_BUDGET_TABLE_RENDER', 18),
            'all_personnel_status_update' => (int) env('PERSONNEL_QUERY_BUDGET_STATUS_UPDATE', 28),
            'personnel_table_status_render' => (int) env('PERSONNEL_QUERY_BUDGET_TABLE_STATUS_RENDER', 24),
            'all_personnel_filter_open' => (int) env('PERSONNEL_QUERY_BUDGET_FILTER_OPEN', 42),
            'personnel_crud_render' => (int) env('PERSONNEL_QUERY_BUDGET_CRUD_RENDER', 48),
            'personnel_crud_step_change' => (int) env('PERSONNEL_QUERY_BUDGET_CRUD_STEP_CHANGE', 14),
        ],
    ],
];
