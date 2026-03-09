<?php

return [
    'title' => 'Timesheet grid',
    'headers' => [
        'personnel' => 'Tabel no / Name',
        'total_hours' => 'Total hours',
        'total_days' => 'Total days',
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
        'none' => 'none',
    ],
];
