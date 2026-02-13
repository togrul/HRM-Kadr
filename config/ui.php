<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modal Close Events
    |--------------------------------------------------------------------------
    |
    | Side-modal listens these Livewire events to auto-close.
    | `ui:modal-close` is the standardized event and should be preferred.
    |
    */
    'modal_close_events' => [
        'ui:modal-close',
        'personnelAdded',
        'permissionSet',
        'staffAdded',
        'userAdded',
        'menuAdded',
        'fileAdded',
        'candidateAdded',
        'templateAdded',
        'componentAdded',
        'orderAdded',
        'rankAdded',
        'leaveAdded',
        'leaveUpdated',
    ],
];

