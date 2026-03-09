<?php

return [
    'title' => 'Timesheet grid',
    'headers' => [
        'personnel' => 'Tabel no / Name',
        'total_hours' => 'Total hours',
        'total_days' => 'Total days',
        'workday_override' => 'Workday override',
    ],
    'search' => [
        'label' => 'Search (name or tabel no)',
        'placeholder' => 'e.g. 12345 or Aliyev',
    ],
    'scope' => [
        'badge' => 'Structure scope',
        'description' => 'Showing personnel from the selected structure tree only.',
    ],
    'tooltips' => [
        'worked' => 'Worked: :hours h',
        'status' => 'Status: :status',
        'absence' => 'Absence: :code',
        'leave_type' => 'Leave type: :type',
        'calendar' => 'Work regime: :type',
    ],
    'statuses' => [
        'present' => 'present',
        'manual_present' => 'manual present',
        'holiday_worked' => 'holiday worked',
        'weekend_worked' => 'weekend worked',
        'absent' => 'absent',
        'manual_absence' => 'manual absence',
        'weekend' => 'weekend',
        'holiday' => 'holiday',
        'workday' => 'workday',
        'none' => 'none',
    ],
    'short_labels' => [
        'vacation' => 'VAC',
        'business_trip' => 'TRP',
    ],
    'legend' => [
        'title' => 'Colors and markers',
        'unknown_leave' => 'Leave',
        'leave_description' => 'This marker shows the approved leave type for that day.',
        'leave_description_with_code' => 'This marker shows the approved leave type for that day. Code: :code.',
        'sections' => [
            'colors' => 'Color meanings',
            'leave_types' => 'Leave types',
            'calendar' => 'Work regime calendar overrides',
        ],
        'items' => [
            'full_day' => 'Full workday',
            'partial_day' => 'Partial workday',
            'absence' => 'Absence',
            'weekend' => 'Weekend',
            'holiday' => 'Holiday',
        ],
        'descriptions' => [
            'full_day' => 'Days with exactly 9 worked hours stay white with black text.',
            'partial_day' => 'Shows workdays with fewer than expected hours.',
            'absence' => 'Represents absence or manual absence on a workday.',
            'weekend' => 'Weekend days are indicated with a single calendar marker in the legend.',
            'holiday' => 'Holiday days are shown with the holiday calendar icon.',
        ],
        'calendar_description' => ':type, :scope, :paid',
    ],
    'calendar' => [
        'global_scope' => 'Global scope',
        'paid' => 'paid',
        'unpaid' => 'unpaid',
    ],
];
