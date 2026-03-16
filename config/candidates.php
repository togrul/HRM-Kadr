<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Candidate Mode
    |--------------------------------------------------------------------------
    |
    | military: keeps current military-heavy candidate flow
    | civilian: hides military-only fields and relaxes military validation
    | auto: resolve by active profile mapping below
    |
    */
    'mode' => env('APP_CANDIDATE_MODE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Profile -> Mode Mapping
    |--------------------------------------------------------------------------
    |
    | When mode=auto, this map decides the candidate mode from active profile.
    |
    */
    'profile_mode_map' => [
        'default' => 'military',
        'military' => 'military',
        'public' => 'civilian',
        'private' => 'civilian',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mode Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'military' => 'Military',
        'civilian' => 'Civilian',
    ],

    'documents' => [
        'disk' => env('CANDIDATES_DOCUMENTS_DISK', 'local'),
        'directory' => env('CANDIDATES_DOCUMENTS_DIRECTORY', 'candidates'),
        'max_upload_kb' => (int) env('CANDIDATES_DOCUMENTS_MAX_UPLOAD_KB', 10240),
        'categories' => ['cv', 'passport', 'diploma', 'medical', 'test_result', 'other'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Candidate List Presets by Mode
    |--------------------------------------------------------------------------
    |
    | default_status: all|deleted|<status_id>
    | show_deleted_tab: whether deleted filter tab is visible/allowed
    | status_whitelist: empty = all locale statuses, otherwise allowed ids
    | enabled_filters: which filter blocks are visible/processed
    |
    */
    'list_presets' => [
        'military' => [
            'default_status' => 'all',
            'show_deleted_tab' => true,
            'status_whitelist' => [],
            'enabled_filters' => ['fullname', 'gender', 'results', 'age', 'appeal_date', 'document_category'],
        ],
        'civilian' => [
            'default_status' => 'all',
            'show_deleted_tab' => true,
            'status_whitelist' => [],
            'enabled_filters' => ['fullname', 'gender', 'age', 'appeal_date', 'document_category'],
        ],
    ],
    'performance' => [
        'render_budget' => [
            'candidate_list_render' => [
                'response_bytes' => (int) env('CANDIDATES_RENDER_BUDGET_RENDER_RESPONSE', 200000),
                'render_ms' => (int) env('CANDIDATES_RENDER_BUDGET_RENDER_MS', 200),
            ],
            'candidate_filter_update' => [
                'response_bytes' => (int) env('CANDIDATES_RENDER_BUDGET_FILTER_RESPONSE', 220000),
                'render_ms' => (int) env('CANDIDATES_RENDER_BUDGET_FILTER_MS', 220),
            ],
            'candidate_add_modal_open' => [
                'response_bytes' => (int) env('CANDIDATES_RENDER_BUDGET_MODAL_RESPONSE', 240000),
                'render_ms' => (int) env('CANDIDATES_RENDER_BUDGET_MODAL_MS', 150),
            ],
        ],
        'query_budget' => [
            'candidate_list_render' => (int) env('CANDIDATES_QUERY_BUDGET_RENDER', 16),
            'candidate_filter_update' => (int) env('CANDIDATES_QUERY_BUDGET_FILTER_UPDATE', 30),
            'candidate_add_modal_open' => (int) env('CANDIDATES_QUERY_BUDGET_MODAL_OPEN', 16),
        ],
    ],
];
