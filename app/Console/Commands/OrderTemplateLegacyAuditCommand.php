<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class OrderTemplateLegacyAuditCommand extends Command
{
    protected $signature = 'orders:templates:legacy-audit
        {--json : Print report as JSON}';

    protected $description = 'Audit no-legacy readiness and remaining legacy-era data footprints';

    public function handle(): int
    {
        $hasTemplateTables = Schema::hasTable('order_template_sets')
            && Schema::hasTable('order_template_versions')
            && Schema::hasTable('order_template_fields')
            && Schema::hasTable('order_template_mappings');

        $rows = [
            'strict_mode' => config('orders.engine.strict_mode', false) ? 'enabled' : 'disabled',
            'template_tables_ready' => $hasTemplateTables ? 'yes' : 'no',
            'order_types_total' => OrderType::query()->count(),
            'order_types_without_template_set' => OrderType::query()
                ->whereDoesntHave('templateSet')
                ->count(),
            'active_versions_total' => $hasTemplateTables
                ? OrderTemplateVersion::query()->where('is_active', true)->count()
                : 0,
            'active_versions_without_row_mapping' => $hasTemplateTables
                ? OrderTemplateVersion::query()
                    ->where('is_active', true)
                    ->whereDoesntHave('mappings', fn ($q) => $q->where('scope', 'row'))
                    ->count()
                : 0,
            'legacy_snapshot_orders' => Schema::hasColumn('order_logs', 'template_snapshot_source')
                ? OrderLog::query()->where('template_snapshot_source', 'legacy')->count()
                : 0,
            'orders_content_non_empty' => Schema::hasColumn('orders', 'content')
                ? Order::query()->whereNotNull('content')->where('content', '!=', '')->count()
                : 0,
            'components_dynamic_fields_non_empty' => Schema::hasColumn('components', 'dynamic_fields')
                ? Component::query()->whereNotNull('dynamic_fields')->count()
                : 0,
            'template_sets_total' => $hasTemplateTables ? OrderTemplateSet::query()->count() : 0,
            'mappings_total' => $hasTemplateTables ? OrderTemplateMapping::query()->count() : 0,
        ];

        $recommended = [
            'Ensure strict mode stays enabled in production.',
            'Keep orders.content/components.dynamic_fields only while runtime flows still depend on them; metadata bootstrap is already placeholder-driven.',
            'Target legacy_snapshot_orders = 0 for full no-legacy freeze.',
            'After onboarding bootstrap removal, plan explicit migration to drop unused legacy columns.',
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $rows,
                'recommended_actions' => $recommended,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(
            ['metric', 'value'],
            collect($rows)->map(fn ($value, $key) => [$key, (string) $value])->values()->all()
        );

        $this->newLine();
        $this->info('Recommended next actions:');
        foreach ($recommended as $item) {
            $this->line('- '.$item);
        }

        return self::SUCCESS;
    }
}
