<?php

return [
    'engine' => [
        /*
         * Global strict mode: disables all legacy fallback for form/print contexts.
         * Use for gradual no-legacy rollout (recommended with staging-first validation).
         */
        'strict_mode' => (bool) env('ORDERS_ENGINE_STRICT_MODE', false),
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

    'observability' => [
        'query_budget' => [
            'add_form_schema' => (int) env('ORDERS_QUERY_BUDGET_ADD_FORM_SCHEMA', 15),
            'edit_order_load' => (int) env('ORDERS_QUERY_BUDGET_EDIT_ORDER_LOAD', 15),
            'print_payload_build' => (int) env('ORDERS_QUERY_BUDGET_PRINT_PAYLOAD_BUILD', 20),
        ],
        'reports' => [
            'enabled' => (bool) env('ORDERS_TEMPLATE_REPORTS_ENABLED', false),
            'daily_at' => env('ORDERS_TEMPLATE_REPORTS_DAILY_AT', '09:00'),
            'weekly_day' => (int) env('ORDERS_TEMPLATE_REPORTS_WEEKLY_DAY', 1), // 0 (Sun) ... 6 (Sat)
            'weekly_at' => env('ORDERS_TEMPLATE_REPORTS_WEEKLY_AT', '09:00'),
            'channels' => array_values(array_filter(array_map(
                static fn ($channel) => trim((string) $channel),
                explode(',', (string) env('ORDERS_TEMPLATE_REPORT_CHANNELS', 'log'))
            ))),
            'log_file' => env('ORDERS_TEMPLATE_REPORT_LOG_FILE', 'logs/orders-template-metrics.log'),
            'slack_webhook' => env('ORDERS_TEMPLATE_REPORT_SLACK_WEBHOOK'),
            'telegram_bot_token' => env('ORDERS_TEMPLATE_REPORT_TELEGRAM_BOT_TOKEN'),
            'telegram_chat_id' => env('ORDERS_TEMPLATE_REPORT_TELEGRAM_CHAT_ID'),
            'metrics_max_error_rate' => env('ORDERS_TEMPLATE_REPORT_METRICS_MAX_ERROR_RATE'),
            'metrics_max_p95' => env('ORDERS_TEMPLATE_REPORT_METRICS_MAX_P95'),
            'metrics_max_p99' => env('ORDERS_TEMPLATE_REPORT_METRICS_MAX_P99'),
            'metrics_min_total' => (int) env('ORDERS_TEMPLATE_REPORT_METRICS_MIN_TOTAL', 1),
        ],
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
