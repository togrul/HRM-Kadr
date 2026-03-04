<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Blaze
    |--------------------------------------------------------------------------
    |
    | Keep this toggle in env so rollout can be reverted instantly without
    | code changes.
    |
    */
    'enabled' => env('BLAZE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable only while profiling Blaze internals.
    |
    */
    'debug' => env('BLAZE_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Optimized Paths
    |--------------------------------------------------------------------------
    |
    | Path-level optimization switches:
    | - compile: compile/wrap components
    | - memo: memoize component compilation
    | - fold: compile-time folding (use cautiously)
    |
    | Start with safe anonymous components and expand gradually.
    |
    */
    'optimize' => [
        // Global baseline: compile all anonymous components in this project.
        // More specific paths below can override this behavior (icons disabled).
        [
            'path' => resource_path('views/components'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 1: icon and badge atoms
        [
            'path' => resource_path('views/components/icons'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/status-badge.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/small-badge.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        // Stage 2: reusable UI layer (safe compile-only rollout)
        [
            'path' => resource_path('views/components/ui'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 3: table primitives (safe compile-only rollout)
        [
            'path' => resource_path('views/components/table'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 4: orders domain components (safe compile-only rollout)
        [
            'path' => resource_path('views/components/orders'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 5: tree/filter primitives used across order forms
        [
            'path' => resource_path('views/components/tree'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/radio-tree'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/filter'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 6: personnel/staff shared component layer
        [
            'path' => resource_path('views/components/personnel'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/staff'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 7: shared UX building blocks
        [
            'path' => resource_path('views/components/nested'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/notification'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/form-card.blade.php'),
            'compile' => true,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/button.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/input.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        // Stage 8: targeted memo rollout (A/B measured)
        [
            'path' => resource_path('views/components/filter/item.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        [
            'path' => resource_path('views/components/notification/item.blade.php'),
            'compile' => true,
            'memo' => true,
            'fold' => false,
        ],
        // Stage 9: module Livewire view rollout (compile-only, no memo/fold)
        [
            'path' => app_path('Modules/Orders/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Candidates/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Leaves/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Staff/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Vacation/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/BusinessTrips/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Personnel/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 10: Services/Admin/UI module rollout (compile-only)
        [
            'path' => app_path('Modules/Services/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/Admin/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/UI/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        // Stage 11: Notifications + SidebarStructure rollout (compile-only)
        [
            'path' => app_path('Modules/Notifications/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
        [
            'path' => app_path('Modules/SidebarStructure/Resources/views'),
            'compile' => false,
            'memo' => false,
            'fold' => false,
        ],
    ],
];
