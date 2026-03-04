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
            'enabled_filters' => ['fullname', 'gender', 'results', 'age', 'appeal_date'],
        ],
        'civilian' => [
            'default_status' => 'all',
            'show_deleted_tab' => true,
            'status_whitelist' => [],
            'enabled_filters' => ['fullname', 'gender', 'age', 'appeal_date'],
        ],
    ],
];
