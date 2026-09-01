<?php

return [
    'title' => 'Home',
    'breadcrumb' => 'Home',
    'greeting' => 'Welcome, :name',
    'subtitle' => 'What needs attention, and how this week looks.',
    'empty' => 'Nothing to show.',
    'panel_subtitle' => 'Today\'s work',

    'greetings' => [
        'morning' => 'Good morning, :name',
        'afternoon' => 'Good afternoon, :name',
        'evening' => 'Good evening, :name',
    ],

    'actions' => [
        'new_employee' => 'New employee',
    ],

    'today' => [
        'title' => 'Today',
        'empty' => 'Nothing waiting today.',
        'items' => [
            'attendance_pending' => ':count employees awaiting approval',
            'unsigned_orders' => ':count orders awaiting signature',
            'birthdays' => ':count birthdays today',
            'vacations_starting' => ':count vacations start this week',
        ],
        'notes' => [
            'attendance_pending' => 'Manually entered attendance records',
            'unsigned_orders' => 'Orders waiting for approval',
            'vacations_starting' => 'Within the next 7 days',
        ],
    ],

    'quick' => [
        'title' => 'Quick actions',
        'new_employee' => 'Add an employee',
        'new_order' => 'Create an order',
        'vacation_request' => 'Vacation request',
        'export_report' => 'Export the monthly report',
        'today_attendance' => "Today's attendance",
    ],

    'attention' => [
        'title' => 'Needs attention',
        'all_clear' => 'Nothing is waiting on you.',
        'open' => 'Open',
        'waiting' => 'oldest is :days days old',
        'waiting_today' => 'all filed today',
        'cards' => [
            'attendance_pending' => [
                'label' => 'Records awaiting approval',
                'hint' => 'Manually entered attendance records',
                'action' => 'Review',
            ],
            'unsigned_orders' => [
                'label' => 'Unsigned orders',
                'hint' => 'Drafted, not yet approved',
                'action' => 'Open orders',
            ],
            'vacation_requests' => [
                'label' => 'Vacation requests',
                'hint' => 'Waiting for manager approval',
                'action' => 'Open requests',
            ],
            'expiring_documents' => [
                'label' => 'Expiring documents',
                'hint' => 'Expiring within 30 days, or already expired',
                'action' => 'Open documents',
            ],
        ],
    ],

    'attendance' => [
        'title' => 'Attendance this week',
        'subtitle' => 'Last 7 days',
        'average' => 'Average',
        'headcount' => 'Daily attendance rate · :count employees',
        'rate_hint' => ':date · attendance :rate%',
        'present' => 'Present',
        'absent' => 'Absent',
        'no_data' => 'No attendance rollup for this week.',
        'weekdays' => [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ],
    ],

    'activity' => [
        'title' => 'Recent activity',
        'view_all' => 'View all',
        'system_actor' => 'System',
        'no_data' => 'No records yet.',
        'events' => [
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'restored' => 'restored',
            'login' => 'signed in',
            'logout' => 'signed out',
            'default' => 'changed',
        ],
    ],

    'structure' => [
        'title' => 'Coverage by structure',
        'subtitle' => 'Against the staff schedule',
        'manage' => 'Staff schedule',
        'filled' => 'Filled',
        'vacant' => 'Vacant',
        'no_data' => 'The staff schedule is empty.',
    ],
];
