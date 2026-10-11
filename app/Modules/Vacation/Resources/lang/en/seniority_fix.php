<?php

return [
    'title' => 'Exactly-15-years correction',
    'subtitle' => 'Work years that started before 11.10.2026 gave employees with exactly 15 years of service at the start of the work year 4 seniority days; under Labour Code art. 116.1 the correct value is :law days. The system finds those work years and posts the difference to working employees as an adjustment (once). Terminated employees are listed separately.',
    'actions' => [
        'scan' => 'Find affected',
        'apply' => 'Post adjustment',
    ],
    'sections' => [
        'active' => 'Working employees — adjustment is posted to the balance',
        'terminated' => 'Terminated employees — compensation difference (for accounting)',
    ],
    'hints' => [
        'terminated' => 'Not posted automatically. Compensation for the difference (art. 144.2, CC ruling of 18.02.2026) is issued by accounting with a compensation order; one day = average monthly pay ÷ 30.4 (art. 140.3).',
    ],
    'table' => [
        'employee' => 'Employee',
        'work_year' => 'Work year',
        'seniority_days' => 'Seniority days (calculated)',
        'missing' => 'Adjustment',
        'status' => 'Status',
        'leave_date' => 'Termination date',
        'compensation_days' => 'Difference (days)',
        'note' => 'Note',
    ],
    'status' => [
        'applied' => 'Posted',
        'pending' => 'Pending',
    ],
    'notes' => [
        'compensate' => 'The compensation difference is payable (art. 144.2, CC ruling 18.02.2026).',
        'before_kmq16' => 'Terminated before 18.02.2026 (CC ruling): art. 144.3 then excluded compensation for additional leave — a lawyer must decide.',
    ],
    'empty' => [
        'title' => 'No affected work years',
        'active' => 'No work year of a working employee needs an adjustment.',
        'terminated' => 'No difference for terminated employees.',
    ],
    'confirm' => [
        'title' => 'Post the adjustment?',
        'text' => 'The seniority difference will be posted to :count work years as an adjustment (art. 116.1). Running it again does not post twice.',
    ],
    'messages' => [
        'nothing' => 'Nothing to post.',
        'applied' => 'Adjustment posted: :count work years.',
    ],
    'entry_note' => 'Labour Code art. 116.1: exactly 15 years of service — :law days (+:days), exactly-15-years correction',
];
