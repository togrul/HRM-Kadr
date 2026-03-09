<?php

return [
    'titles' => [
        'set_permission' => 'Set permission - :role',
        'add_permission' => 'Add permission',
        'edit_permission' => 'Edit permission',
        'delete_role' => 'Delete role',
        'delete_permission' => 'Delete permission',
    ],
    'actions' => [
        'add_role' => 'Add role',
        'add_permission' => 'Add permission',
        'search_permission' => 'Search permission',
    ],
    'fields' => [
        'permission_description' => 'Permission description',
    ],
    'messages' => [
        'role_saved' => 'Role was updated successfully!',
        'role_deleted' => 'Role was deleted!',
        'permission_saved' => 'Permission was added successfully!',
        'permission_deleted' => 'Permission was deleted!',
        'permission_assigned' => 'Permission was added to role successfully!',
        'delete_role_description' => 'Are you sure you want to delete this role? This action cannot be undone.',
        'delete_permission_description' => 'Are you sure you want to delete this permission? This action cannot be undone.',
    ],
    'badges' => [
        'modules' => [
            'attendance' => 'Attendance',
            'orders' => 'Orders',
            'candidates' => 'Candidates',
            'time_off' => 'Time off',
            'workforce' => 'Workforce',
            'admin' => 'Admin',
            'general' => 'General',
        ],
        'risks' => [
            'high' => 'High risk',
            'medium' => 'Medium risk',
            'low' => 'Low risk',
        ],
        'admin_only' => 'Admin only',
    ],
];
