<?php

return [
    'performance' => [
        'render_budget' => [
            'all_personnel_render' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_RENDER_RESPONSE', 220000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_RENDER_MS', 220),
            ],
            'all_personnel_status_update' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_STATUS_RESPONSE', 190000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_STATUS_MS', 180),
            ],
            'all_personnel_filter_open' => [
                'response_bytes' => (int) env('PERSONNEL_RENDER_BUDGET_FILTER_RESPONSE', 190000),
                'render_ms' => (int) env('PERSONNEL_RENDER_BUDGET_FILTER_MS', 180),
            ],
        ],
        'query_budget' => [
            'all_personnel_render' => (int) env('PERSONNEL_QUERY_BUDGET_RENDER', 18),
            'all_personnel_status_update' => (int) env('PERSONNEL_QUERY_BUDGET_STATUS_UPDATE', 28),
            'all_personnel_filter_open' => (int) env('PERSONNEL_QUERY_BUDGET_FILTER_OPEN', 42),
        ],
    ],
];
