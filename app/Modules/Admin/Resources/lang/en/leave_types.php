<?php

return [
    'actions' => [
        'add' => 'Add type',
        'save' => 'Save',
    ],
    'hints' => [
        'attendance_code' => 'This short code is shown in the timesheet grid. Use uppercase letters, numbers, `_`, and `-` only.',
        'attendance_code_empty' => 'If left empty, the timesheet falls back to the generic leave marker.',
        'attendance_code_preview' => 'Timesheet preview',
    ],
    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'attendance_code' => 'Attendance code',
        'max_days' => 'Max days',
        'requires_document' => 'Requires document?',
    ],
    'placeholders' => [
        'attendance_code' => 'e.g. SICK, PTO, HLF',
    ],
    'table' => [
        'actions' => 'Actions',
    ],
];
