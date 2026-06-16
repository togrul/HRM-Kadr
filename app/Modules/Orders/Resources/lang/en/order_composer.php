<?php

return [
    'title' => 'Compose order',
    'labels' => [
        'type' => 'Order type',
        'number' => 'Order number',
        'date' => 'Order date',
        'employee' => 'Employee',
        'employee_search' => 'Search by name or tabel no.',
        'preview' => 'Preview',
        'fields' => 'Details',
        'edit_hint' => 'You can edit the text directly.',
    ],
    'actions' => [
        'generate' => 'Generate preview',
        'download' => 'Download Word document',
        'issue' => 'Create order & download',
        'edit' => 'Edit order',
        'save' => 'Save changes',
    ],
    'errors' => [
        'unknown_type' => 'Unknown order type.',
        'nothing_to_generate' => 'Nothing to generate. Create a preview first.',
        'personnel_required' => 'Please select an employee.',
        'number_required' => 'Enter the order number.',
    ],
    'messages' => [
        'template_saved' => 'Template saved.',
        'order_issued' => 'Order created and added to the list.',
        'order_approved' => 'Order approved.',
        'order_updated' => 'Order updated.',
    ],
    'designer' => [
        'title' => 'Order type designer',
        'code' => 'Code (lowercase latin)',
        'name' => 'Name',
        'blocks' => 'Blocks',
        'add_block' => 'Add block',
        'variables' => 'Variables',
        'variables_hint' => 'Insert into text as {{ key }}.',
        'save' => 'Save',
        'remove' => 'Remove',
        'numbered' => 'Numbered',
    ],
];
