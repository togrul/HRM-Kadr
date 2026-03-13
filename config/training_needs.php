<?php

return [
    'performance' => [
        'query_budget' => [
            'overview_build' => env('TRAINING_NEEDS_QUERY_BUDGET_OVERVIEW', 20),
            'planning_build' => env('TRAINING_NEEDS_QUERY_BUDGET_PLANNING', 24),
            'calendar_build' => env('TRAINING_NEEDS_QUERY_BUDGET_CALENDAR', 26),
            'analytics_build' => env('TRAINING_NEEDS_QUERY_BUDGET_ANALYTICS', 12),
            'results_summary_build' => env('TRAINING_NEEDS_QUERY_BUDGET_RESULTS_SUMMARY', 14),
            'reports_build' => env('TRAINING_NEEDS_QUERY_BUDGET_REPORTS', 18),
        ],
        'render_budget' => [
            'planning_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_PLANNING_RESPONSE', 300000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_PLANNING_MS', 400),
            ],
            'calendar_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_CALENDAR_RESPONSE', 300000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_CALENDAR_MS', 400),
            ],
            'analytics_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_ANALYTICS_RESPONSE', 120000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_ANALYTICS_MS', 200),
            ],
            'results_summary_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_RESULTS_SUMMARY_RESPONSE', 120000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_RESULTS_SUMMARY_MS', 200),
            ],
            'reports_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_REPORTS_RESPONSE', 220000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_REPORTS_MS', 260),
            ],
            'session_detail_workspace_render' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_SESSION_DETAIL_RESPONSE', 300000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_SESSION_DETAIL_MS', 300),
            ],
            'calendar_session_detail_update' => [
                'response_bytes' => env('TRAINING_NEEDS_RENDER_BUDGET_CALENDAR_SELECT_RESPONSE', 200000),
                'render_ms' => env('TRAINING_NEEDS_RENDER_BUDGET_CALENDAR_SELECT_MS', 250),
            ],
        ],
    ],
    'suggestion' => [
        'source_weights' => [
            'performance_gap' => 36,
            'skill_gap' => 32,
            'manager_review' => 24,
            'hr_review' => 22,
            'exam' => 26,
            'manager_request' => 18,
            'employee_request' => 12,
            'default' => 10,
        ],
        'priority_weights' => [
            'high' => 22,
            'medium' => 12,
            'low' => 5,
        ],
        'repeat_competency_bonus_per_need' => 3,
        'repeat_competency_bonus_cap' => 18,
        'repeat_position_bonus_per_need' => 2,
        'repeat_position_bonus_cap' => 12,
        'mandatory_bonus' => 16,
        'role_priority_bonus' => [
            'high' => 12,
            'medium' => 6,
            'low' => 0,
        ],
        'program_ready_bonus' => 10,
        'gap_bonus' => [
            'high' => 18,
            'medium' => 8,
        ],
        'due_date_bonus' => [
            'overdue' => 20,
            'near_30' => 14,
            'near_90' => 8,
        ],
    ],
    'proposal' => [
        'hourly_rate' => [
            'internal' => 22,
            'external' => 40,
            'hybrid' => 30,
        ],
        'default_hourly_rate' => 25,
        'default_start_hour' => 10,
    ],
];
