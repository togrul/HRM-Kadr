<?php

return [
    'engine' => [
        /*
         * When enabled, order types must have metadata template mappings to render.
         * If `metadata_only_order_type_ids` is empty, it applies to all order types.
         */
        'metadata_only' => (bool) env('ORDERS_ENGINE_METADATA_ONLY', false),
        'metadata_only_order_type_ids' => array_values(array_filter(array_map(
            static fn ($value) => is_numeric($value) ? (int) $value : null,
            explode(',', (string) env('ORDERS_ENGINE_METADATA_ONLY_ORDER_TYPE_IDS', ''))
        ))),

        /*
         * Legacy fallback gates per context. Keep true during transition.
         * Set false to force metadata-only behavior for that context.
         */
        'allow_legacy_fallback' => [
            'form' => (bool) env('ORDERS_ENGINE_ALLOW_LEGACY_FALLBACK_FORM', true),
            'print' => (bool) env('ORDERS_ENGINE_ALLOW_LEGACY_FALLBACK_PRINT', true),
        ],

        /*
         * If enabled, order types that already have a template set are treated as
         * metadata-required (legacy fallback blocked) for selected context gates.
         */
        'metadata_required_when_template_set_exists' => (bool) env('ORDERS_ENGINE_METADATA_REQUIRED_WHEN_TEMPLATE_SET_EXISTS', false),

        /*
         * Log when runtime falls back to legacy payload/schema in form or print.
         */
        'log_legacy_fallback' => (bool) env('ORDERS_ENGINE_LOG_LEGACY_FALLBACK', true),
    ],

    'template_registry' => [
        /*
         * schema_cached: checks order_template_* table existence via Schema::hasTable
         * and caches readiness result.
         *
         * assume_ready: skips table-existence checks completely to avoid any
         * information_schema queries. Use only when migrations are guaranteed applied.
         */
        'readiness_mode' => env('ORDERS_TEMPLATE_REGISTRY_READINESS_MODE', 'schema_cached'),

        // Cache TTL for active template version IDs per order type.
        'active_version_cache_minutes' => (int) env('ORDERS_TEMPLATE_REGISTRY_ACTIVE_VERSION_CACHE_MINUTES', 15),

        // Cache TTL for readiness checks when readiness_mode = schema_cached.
        'readiness_cache_minutes' => (int) env('ORDERS_TEMPLATE_REGISTRY_READINESS_CACHE_MINUTES', 60),
    ],

    'form' => [
        /*
         * Default option count loaded for personnel dropdown when order is not
         * candidate-based and search is empty.
         */
        'personnel_default_limit' => (int) env('ORDERS_FORM_PERSONNEL_DEFAULT_LIMIT', 15),

        /*
         * Cache TTL for metadata-driven form schema payloads.
         */
        'schema_cache_minutes' => (int) env('ORDERS_FORM_SCHEMA_CACHE_MINUTES', 15),
    ],

    'template_master' => [
        /*
         * Order models selectable in Template Add/Edit modal.
         * Keep these as FQCN strings.
         */
        'order_models' => [
            \App\Models\Personnel::class,
            \App\Models\Candidate::class,
        ],

        /*
         * Allowed blade/page values for template master.
         */
        'allowed_blades' => [
            'default',
            'vacation',
            'business-trips',
        ],
    ],
];
