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
];
