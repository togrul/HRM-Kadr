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
    ],

    'confirm' => [
        'delete' => 'Are you sure you want to delete this record?',
    ],

    'messages' => [
        'saved' => 'Saved',
        'deleted' => 'Deleted',
    ],
];
