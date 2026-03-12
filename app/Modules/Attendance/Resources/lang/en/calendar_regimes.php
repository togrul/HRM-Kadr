<?php

return [
    'title' => 'Work regime calendar',
    'description' => 'Manage global and structure-level workday, weekend, and holiday overrides.',
    'cards' => [
        'create' => 'New calendar rule',
        'edit' => 'Edit calendar rule',
        'list' => 'Calendar rules',
    ],
    'fields' => [
        'date' => 'Date',
        'day_type' => 'Day type',
        'name' => 'Name',
        'is_paid' => 'Paid day',
        'scope_type' => 'Scope type',
        'structure' => 'Structure',
        'scope' => 'Scope',
    ],
    'options' => [
        'workday' => 'Workday',
        'weekend' => 'Weekend',
        'holiday' => 'Holiday',
        'global' => 'Global',
        'structure' => 'Structure',
        'select_structure' => 'Select structure',
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
    ],
    'auto_labels' => [
        'weekend' => 'Auto weekend',
    ],
    'messages' => [
        'saved' => 'Calendar rule saved.',
        'deleted' => 'Calendar rule deleted.',
        'duplicate_scope_date' => 'A calendar rule already exists for this date and scope.',
    ],
    'confirmations' => [
        'delete' => 'Delete this calendar rule?',
    ],
];
