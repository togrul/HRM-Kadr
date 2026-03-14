<?php

return [
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
        ],
        'query_budget' => [
            'all_personnel_render' => (int) env('PERSONNEL_QUERY_BUDGET_RENDER', 18),
            'all_personnel_initial_page' => (int) env('PERSONNEL_QUERY_BUDGET_INITIAL_PAGE', 32),
            'personnel_table_render' => (int) env('PERSONNEL_QUERY_BUDGET_TABLE_RENDER', 18),
            'all_personnel_status_update' => (int) env('PERSONNEL_QUERY_BUDGET_STATUS_UPDATE', 28),
            'personnel_table_status_render' => (int) env('PERSONNEL_QUERY_BUDGET_TABLE_STATUS_RENDER', 24),
            'all_personnel_filter_open' => (int) env('PERSONNEL_QUERY_BUDGET_FILTER_OPEN', 42),
        ],
    ],
];
