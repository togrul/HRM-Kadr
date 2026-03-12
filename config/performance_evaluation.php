<?php

return [
    'performance' => [
        'query_budget' => [
            'overview_build' => env('PERFORMANCE_QUERY_BUDGET_OVERVIEW', 18),
            'templates_build' => env('PERFORMANCE_QUERY_BUDGET_TEMPLATES', 20),
            'tests_build' => env('PERFORMANCE_QUERY_BUDGET_TESTS', 22),
        ],
    ],
];
