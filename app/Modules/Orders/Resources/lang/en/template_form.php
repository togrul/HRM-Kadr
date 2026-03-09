<?php

return [
    'titles' => [
        'add_template' => 'Add template',
        'edit_template' => 'Edit template',
        'delete_template' => 'Delete template',
    ],
    'labels' => [
        'id' => 'ID',
        'category' => 'Category',
        'name' => 'Name',
        'model' => 'Model',
        'page' => 'Page',
        'file' => 'File',
        'content' => 'Content',
        'download' => 'Download',
        'template_flow' => 'Template flow',
        'type' => 'Type',
        'active_version' => 'Active version',
        'status' => 'Status',
        'checksum' => 'Checksum',
        'linked' => 'Linked',
        'yes' => 'Yes',
        'no' => 'No',
        'current_file_checksum' => 'Current file checksum (sha256)',
        'uploaded_file_checksum' => 'Uploaded file checksum (sha256)',
    ],
    'actions' => [
        'save' => 'Save',
        'delete' => 'Delete',
        'open_set_type_ui_config' => 'Open Set Type / UI config',
    ],
    'descriptions' => [
        'model_locked_edit_mode' => 'Model is locked in edit mode. Create a new template if model type must change.',
        'template_master_data' => 'This modal manages template master data (ID, category, model, blade, DOCX file).',
        'set_type_ui_config' => 'Set Type > UI config manages dynamic fields, mappings, section blocks, and template version lifecycle.',
    ],
    'messages' => [
        'template_id_required' => 'Template id is required.',
        'template_id_exists' => 'Template id already exists.',
        'template_id_change_blocked' => 'Template id cannot be changed because dependent records exist.',
        'delete_template_confirm' => 'Are you sure you want to delete this template?',
    ],
];
