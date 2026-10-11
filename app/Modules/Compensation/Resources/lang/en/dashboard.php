<?php

return [
    'kicker' => 'Pay & compensation',
    'title' => 'Compensation',
    'description' => 'Manage pay scales, the component catalog, employee compensation and bank details here.',

    'tabs' => [
        'scales' => 'Scales',
        'components' => 'Catalog',
        'assignments' => 'Employee pay',
        'bank' => 'Bank',
        'history' => 'History',
        'statutory' => 'Tax/insurance rates',
    ],

    'statutory' => [
        'title' => 'Add statutory rate',
        'list' => 'Existing rates',
        'empty' => 'No rates',
        'add_bracket' => 'Add bracket',
        'component' => 'Component',
        'payer' => 'Payer',
        'base' => 'Base',
        'up_to' => 'Up to',
        'rate' => 'Rate (%)',
        'brackets' => 'Brackets',
        'default_regime' => 'All regimes (default)',
        'bracket_count' => ':count brackets',
        'top_rate' => '(top rate)',
        'components' => [
            'income_tax' => 'Income tax',
            'dsmf' => 'DSMF',
            'unemployment' => 'Unemployment insurance',
            'medical' => 'Mandatory medical insurance',
        ],
        'payers' => [
            'ee' => 'Employee',
            'er' => 'Employer',
        ],
        'bases' => [
            'taxable' => 'Taxable base',
            'social' => 'Social base',
        ],
        'effective' => 'In force from :date',
    ],

    'exemptions' => [
        'title' => 'Income tax exemptions (Tax Code art. 102)',
        'hint' => 'Taken off the taxable base before the brackets apply. A new row applies from its own date; locked months keep the old rule.',
        'panel_hint' => 'E.g. Tax Code art. 102.1.6: at the main workplace, a monthly income of up to 2500 AZN has 200 AZN exempt. The previous row with the same code ends the day before.',
        'add' => 'Add exemption',
        'empty' => 'No exemptions',
        'code' => 'Code',
        'legal_basis' => 'Legal basis',
        'amount' => 'Exempt amount (AZN)',
        'income_up_to' => 'Only if monthly income is up to (AZN)',
        'income_limit' => 'monthly income up to :amount AZN',
        'no_income_limit' => 'no income limit',
        'main_workplace_only' => 'main workplace only',
        'taxable_only' => 'Only these components are taxed: :codes',
    ],

    'summary' => [
        'scales' => 'Scales',
        'grades' => 'Grades',
        'components' => 'Active components',
        'assignments' => 'Active pay',
    ],

    'scales' => [
        'title' => 'Pay scales',
        'list' => 'Scales',
        'empty' => 'No scales yet',
        'subtitle' => 'Minimum, midpoint and maximum by grade',
        'meta' => ':scales scales · :grades grades',
    ],

    'grades' => [
        'title' => 'Grades',
        'label' => 'grade',
        'select_scale' => 'Select a scale on the left to view its grades.',
        'empty' => 'No grades in this scale',
    ],

    'components' => [
        'title' => 'Component catalog',
        'list' => 'Components',
        'empty' => 'No components',
    ],

    'assignments' => [
        'current' => 'Current pay',
        'title' => 'New assignment',
        'lines' => 'Earning / deduction lines',
        'order_only' => 'Under the change policy, salary changes only through a salary change order. A first assignment (no active pay yet) can still be made here.',
        'create_order' => 'Create order',
        'reason' => 'Reason for the change',
        'reason_hint' => 'Salary is in journal mode: the reason will be written to the audit log.',
    ],

    'bank' => [
        'title' => 'Bank details',
        'list' => 'Bank accounts',
        'empty' => 'No bank accounts',
    ],

    'history' => [
        'title' => 'Pay history',
        'empty' => 'No history',
        'ongoing' => 'ongoing',
    ],

    'fields' => [
        'name' => 'Name',
        'regime' => 'Regime',
        'currency' => 'Currency',
        'currency_legacy' => ':code (unsupported code)',
        'effective_from' => 'Effective from',
        'effective_to' => 'Effective to',
        'code' => 'Code',
        'base_amount' => 'Base amount',
        'rank_category' => 'Rank category',
        'position' => 'Position',
        'type' => 'Type',
        'calc_type' => 'Calculation type',
        'taxable' => 'Taxable',
        'affects_social' => 'Affects social base',
        'is_statutory' => 'Statutory',
        'personnel' => 'Employee',
        'order_no' => 'Order no.',
        'note' => 'Note',
        'component' => 'Component',
        'amount' => 'Amount',
        'percent' => 'Percent',
        'iban' => 'IBAN',
        'bank_name' => 'Bank name',
        'account_no' => 'Account no.',
        'is_primary' => 'Primary',
        'is_active' => 'Active',
        'sort' => 'Sort',
        'description' => 'Description',
        'gl_code' => 'GL code',
    ],

    'columns' => [
        'scale' => 'Scale',
        'grade_range' => 'Grades',
        'min' => 'Minimum',
        'midpoint' => 'Midpoint',
        'max' => 'Maximum',
        'regime' => 'Regime',
        'grade' => 'Grade',
        'amount' => 'Amount',
        'position' => 'Position',
        'component' => 'Component',
        'type' => 'Type',
        'calc_type' => 'Calculation',
        'flags' => 'Flags',
        'actions' => 'Actions',
    ],

    'types' => [
        'earning' => 'Earning',
        'deduction' => 'Deduction',
    ],

    'calc_types' => [
        'fixed' => 'Fixed',
        'percent' => 'Percent',
        'formula' => 'Formula',
        'per_diem' => 'Per diem',
        'rate' => 'Rate',
    ],

    'status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'ended' => 'Ended',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'search' => 'Search',
        'clear' => 'Clear',
        'search_personnel' => 'Search by name or staff number',
        'add_line' => 'Add line',
        'assign' => 'Assign pay',
        'add_scale' => 'Add scale',
        'add_grade' => 'Add grade',
        'add_component' => 'Add component',
        'add_bank' => 'Add bank account',
        'add_rate' => 'Add rate',
        'open_catalog' => 'Catalog',
        'edit' => 'Edit',
        'close' => 'Close',
    ],

    'confirm' => [
        'delete' => 'Are you sure you want to delete this record?',
    ],

    'messages' => [
        'saved' => 'Saved',
        'deleted' => 'Deleted',
    ],
];
