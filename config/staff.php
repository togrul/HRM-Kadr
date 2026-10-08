<?php

return [
    /*
     * Hiring or transferring someone into a structure + position with no free ştat slot
     * (no row, or the row is full). Off (default): the forms warn, the save goes through —
     * companies often hire before the ştat is amended. On: the save is refused.
     */
    'hire_guard' => [
        'block' => (bool) env('STAFF_HIRE_GUARD_BLOCK', false),
    ],

    'performance' => [
        'render_budget' => [
            'staffs_render' => [
                'response_bytes' => (int) env('STAFF_RENDER_BUDGET_RENDER_RESPONSE', 260000),
                'render_ms' => (int) env('STAFF_RENDER_BUDGET_RENDER_MS', 220),
            ],
            'staffs_vacancies_render' => [
                'response_bytes' => (int) env('STAFF_RENDER_BUDGET_VACANCIES_RESPONSE', 220000),
                'render_ms' => (int) env('STAFF_RENDER_BUDGET_VACANCIES_MS', 220),
            ],
            'staffs_add_modal_open' => [
                'response_bytes' => (int) env('STAFF_RENDER_BUDGET_MODAL_RESPONSE', 180000),
                'render_ms' => (int) env('STAFF_RENDER_BUDGET_MODAL_MS', 160),
            ],
        ],
        'query_budget' => [
            'staffs_render' => (int) env('STAFF_QUERY_BUDGET_RENDER', 12),
            'staffs_vacancies_render' => (int) env('STAFF_QUERY_BUDGET_VACANCIES', 12),
            'staffs_add_modal_open' => (int) env('STAFF_QUERY_BUDGET_MODAL_OPEN', 8),
        ],
    ],
];
