<?php

return [
    'title' => 'Attendance settings',
    'description' => 'Configure late/early grace and calculation policies for attendance.',
    'global_policy' => 'Global policy',
    'default_shift' => [
        'current_title' => 'Current default shift',
        'current_description' => 'This shift is used when personnel do not have an active assignment.',
        'none_badge' => 'No default shift configured',
        'none_description' => 'Configure a default shift here if manual calculations should work without a personnel-specific assignment.',
        'option_none' => 'No default shift',
    ],
    'fields' => [
        'timezone' => 'Timezone',
        'default_shift' => 'Default shift',
        'late_grace' => 'Late grace (minutes)',
        'early_grace' => 'Early leave grace (minutes)',
        'rounding_policy' => 'Rounding policy',
        'rounding_step' => 'Rounding step (minutes)',
        'overtime_policy' => 'Overtime policy',
    ],
    'options' => [
        'none' => 'none',
        'floor' => 'floor',
        'ceil' => 'ceil',
        'nearest' => 'nearest',
        'by_approval' => 'by_approval',
        'all_worked' => 'all_worked',
        'after_shift' => 'after_shift',
    ],
    'actions' => [
        'save' => 'Save settings',
    ],
    'messages' => [
        'saved' => 'Attendance settings saved.',
    ],
];
