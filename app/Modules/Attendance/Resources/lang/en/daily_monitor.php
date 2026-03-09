<?php

return [
    'title' => 'Daily monitor',
    'filters' => [
        'title' => 'Filters',
        'description' => 'Review today attendance status, late arrivals and missing records for the selected scope.',
        'date' => 'Date',
        'status' => 'Status',
        'search' => 'Search',
        'search_placeholder' => 'Name or tabel no',
    ],
    'scope' => [
        'badge' => 'Structure scope',
        'description' => 'Showing personnel from the selected structure tree only.',
    ],
    'breakdown' => [
        'title' => 'Daily status breakdown',
        'description' => 'Live counters for selected date and structure scope.',
    ],
    'cards' => [
        'present' => 'Present',
        'late' => 'Late',
        'absent' => 'Absent',
        'missing' => 'Missing ledger',
    ],
    'table' => [
        'title' => 'Personnel status list',
        'tabel_no' => 'Tabel no',
        'full_name' => 'Full name',
        'status' => 'Status',
        'worked_hours' => 'Worked (hours)',
        'late_minutes' => 'Late (min)',
        'early_minutes' => 'Early (min)',
    ],
    'statuses' => [
        'all' => 'all',
        'present' => 'present',
        'late' => 'late',
        'absent' => 'absent',
        'missing' => 'missing ledger',
        'manual_present' => 'manual present',
        'holiday_worked' => 'holiday worked',
        'weekend_worked' => 'weekend worked',
        'manual_absence' => 'manual absence',
        'unknown' => 'unknown',
    ],
];
