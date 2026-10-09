<?php

return [
    'filters' => [
        'all' => 'All',
        'deleted' => 'Deleted',
        'search' => 'Search',
        'given_date' => 'Given date',
        'reset' => 'Reset',
        'order_type' => 'Order type',
        'search_placeholder' => 'Order # or title',
        'date_start' => 'start',
        'date_end' => 'end',
        'show_all' => 'Show all',
    ],
    'table' => [
        'title' => 'Orders',
        'row_no' => '#',
        'order_no' => 'Order #',
        'type' => 'Type',
        'given_date' => 'Given date',
        'given_by' => 'Given by',
        'status' => 'Status',
        'action' => 'Action',
        'deleted_date' => 'Deleted date',
        'deleted_by' => 'Deleted by',
        'unit' => 'orders',
    ],
    'messages' => [
        'force_delete_confirm' => 'Are you sure you want to remove this data?',
        'delete_order_confirm' => 'Are you sure you want to delete this order?',
        'order_duplicated' => 'A copy of the order was created as a draft.',
        'draft_not_ready' => 'A draft order cannot be approved — finish and save it first.',
    ],
    'actions' => [
        'open_user_guide' => 'User guide',
        'force_delete' => 'Delete permanently',
        'restore' => 'Restore',
        'delete' => 'Delete',
        'export_excel' => 'Export to Excel',
        'more' => 'More actions',
        'continue' => 'Continue',
        'preview' => 'Preview',
        'edit' => 'Edit',
        'duplicate' => 'Duplicate',
        'download' => 'Download',
    ],
    'status' => [
        'draft' => 'Draft',
    ],
    'preview' => [
        'loading' => 'Preparing preview…',
        'unavailable' => 'The preview could not be generated. Use “Download” to open the document.',
        'no_document' => 'This order has no document yet.',
        'html_fallback' => 'Simplified view — download the document for the exact layout.',
    ],
    'hints' => [
        'docx_only' => 'Only DOCX orders can be edited',
    ],
    'guide' => [
        'title' => 'Using orders for the first time?',
        'description' => 'Open the quick guide for creating, filtering, searching, and editing orders.',
    ],
];
