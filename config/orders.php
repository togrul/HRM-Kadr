<?php

return [
    'engine' => [
        /*
         * Compatibility mirror for the legacy order_log_components and
         * order_log_component_attributes tables, still written by the Personnel
         * self-service vacation order binder (OrderLegacyComponentSnapshotPersister).
         */
        'write_legacy_component_snapshots' => (bool) env('ORDERS_ENGINE_WRITE_LEGACY_COMPONENT_SNAPSHOTS', true),
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
    ],
];
