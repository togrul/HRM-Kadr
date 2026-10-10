<?php

return [
    'title' => 'Change policy',
    'modes' => [
        'free' => 'Free',
        'journal' => 'Journal',
        'order' => 'Order only',
    ],
    'mode_hints' => [
        'free' => 'The field can be changed at any time.',
        'journal' => 'Changes are allowed, but a reason is required and goes to the audit log.',
        'order' => 'The field changes only through an approved order; it is locked in the form.',
    ],
    'groups' => [
        'assignment' => [
            'label' => 'Structure and position',
            'description' => 'Changed by transfer and hire orders.',
        ],
        'salary' => [
            'label' => 'Salary',
            'description' => 'Pay assignment in Compensation; salary change and hire orders.',
        ],
        'surname' => [
            'label' => 'Surname',
            'description' => 'Changed by a surname change order.',
        ],
        'employment_dates' => [
            'label' => 'Hire and termination dates',
            'description' => 'Written by hire and termination orders.',
        ],
        'contact' => [
            'label' => 'Contact details',
            'description' => 'Phone, mobile, e-mail, residential and registered address.',
        ],
        'family' => [
            'label' => 'Family members',
            'description' => 'Kinship records (the «Family» form step).',
        ],
        'documents' => [
            'label' => 'Documents',
            'description' => 'ID card, service card and passports.',
        ],
        'photo_notes' => [
            'label' => 'Photo and notes',
            'description' => 'The employee photo and additional important information.',
        ],
    ],
    'badges' => [
        'order' => '(by order)',
        'journal' => '(journal)',
    ],
    'hints' => [
        'order_only' => ':group changes only through an order.',
        'create_order' => 'Create order',
    ],
    'reason' => [
        'label' => 'Reason for the change',
        'placeholder' => 'E.g. correcting a typo from the document',
        'hint' => 'Journal-mode fields have changed (:groups) — the reason will be written to the audit log.',
    ],
    'validation' => [
        'order_only' => ':group can only be changed by an order.',
        'reason_required' => 'This change is journaled: give a reason of at least :min characters.',
        'pending_flag_locked' => 'An approved employee cannot be returned to pending approval.',
    ],
];
