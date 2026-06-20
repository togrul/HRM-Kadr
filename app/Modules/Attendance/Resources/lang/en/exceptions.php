<?php

return [
    'title' => 'Exceptions inbox',
    'filters' => [
        'title' => 'Queue filters',
        'description' => 'Use exception type and date range to focus on unresolved attendance anomalies.',
        'status' => 'Status',
        'type' => 'Type',
        'from' => 'From',
        'to' => 'To',
    ],
    'scope' => [
        'badge' => 'Structure scope',
        'description' => 'Showing personnel from the selected structure tree only.',
    ],
    'table' => [
        'title' => 'Open items',
        'date' => 'Date',
        'tabel_no' => 'Tabel no',
        'personnel' => 'Personnel',
        'type' => 'Type',
        'message' => 'Message',
        'status' => 'Status',
        'action' => 'Action',
    ],
    'statuses' => [
        'open' => 'open',
        'resolved' => 'resolved',
        'all' => 'all',
    ],
    'types' => [
        'all' => 'all',
        'missing_in' => 'missing_in',
        'missing_out' => 'missing_out',
        'unmatched_punch' => 'unmatched_punch',
    ],
    'actions' => [
        'resolve' => 'Resolve',
        'reopen' => 'Reopen',
    ],
    'messages' => [
        'resolution_note' => 'Resolved from exceptions inbox.',
        'resolved_description' => 'Attendance exception resolved from inbox.',
        'reopened_description' => 'Attendance exception reopened from inbox.',
        'resolved' => 'Exception resolved.',
        'reopened' => 'Exception reopened.',
    ],
];
