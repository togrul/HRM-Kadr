<?php

return [
    'titles' => [
        'add' => 'Add user',
        'edit' => 'Edit user',
        'delete' => 'Delete user',
    ],
    'actions' => [
        'add_user' => 'Add user',
        'save_user' => 'Save user',
        'personnel_links' => 'User ↔ employee links',
    ],
    'fields' => [
        'user_name_or_email' => 'User name or email',
        'actor_password' => 'Your password (to confirm)',
    ],
    'messages' => [
        'created' => 'User was added successfully!',
        'updated' => 'User was updated successfully!',
        'deleted' => 'User was deleted!',
        'delete_description' => 'Are you sure you want to delete this user? A deleted user can be restored later from the Deleted tab.',
        'force_delete_confirm' => 'Are you sure you want to remove this user?',
        'old_password_mismatch' => 'The current password is incorrect',
        'restored' => 'User was restored!',
        'restore_confirm' => 'Are you sure you want to restore this user? The account will become active again.',
        'target_has_more_permissions' => 'This user holds permissions you do not have — you cannot change or delete them.',
        'role_exceeds_your_permissions' => 'This role contains permissions you do not have — you cannot assign it.',
        'own_role_locked' => 'You cannot change your own role.',
        'own_status_locked' => 'You cannot deactivate your own account.',
        'own_email_locked' => 'You cannot change your own e-mail — another administrator has to do it.',
        'cannot_delete_self' => 'You cannot delete your own account here.',
        'actor_password_required' => 'Enter your own password to change another user\'s e-mail or password.',
        'actor_password_mismatch' => 'Your password is incorrect.',
    ],
];
