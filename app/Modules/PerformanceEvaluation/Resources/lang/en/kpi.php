<?php

return [
    'sections' => [
        'kpis' => 'KPI library',
        'templates' => 'Position templates',
    ],

    'fields' => [
        'code' => 'Code',
        'name' => 'Name',
        'description' => 'Description',
        'type' => 'Type',
        'direction' => 'Direction',
        'unit' => 'Unit',
        'frequency' => 'Frequency',
        'aggregation' => 'Period aggregation',
        'perspective' => 'Perspective',
        'indicator_kind' => 'Indicator kind',
        'evidence_required' => 'Evidence file required for manual values',
        'status' => 'Status',
        'version' => 'Version',
        'period_type' => 'Period type',
        'kpi_weight_share' => 'KPI share, %',
        'competency_weight_share' => 'Competency share, %',
        'positions' => 'Positions',
        'items' => 'KPI items',
        'choose_kpi' => 'Choose a KPI',
        'weight' => 'Weight, %',
        'target' => 'Target',
        'range_min' => 'Range: lower',
        'range_max' => 'Range: upper',
        'threshold' => 'Threshold, %',
        'stretch' => 'Stretch, %',
        'cap' => 'Cap, %',
        'band' => 'Threshold / Stretch / Cap',
        'target_editable' => 'Manager may change the target',
        'kpi' => 'KPI',
        'actual' => 'Actual',
        'achievement' => 'Achievement',
        'score' => 'Score',
        'kpi_score' => 'KPI score',
        'final_score' => 'Final score',
        'personnel' => 'Employee',
        'position' => 'Position',
        'manager' => 'Manager',
        'prorata' => 'Pro-rata',
        'note' => 'Note',
        'evidence' => 'Evidence file',
    ],

    'columns' => [
        'kpi' => 'KPI',
        'measure' => 'Unit · frequency',
    ],

    'directions_short' => [
        'higher_better' => 'Higher is better',
        'lower_better' => 'Lower is better',
        'range' => 'Within range',
    ],

    'types' => [
        'quantitative' => 'Quantitative',
        'qualitative' => 'Qualitative (1–5)',
        'binary' => 'Binary (yes/no)',
    ],

    'directions' => [
        'higher_better' => 'Higher is better',
        'lower_better' => 'Lower is better',
        'range' => 'Range',
    ],

    'units' => [
        'percent' => 'Percent',
        'currency' => 'Amount',
        'count' => 'Count',
        'days' => 'Days',
        'hours' => 'Hours',
        'score' => 'Score',
    ],

    'frequencies' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'semiannual' => 'Semi-annual',
        'annual' => 'Annual',
    ],

    'aggregations' => [
        'sum' => 'Sum',
        'avg' => 'Average',
        'last' => 'Latest value',
        'min' => 'Minimum',
        'max' => 'Maximum',
    ],

    'perspectives' => [
        'financial' => 'Financial',
        'customer' => 'Customer',
        'process' => 'Internal process',
        'growth' => 'Learning & growth',
    ],

    'indicator_kinds' => [
        'lead' => 'Leading (lead)',
        'lag' => 'Lagging (lag)',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ],

    'card_statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'manager_review' => 'Manager review',
        'closed' => 'Closed',
    ],

    'transitions' => [
        'activate' => 'Activate card',
        'submit' => 'Send for review',
        'return' => 'Return',
        'close' => 'Approve and close',
    ],

    'confirm_transition' => [
        'activate' => 'The card becomes active and its targets lock. Continue?',
        'submit' => 'The card goes to manager review and stops taking actuals. Continue?',
        'return' => 'The card goes back to active. Continue?',
        'close' => 'The card is scored, closed and becomes read-only. Continue?',
    ],

    'ratings' => [
        'not_meeting' => 'Does not meet expectations',
        'partially_meeting' => 'Partially meets',
        'meeting' => 'Meets',
        'exceeding' => 'Exceeds',
        'far_exceeding' => 'Far exceeds',
    ],

    'actions' => [
        'add_kpi' => 'New KPI',
        'edit_kpi' => 'Edit KPI',
        'add_template' => 'New template',
        'edit_template' => 'Edit template',
        'add_item' => 'Add item',
        'add_actual' => 'Actual value',
        'generate_cards' => 'Create cards',
        'back_to_list' => 'Back to list',
        'approve' => 'Approve',
        'archive' => 'Archive',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'clear' => 'Clear',
        'remove' => 'Remove',
        'cancel' => 'Cancel',
    ],

    'messages' => [
        'kpi_saved' => 'KPI saved.',
        'kpi_deleted' => 'KPI deleted.',
        'template_saved' => 'Template saved.',
        'template_deleted' => 'Template deleted.',
        'cards_generated' => ':count new KPI cards created.',
        'status_changed' => 'Card status changed.',
        'actual_saved' => 'Actual value recorded.',
    ],

    'errors' => [
        'weights_sum_invalid' => 'KPI weights must add up to 100%.',
        'shares_sum_invalid' => 'The KPI and competency shares must add up to 100%.',
        'items_required' => 'A template needs at least one KPI.',
        'threshold_order_invalid' => 'Threshold must be below 100%, stretch and cap at least 100%, and stretch no higher than cap.',
        'target_required' => 'Enter a target for this KPI.',
        'range_invalid' => 'Enter the lower and upper bound for a range KPI (lower ≤ upper).',
        'kpi_missing' => 'The selected KPI was not found.',
        'duplicate_kpi' => 'A KPI can appear only once in a template.',
        'position_taken' => 'One of the selected positions already belongs to another template.',
        'kpi_in_use' => 'This KPI is used by a template or a card — archive it instead of deleting.',
        'invalid_transition' => 'This transition is not allowed from the card\'s current status.',
        'scorecard_locked' => 'The card is locked: targets change only while it is a draft.',
        'scorecard_not_active' => 'Actuals can be entered only on an active card.',
        'evidence_required' => 'This KPI requires an evidence file.',
    ],

    'warnings' => [
        'item_count' => 'Recommended: :min–:max KPIs per template.',
        'item_weight' => 'Recommended: each KPI weighs between :min% and :max%.',
    ],

    'template_weight' => 'Weight total: :sum%',
    'no_positions' => 'Not linked to a position',
    'positions_hint' => 'Each position can belong to one template only. Greyed-out positions already sit in another template.',
    'band_hint' => 'Threshold, stretch and cap are achievement percentages (e.g. 80 / 110 / 120). Target and range are in the KPI\'s own unit.',
    'version_hint' => 'Changing the type, direction, unit, aggregation or scale creates a new version; open cards stay on the old one.',
    'confirm_delete_kpi' => 'Delete this KPI? It will not be deleted while it is in use.',
    'confirm_delete_template' => 'Delete this template? Cards already created stay as they are.',
    'all_statuses' => 'All statuses',
    'actual_approved' => 'approved',
    'actual_pending' => 'awaiting approval',
    'actual_pending_hint' => 'Your value counts once your manager approves it.',
    'empty_kpis' => 'The library has no KPIs yet.',
    'empty_templates' => 'No templates yet. Create one to link KPIs to positions.',
    'empty_cards' => 'No cards for this cycle. "Create cards" opens one for every position that has a template.',
    'no_cycle' => 'Create an evaluation cycle first.',
    'empty_kpis_hint' => 'Press “New KPI” to start — for example a sales plan, customer satisfaction or on-time task delivery.',
    'split_kpi' => 'KPI',
    'split_competency' => 'Competency',
    'more_items' => 'and :count more KPIs',
    'weight_ok' => 'Weights are balanced — 100% in total',
    'weight_off' => 'Weights add up to :sum% — they must total 100%',
    'positions_selected' => 'selected',
    'search_positions' => 'Search positions…',
    'position_taken_by' => 'Belongs to the “:template” template',
    'no_results' => 'Nothing found',
];
