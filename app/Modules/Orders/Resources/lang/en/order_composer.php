<?php

return [
    'title' => 'Compose order',
    'labels' => [
        'type' => 'Order type',
        'number' => 'Order number',
        'date' => 'Order date',
        'preview' => 'Preview',
        'fields' => 'Details',
        'edit_hint' => 'You can edit the text directly.',
    ],
    'actions' => [
        'generate' => 'Generate preview',
        'download' => 'Download Word document',
    ],
    'errors' => [
        'unknown_type' => 'Unknown order type.',
        'nothing_to_generate' => 'Nothing to generate. Create a preview first.',
    ],
    'messages' => [
        'template_saved' => 'Template saved.',
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
