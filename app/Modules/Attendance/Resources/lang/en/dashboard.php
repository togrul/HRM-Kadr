<?php

return [
    'title' => 'Attendance tracking',
    'workspace' => [
        'title' => 'Workspace',
        'description' => 'This screen manages attendance, shifts and manual entries.',
    ],
    'filters' => [
        'year' => 'Year',
        'month' => 'Month',
    ],
    'sections' => [
        'title' => 'Attendance sections',
        'description' => 'Switch views without leaving the attendance workspace.',
    ],
    'actions' => [
        'open_user_guide' => 'User guide',
    ],
    'tabs' => [
        'overview' => 'Summary',
        'daily_monitor' => 'Daily monitor',
        'puantaj' => 'Timesheet grid',
        'exceptions' => 'Exceptions inbox',
        'overtime' => 'Overtime board',
        'month_close' => 'Month close',
        'manual' => 'Manual entries',
        'settings' => 'Settings',
        'shifts' => 'Shifts',
        'calendar_regimes' => 'Work regime calendar',
    ],
    'cards' => [
        'attendance_statistics' => 'Attendance statistics',
        'process_statistics' => 'Process statistics',
    ],
    'metrics' => [
        'workdays' => 'Workdays',
        'holiday_weekend' => 'Holiday / Weekend',
        'scheduled_minutes' => 'Scheduled minutes',
        'worked_minutes' => 'Worked minutes',
        'overtime_minutes' => 'Overtime minutes',
        'coverage' => 'Coverage',
        'coverage_hint' => 'Actual / planned work hours',
        'absence_rate' => 'Absence rate',
        'absence_rate_hint' => ':absence / :scheduled planned days',
        'compliance' => 'Compliance',
        'compliance_hint' => 'Days without late or early leave',
        'overtime_trend' => 'Overtime trend',
        'overtime_trend_hint' => 'Previous month: :minutes minutes',
        'manual_pending' => 'Manual pending',
        'unprocessed_punches' => 'Unprocessed punches',
        'open_exceptions' => 'Open exceptions',
        'pending_overtime' => 'Pending overtime',
    ],
];
