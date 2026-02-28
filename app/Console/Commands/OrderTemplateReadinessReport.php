<?php

namespace App\Console\Commands;

use App\Models\OrderType;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class OrderTemplateReadinessReport extends Command
{
    protected $signature = 'orders:templates:readiness
        {--json : Print report as JSON}';

    protected $description = 'Show metadata-readiness status for order template rendering by order type';

    public function handle(TemplateRegistry $templateRegistry): int
    {
        if (! Schema::hasTable('order_template_sets') || ! Schema::hasTable('order_template_versions')) {
            $this->error('Template engine tables are missing. Run migrations first.');
            return self::FAILURE;
        }

        $types = OrderType::query()
            ->with([
                'order:id,name,content,blade',
                'templateSet.versions' => fn ($query) => $query
                    ->withCount(['fields', 'mappings'])
                    ->orderByDesc('version_no'),
            ])
            ->orderBy('id')
            ->get();

        $rows = $types->map(function (OrderType $type) {
            $activeVersion = $type->templateSet
                ?->versions
                ?->first(fn ($version) => (bool) $version->is_active);

            $fieldsCount = (int) ($activeVersion?->fields_count ?? 0);
            $mappingsCount = (int) ($activeVersion?->mappings_count ?? 0);
            $hasActiveTemplate = ! empty($activeVersion?->template_path);
            $hasTemplateSet = $type->templateSet !== null;

            $status = match (true) {
                $hasActiveTemplate && $mappingsCount > 0 => 'metadata_ready',
                $hasActiveTemplate && $mappingsCount === 0 => 'version_without_mappings',
                $hasTemplateSet => 'version_not_active',
                default => 'no_template_set',
            };

            return [
                'order_type_id' => (int) $type->id,
                'name' => (string) $type->name,
                'blade' => (string) ($type->order?->blade ?? ''),
                'active_version' => $activeVersion?->version_no,
                'fields' => $fieldsCount,
                'mappings' => $mappingsCount,
                'render_mode' => $status === 'metadata_ready' ? 'metadata' : 'blocked',
                'status' => $status,
            ];
        })->values();
        $rows = $rows->map(function (array $row) use ($templateRegistry) {
            $orderTypeId = (int) ($row['order_type_id'] ?? 0);

            $row['has_template_set'] = $templateRegistry->hasTemplateSetForOrderType($orderTypeId) ? 'yes' : 'no';
            $row['legacy_form'] = $orderTypeId > 0 ? 'blocked' : 'n/a';
            $row['legacy_print'] = $orderTypeId > 0 ? 'blocked' : 'n/a';

            return $row;
        })->values();

        $summary = [
            'order_types_total' => $rows->count(),
            'metadata_ready' => $rows->where('status', 'metadata_ready')->count(),
            'version_without_mappings' => $rows->where('status', 'version_without_mappings')->count(),
            'version_not_active' => $rows->where('status', 'version_not_active')->count(),
            'no_template_set' => $rows->where('status', 'no_template_set')->count(),
            'legacy_form_blocked' => $rows->where('legacy_form', 'blocked')->count(),
            'legacy_print_blocked' => $rows->where('legacy_print', 'blocked')->count(),
            'strict_mode' => $templateRegistry->strictModeEnabled() ? 'enabled' : 'disabled',
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'rows' => $rows->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(
            ['order_type_id', 'name', 'blade', 'active_version', 'fields', 'mappings', 'render_mode', 'status', 'has_template_set', 'legacy_form', 'legacy_print'],
            $rows->all()
        );

        $this->newLine();
        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, (string) $value])->values()->all()
        );

        return self::SUCCESS;
    }
}
