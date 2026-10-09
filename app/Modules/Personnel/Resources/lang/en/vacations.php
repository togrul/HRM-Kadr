<?php

return [
    'titles' => [
        'vacations_for' => 'Vacations - :name',
        'opening' => 'Opening balance',
        'legacy' => 'Old calendar-year balances',
    ],
    'messages' => [
        'updated' => 'Vacation is updated!',
        'empty' => 'No information added',
        'opening_added' => 'Opening balance added.',
    ],
    'hints' => [
        'work_year' => 'Leave is kept per work year (a work year starts on the hire date). Usage is taken from the oldest work year; unused days carry over to later years.',
        'opening' => 'Unused days of work years before the switch to this system. An opening balance is not edited — delete it and add it again.',
        'legacy' => 'The previous ledger was per calendar year. The last year\'s remaining days were moved to the work year in progress on the switch date; these rows are history only.',
    ],
    'labels' => [
        'work_year' => 'Work year',
        'entitlement' => 'Entitlement',
        'days' => 'days',
        'opening_days' => 'Days',
        'note' => 'Note',
        'available_from' => 'usable from :date',
        'compensated' => 'of which compensated: :days',
        'open_vacations' => 'Vacations of this work year',
        'legacy_row' => 'total :total, remaining :remaining days',
    ],
    'breakdown' => [
        'base' => 'Base :days',
        'seniority' => 'Service +:days (:years yrs)',
        'children' => 'Children +:days',
        'conditions' => 'Conditions +:days',
        'opening' => 'Opening +:days',
    ],
    'strategies' => [
        'legacy' => 'Old balance',
        'opening' => 'Opening balance',
        'ranked' => 'Rank category',
    ],
    'actions' => [
        'add_opening' => 'Add opening balance',
    ],
    'confirm' => [
        'delete_opening_title' => 'Delete this opening balance?',
        'delete_opening_text' => 'The work year\'s remaining days go down by that many days.',
    ],
];
