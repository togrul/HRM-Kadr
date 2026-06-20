<?php

return [
    'title' => 'Manager summary',
    'filters' => [
        'title' => 'Monthly team filters',
        'description' => 'Provides a manager-facing monthly attendance view for the selected team.',
        'search' => 'Search employee',
        'search_placeholder' => 'Search by surname, name or employee number',
        'only_problematic' => 'Show only problematic employees',
    ],
    'scope' => [
        'badge' => 'Structure',
        'description' => 'Summary is limited to the selected structure tree:',
    ],
    'cards' => [
        'personnel_count' => 'Personnel count',
        'problem_personnel' => 'Problematic personnel',
        'absence_days' => 'Absence days',
        'late_minutes' => 'Late minutes',
        'early_leave_minutes' => 'Early leave minutes',
        'open_exceptions' => 'Open exceptions',
    ],
    'table' => [
        'title' => 'Monthly team summary',
        'personnel' => 'Personnel',
        'structure' => 'Structure',
        'scheduled_days' => 'Scheduled days',
        'present_days' => 'Present days',
        'absence_days' => 'Absence',
        'late' => 'Late',
        'early_leave' => 'Early leave',
        'overtime' => 'Overtime (hours)',
        'exceptions' => 'Exceptions',
    ],
    'labels' => [
        'problematic' => 'At risk',
        'day_count' => ':count days',
    ],
];
