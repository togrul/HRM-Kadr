<?php

return [
    'title' => 'Saved filters',
    'empty' => 'No saved filters for this section yet.',
    'fields' => [
        'name' => 'Preset name',
        'name_placeholder' => 'E.g. Monthly headcount report',
        'shared' => 'Share with all report users',
    ],
    'labels' => [
        'shared' => 'Shared',
        'personal' => 'Personal',
    ],
    'actions' => [
        'save' => 'Save current filters',
        'apply' => 'Apply',
        'delete' => 'Delete preset',
    ],
    'messages' => [
        'saved' => 'Filter preset saved.',
        'deleted' => 'Filter preset deleted.',
        'applied' => 'Filter preset applied.',
        'structure_dropped' => 'The preset\'s structure is outside your visibility — the report is shown for your own structures.',
        'delete_confirmation_required' => 'Please confirm the deletion.',
    ],
    'confirmations' => [
        'delete' => 'Delete this filter preset?',
    ],
    'errors' => [
        'invalid_name' => 'The preset name must not be empty or longer than :max characters.',
        'invalid_period' => 'The report period is invalid.',
        'structure_out_of_scope' => 'The selected structure is outside your visibility.',
        'report_not_allowed' => 'You are not allowed to view this report.',
        'invalid_selection' => 'The report selection is invalid.',
        'tab_not_supported' => 'Filter presets are saved for standard and dynamic reports only.',
    ],
];
