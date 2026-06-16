<?php

return [
    'engine' => [
        /*
         * Global strict mode: disables all legacy fallback for form/print contexts.
         * Use for gradual no-legacy rollout (recommended with staging-first validation).
         */
        'strict_mode' => (bool) env('ORDERS_ENGINE_STRICT_MODE', false),

        /*
         * New order types should start from the block designer. Legacy DOCX
         * placeholder rendering is kept only for already-onboarded old types.
         */
        'default_render_mode' => env('ORDERS_ENGINE_DEFAULT_RENDER_MODE', 'designer_layout'),

        /*
         * Temporary compatibility mirror for old order_log_components and
         * order_log_component_attributes tables. Keep enabled until historical
         * printing is fully covered by template_snapshot.
         */
        'write_legacy_component_snapshots' => (bool) env('ORDERS_ENGINE_WRITE_LEGACY_COMPONENT_SNAPSHOTS', true),

        /*
         * Per-order-type behavior hooks. Keys currently follow the selected blade key
         * until order_types gets its own stable handler code column.
         */
        'type_handlers' => [
            'default' => \App\Services\Orders\Handlers\DefaultOrderTypeHandler::class,
            'hiring' => \App\Services\Orders\Handlers\HiringOrderHandler::class,
            'hire' => \App\Services\Orders\Handlers\HiringOrderHandler::class,
            'ise-qebul' => \App\Services\Orders\Handlers\HiringOrderHandler::class,
            'vacation' => \App\Services\Orders\Handlers\LeaveOrderHandler::class,
            'leave' => \App\Services\Orders\Handlers\LeaveOrderHandler::class,
            'mezuniyyet' => \App\Services\Orders\Handlers\LeaveOrderHandler::class,
            'business-trips' => \App\Services\Orders\Handlers\BusinessTripOrderHandler::class,
            'business_trip' => \App\Services\Orders\Handlers\BusinessTripOrderHandler::class,
            'ezamiyyet' => \App\Services\Orders\Handlers\BusinessTripOrderHandler::class,
            'transfer' => \App\Services\Orders\Handlers\TransferOrderHandler::class,
            'internal-transfer' => \App\Services\Orders\Handlers\TransferOrderHandler::class,
            'daxili-yerdeyisme' => \App\Services\Orders\Handlers\TransferOrderHandler::class,
            'termination' => \App\Services\Orders\Handlers\TerminationOrderHandler::class,
            'dismissal' => \App\Services\Orders\Handlers\TerminationOrderHandler::class,
            'isden-ayrilma' => \App\Services\Orders\Handlers\TerminationOrderHandler::class,
        ],
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

    'listing' => [
        /*
         * Order IDs visible globally in orders listing, without structure-based
         * personnel scope filtering.
         */
        'global_visible_order_ids' => array_values(array_filter(array_map(
            static fn ($id) => (int) trim((string) $id),
            explode(',', (string) env('ORDERS_GLOBAL_VISIBLE_ORDER_IDS', (string) \App\Models\Order::IG_EMR))
        ), static fn ($id) => $id > 0)),
    ],

    'observability' => [
        'list_render_budget' => [
            'orders_render' => [
                'response_bytes' => (int) env('ORDERS_LIST_RENDER_BUDGET_RENDER_RESPONSE', 180000),
                'render_ms' => (int) env('ORDERS_LIST_RENDER_BUDGET_RENDER_MS', 800),
            ],
            'orders_filter_update' => [
                'response_bytes' => (int) env('ORDERS_LIST_RENDER_BUDGET_FILTER_RESPONSE', 180000),
                'render_ms' => (int) env('ORDERS_LIST_RENDER_BUDGET_FILTER_MS', 400),
            ],
        ],
        'list_query_budget' => [
            'orders_render' => (int) env('ORDERS_LIST_QUERY_BUDGET_RENDER', 14),
            'orders_filter_update' => (int) env('ORDERS_LIST_QUERY_BUDGET_FILTER_UPDATE', 28),
        ],
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

    'variables' => [
        'groups' => [
            'order' => [
                'label' => 'Əmr məlumatları',
                'variables' => [
                    ['key' => 'order_no', 'label' => 'Əmr nömrəsi', 'type' => 'string'],
                    ['key' => 'given_date', 'label' => 'Əmrin tarixi', 'type' => 'date'],
                    ['key' => 'name_director', 'label' => 'İmzalayan şəxsin adı', 'type' => 'string'],
                    ['key' => 'rank_director', 'label' => 'İmzalayan şəxsin rütbəsi/vəzifəsi', 'type' => 'string'],
                ],
            ],
            'personnel' => [
                'label' => 'Əməkdaş məlumatları',
                'variables' => [
                    ['key' => 'fullname', 'label' => 'Əməkdaşın SAA', 'type' => 'string'],
                    ['key' => 'surname', 'label' => 'Soyad', 'type' => 'string'],
                    ['key' => 'name', 'label' => 'Ad', 'type' => 'string'],
                    ['key' => 'patronymic', 'label' => 'Ata adı', 'type' => 'string'],
                    ['key' => 'gender_suffix', 'label' => 'Oğlu/qızı şəkilçisi', 'type' => 'string'],
                    ['key' => 'tabel_no', 'label' => 'Tabel nömrəsi', 'type' => 'string'],
                    ['key' => 'position', 'label' => 'Vəzifə', 'type' => 'string'],
                    ['key' => 'structure', 'label' => 'Struktur', 'type' => 'string'],
                    ['key' => 'rank', 'label' => 'Rütbə', 'type' => 'string'],
                ],
            ],
            'date' => [
                'label' => 'Tarix məlumatları',
                'variables' => [
                    ['key' => 'day', 'label' => 'Gün', 'type' => 'number'],
                    ['key' => 'month', 'label' => 'Ay', 'type' => 'string'],
                    ['key' => 'year', 'label' => 'İl', 'type' => 'number'],
                    ['key' => 'start_date', 'label' => 'Başlama tarixi', 'type' => 'date'],
                    ['key' => 'end_date', 'label' => 'Bitmə tarixi', 'type' => 'date'],
                    ['key' => 'days', 'label' => 'Gün sayı', 'type' => 'number'],
                ],
            ],
        ],
    ],
];
