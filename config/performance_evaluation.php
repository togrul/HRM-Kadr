<?php

return [
    'performance' => [
        'query_budget' => [
            'overview_build' => env('PERFORMANCE_QUERY_BUDGET_OVERVIEW', 18),
            'templates_build' => env('PERFORMANCE_QUERY_BUDGET_TEMPLATES', 20),
            'tests_build' => env('PERFORMANCE_QUERY_BUDGET_TESTS', 22),
            'evaluations_summary_build' => env('PERFORMANCE_QUERY_BUDGET_EVALUATIONS_SUMMARY', 8),
            'tests_summary_build' => env('PERFORMANCE_QUERY_BUDGET_TESTS_SUMMARY', 10),
            'evaluator_workspace_build' => env('PERFORMANCE_QUERY_BUDGET_EVALUATOR_WORKSPACE', 12),
        ],
        'render_budget' => [
            'overview_render' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_OVERVIEW_RESPONSE', 70000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_OVERVIEW_MS', 120),
            ],
            'evaluations_summary_render' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_EVALUATIONS_SUMMARY_RESPONSE', 30000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_EVALUATIONS_SUMMARY_MS', 50),
            ],
            'tests_summary_render' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_TESTS_SUMMARY_RESPONSE', 40000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_TESTS_SUMMARY_MS', 60),
            ],
            'evaluator_workspace_render' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_WORKSPACE_RESPONSE', 70000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_WORKSPACE_MS', 60),
            ],
            'score_capture_render' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_SCORE_CAPTURE_RESPONSE', 40000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_SCORE_CAPTURE_MS', 60),
            ],
            'evaluator_open_score_form_update' => [
                'response_bytes' => env('PERFORMANCE_RENDER_BUDGET_SCORE_OPEN_RESPONSE', 50000),
                'render_ms' => env('PERFORMANCE_RENDER_BUDGET_SCORE_OPEN_MS', 60),
            ],
        ],
    ],
];
