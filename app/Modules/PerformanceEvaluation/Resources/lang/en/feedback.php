<?php

return [
    'eyebrow' => '360° feedback',
    'subtitle' => 'Multi-rater feedback and score calibration',

    'stats' => [
        'requests' => 'Requests',
        'collecting' => 'Collecting',
        'calibrating' => 'Calibrating',
        'closed' => 'Closed',
    ],

    'actions' => [
        'new_request' => 'New request',
        'open' => 'Open',
        'calibrate' => 'Calibrate',
        'go_calibrate' => 'Go to calibration',
        'reopen' => 'Reopen',
        'delete' => 'Delete',
        'back' => 'Back',
        'add_rater' => 'Add rater',
        'add' => 'Add',
        'enter_scores' => 'Enter scores',
        'remove' => 'Remove',
        'save_draft' => 'Save draft',
        'approve' => 'Approve',
        'cancel' => 'Cancel',
        'save' => 'Save',
    ],

    'fields' => [
        'subject' => 'Subject',
        'template' => 'Template',
        'cycle' => 'Cycle',
        'progress' => 'Progress',
        'status' => 'Status',
        'final_score' => 'Final score',
        'actions' => 'Actions',
        'raters' => 'raters',
        'rater' => 'Rater',
        'rater_type' => 'Relationship',
        'rater_search' => 'Search rater...',
        'subject_search' => 'Search employee...',
        'submitted_at' => 'Submitted at',
        'anonymous' => 'Anonymous',
        'due_date' => 'Due date',
        'criterion' => 'Criterion',
        'comment' => 'Comment',
    ],

    'status' => [
        'collecting' => 'Collecting',
        'calibrating' => 'Calibrating',
        'closed' => 'Closed',
    ],

    'rater_types' => [
        'manager' => 'Manager',
        'peer' => 'Peer',
        'subordinate' => 'Subordinate',
        'self' => 'Self',
    ],

    'rater_status' => [
        'submitted' => 'Submitted',
        'pending' => 'Pending',
    ],

    'calibrate' => [
        'title' => 'Calibration',
        'description' => 'Review the raw averages per criterion and set the agreed score.',
        'raw_final' => 'Raw final',
        'raw_avg' => 'Raw avg',
        'calibrated' => 'Calibrated',
        'note' => 'Note',
        'approved_final' => 'Approved final',
    ],

    'confirm' => [
        'delete' => 'Are you sure you want to delete this feedback request?',
        'remove_rater' => 'Are you sure you want to remove this rater?',
    ],

    'empty' => [
        'requests' => 'No feedback requests yet.',
        'raters' => 'No raters added yet.',
        'scores' => 'No scores submitted yet.',
        'items' => 'This template has no criteria.',
    ],

    'messages' => [
        'request_created' => 'Feedback request created.',
        'rater_added' => 'Rater added.',
        'scores_saved' => 'Scores saved.',
        'calibration_saved' => 'Calibration saved.',
        'calibration_approved' => 'Calibration approved.',
        'reopened' => 'Request reopened.',
    ],
];
