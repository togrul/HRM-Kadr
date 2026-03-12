<?php

return [
    'performance' => [
        'query_budget' => [
            'overview_build' => env('TRAINING_NEEDS_QUERY_BUDGET_OVERVIEW', 20),
            'planning_build' => env('TRAINING_NEEDS_QUERY_BUDGET_PLANNING', 24),
            'calendar_build' => env('TRAINING_NEEDS_QUERY_BUDGET_CALENDAR', 26),
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
