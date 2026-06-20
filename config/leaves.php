<?php

return [
    'performance' => [
        'render_budget' => [
            'leaves_render' => [
                'response_bytes' => (int) env('LEAVES_RENDER_BUDGET_RENDER_RESPONSE', 420000),
                'render_ms' => (int) env('LEAVES_RENDER_BUDGET_RENDER_MS', 320),
            ],
            'leaves_status_update' => [
                'response_bytes' => (int) env('LEAVES_RENDER_BUDGET_STATUS_RESPONSE', 320000),
                'render_ms' => (int) env('LEAVES_RENDER_BUDGET_STATUS_MS', 260),
            ],
            'leaves_add_modal_open' => [
                'response_bytes' => (int) env('LEAVES_RENDER_BUDGET_MODAL_RESPONSE', 320000),
                'render_ms' => (int) env('LEAVES_RENDER_BUDGET_MODAL_MS', 240),
            ],
        ],
        'query_budget' => [
            'leaves_render' => (int) env('LEAVES_QUERY_BUDGET_RENDER', 28),
            'leaves_status_update' => (int) env('LEAVES_QUERY_BUDGET_STATUS', 32),
            'leaves_add_modal_open' => (int) env('LEAVES_QUERY_BUDGET_MODAL_OPEN', 28),
            'leaves_edit_modal_open' => (int) env('LEAVES_QUERY_BUDGET_EDIT_MODAL_OPEN', 24),
            'leaves_edit_manual_toggle' => (int) env('LEAVES_QUERY_BUDGET_EDIT_MANUAL_TOGGLE', 10),
        ],
    ],
];
