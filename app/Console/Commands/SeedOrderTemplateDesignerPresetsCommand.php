<?php

namespace App\Console\Commands;

use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateDesignerPresetService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedOrderTemplateDesignerPresetsCommand extends Command
{
    protected $signature = 'orders:templates:seed-designer-presets
        {--order-type= : Limit to a specific order_type_id}
        {--force : Replace existing designer blocks}';

    protected $description = 'Create designer-layout order template presets for order types';

    public function handle(
        OrderTemplateDesignerPresetService $presets,
        TemplateRegistry $registry
    ): int {
        if (! $this->isReady()) {
            $this->error('Template designer tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $orderTypeId = (int) ($this->option('order-type') ?? 0);
        $force = (bool) $this->option('force');

        $query = OrderType::query()->with('templateSet.activeVersion', 'order')->orderBy('id');
        if ($orderTypeId > 0) {
            $query->whereKey($orderTypeId);
        }

        $rows = [];

        foreach ($query->get() as $orderType) {
            $result = DB::transaction(function () use ($orderType, $presets, $force, $registry): array {
                $set = OrderTemplateSet::query()->firstOrCreate(
                    ['order_type_id' => $orderType->id],
                    [
                        'name' => (string) $orderType->name,
                        'description' => 'Designer layout template set',
                    ]
                );

                /** @var OrderTemplateVersion|null $version */
                $version = $set->versions()->where('is_active', true)->orderByDesc('version_no')->first();
                if (! $version) {
                    $version = $set->versions()->create([
                        'version_no' => ((int) ($set->versions()->max('version_no') ?? 0)) + 1,
                        'template_name' => (string) $orderType->name,
                        'template_path' => '',
                        'render_mode' => 'designer_layout',
                        'status' => 'published',
                        'is_active' => true,
                        'published_at' => now(),
                        'meta' => ['source' => 'orders:templates:seed-designer-presets'],
                    ]);
                }

                $preset = $presets->presetForOrderType(
                    (string) ($orderType->code ?? ''),
                    (string) $orderType->name
                );

                $stats = $presets->applyPreset($version, $preset, $force);
                $version->refresh()->load('blocks.variables');

                $registry->invalidate((int) $orderType->id);

                return [
                    'order_type_id' => (int) $orderType->id,
                    'order_type' => (string) $orderType->name,
                    'preset' => (string) ($preset['key'] ?? 'generic'),
                    'version_id' => (int) $version->id,
                    'blocks' => (int) $stats['blocks'],
                    'variables' => (int) $stats['variables'],
                ];
            });

            $rows[] = $result;
        }

        $this->table(
            ['order_type_id', 'order_type', 'preset', 'version_id', 'blocks', 'variables'],
            $rows
        );

        $this->info('Designer presets are ready.');

        return self::SUCCESS;
    }

    private function isReady(): bool
    {
        return Schema::hasTable('order_types')
            && Schema::hasTable('order_template_sets')
            && Schema::hasTable('order_template_versions')
            && Schema::hasTable('order_template_blocks')
            && Schema::hasColumn('order_template_versions', 'render_mode');
    }
}
