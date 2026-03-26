<?php

$allMenuVisibility = [
    'personnels' => true,
    'staffs' => true,
    'orders' => true,
    'vacations.list' => true,
    'business-trips.list' => true,
    'my-hr' => true,
    'self-service-reviews' => true,
    'onboarding-library' => true,
    'learning-library' => true,
    'training-needs' => true,
    'performance-evaluation' => true,
    'attendance' => true,
];

$trainingNeedsTabs = ['overview', 'catalogs', 'matrix', 'profiles', 'planning', 'calendar', 'results', 'analytics', 'reports', 'lists'];
$performanceTabs = ['overview', 'cycles', 'templates', 'evaluations', 'tests', 'reports', 'lists'];
$performanceTestTabs = ['banks', 'questions', 'import', 'sessions', 'review'];

return [
    'profile_map' => [
        'default' => 'corporate',
        'military' => 'military',
        'public' => 'public',
        'private' => 'private',
    ],

    'packs' => [
        'corporate' => [
            'menu_visibility' => $allMenuVisibility,
            'permission_flags' => [
                'training_needs.view' => true,
                'training_needs.manage' => true,
                'training_needs.review' => true,
                'training_needs.export' => true,
                'performance_evaluation.view' => true,
                'performance_evaluation.manage' => true,
                'performance_evaluation.review' => true,
                'performance_evaluation.export' => true,
                'self_service_reviews.review_all' => true,
            ],
            'workflow_defaults' => [
                'training_needs' => [
                    'tabs' => $trainingNeedsTabs,
                ],
                'performance_evaluation' => [
                    'tabs' => $performanceTabs,
                    'test_tabs' => $performanceTestTabs,
                ],
            ],
            'self_service_approval' => [
                'leave' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
                'vacation' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => true,
                    'hr_always_included' => true,
                ],
                'business_trip' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
            ],
        ],
        'public' => [
            'menu_visibility' => $allMenuVisibility,
            'permission_flags' => [
                'training_needs.view' => true,
                'training_needs.manage' => true,
                'training_needs.review' => true,
                'training_needs.export' => true,
                'performance_evaluation.view' => true,
                'performance_evaluation.manage' => true,
                'performance_evaluation.review' => true,
                'performance_evaluation.export' => true,
                'self_service_reviews.review_all' => true,
            ],
            'workflow_defaults' => [
                'training_needs' => [
                    'tabs' => $trainingNeedsTabs,
                ],
                'performance_evaluation' => [
                    'tabs' => $performanceTabs,
                    'test_tabs' => $performanceTestTabs,
                ],
            ],
            'self_service_approval' => [
                'leave' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
                'vacation' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
                'business_trip' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
            ],
        ],
        'private' => [
            'menu_visibility' => $allMenuVisibility,
            'permission_flags' => [
                'training_needs.view' => true,
                'training_needs.manage' => true,
                'training_needs.review' => true,
                'training_needs.export' => true,
                'performance_evaluation.view' => true,
                'performance_evaluation.manage' => true,
                'performance_evaluation.review' => true,
                'performance_evaluation.export' => true,
                'self_service_reviews.review_all' => true,
            ],
            'workflow_defaults' => [
                'training_needs' => [
                    'tabs' => $trainingNeedsTabs,
                ],
                'performance_evaluation' => [
                    'tabs' => $performanceTabs,
                    'test_tabs' => $performanceTestTabs,
                ],
            ],
            'self_service_approval' => [
                'leave' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
                'vacation' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => true,
                    'hr_always_included' => true,
                ],
                'business_trip' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => false,
                    'hr_always_included' => true,
                ],
            ],
        ],
        'military' => [
            'menu_visibility' => $allMenuVisibility,
            'permission_flags' => [
                'training_needs.view' => true,
                'training_needs.manage' => true,
                'training_needs.review' => true,
                'training_needs.export' => true,
                'performance_evaluation.view' => true,
                'performance_evaluation.manage' => true,
                'performance_evaluation.review' => true,
                'performance_evaluation.export' => true,
                'self_service_reviews.review_all' => true,
            ],
            'workflow_defaults' => [
                'training_needs' => [
                    'tabs' => $trainingNeedsTabs,
                ],
                'performance_evaluation' => [
                    'tabs' => $performanceTabs,
                    'test_tabs' => $performanceTestTabs,
                ],
            ],
            'self_service_approval' => [
                'leave' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => true,
                    'hr_always_included' => true,
                ],
                'vacation' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => true,
                    'hr_always_included' => true,
                ],
                'business_trip' => [
                    'include_primary_approver' => true,
                    'include_upper_approver' => true,
                    'hr_always_included' => true,
                ],
            ],
        ],
    ],
];
