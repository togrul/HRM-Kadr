<?php

namespace App\Console\Commands;

use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillOrderTemplateRegistry extends Command
{
    protected $signature = 'orders:templates:backfill
        {--dry-run : Preview changes without writing}
        {--force : Always create a new active version even when template path is unchanged}';

    protected $description = 'Backfill order template sets/versions from current order_types + orders records';

    public function handle(TemplateRegistry $registry): int
    {
        if (! Schema::hasTable('order_template_sets') || ! Schema::hasTable('order_template_versions')) {
            $this->error('Required tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $stats = [
            'types_total' => 0,
            'sets_created' => 0,
            'versions_created' => 0,
            'skipped_no_template' => 0,
            'skipped_already_active' => 0,
        ];

        $types = OrderType::query()->with('order')->orderBy('id')->get();
        $stats['types_total'] = $types->count();

        foreach ($types as $type) {
            $order = $type->order;
            $storedPath = trim((string) ($order?->content ?? ''));

            if (! $order || $storedPath === '') {
                $stats['skipped_no_template']++;
                $this->warn("Skipped order_type #{$type->id}: no order/content path.");
                continue;
            }

            $set = OrderTemplateSet::query()->where('order_type_id', $type->id)->first();
            $setCreated = false;

            if (! $set && ! $dryRun) {
                $set = OrderTemplateSet::query()->create([
                    'order_type_id' => $type->id,
                    'name' => $type->name,
                    'description' => "Backfilled from order_id={$order->id}",
                ]);
                $setCreated = true;
            }

            if (! $set && $dryRun) {
                $setCreated = true;
            }

            $activeVersion = $set
                ? $set->versions()->where('is_active', true)->orderByDesc('version_no')->first()
                : null;

            if (! $force && $activeVersion && (string) $activeVersion->template_path === $storedPath) {
                $stats['skipped_already_active']++;
                $this->line("No-op order_type #{$type->id}: active version already uses same template.");
                continue;
            }

            $nextVersionNo = $set
                ? ((int) ($set->versions()->max('version_no') ?? 0) + 1)
                : 1;

            if ($dryRun) {
                if ($setCreated) {
                    $stats['sets_created']++;
                }

                $stats['versions_created']++;
                $this->line(
                    "DRY-RUN order_type #{$type->id}: " .
                    ($setCreated ? 'create set, ' : '') .
                    "create version v{$nextVersionNo} from {$storedPath}"
                );
                continue;
            }

            DB::transaction(function () use ($set, $activeVersion, $nextVersionNo, $storedPath, $order, $type): void {
                if ($activeVersion) {
                    $activeVersion->update([
                        'is_active' => false,
                        'status' => $activeVersion->status === 'published' ? 'archived' : $activeVersion->status,
                    ]);
                }

                $createdVersion = $set->versions()->create([
                    'version_no' => $nextVersionNo,
                    'template_name' => $order->name,
                    'template_path' => $storedPath,
                    'checksum' => $this->resolveChecksum($storedPath),
                    'status' => 'published',
                    'is_active' => true,
                    'published_at' => now(),
                    'meta' => [
                        'order_id' => (int) $order->id,
                        'order_type_id' => (int) $type->id,
                        'blade' => (string) ($order->blade ?? ''),
                        'source' => 'orders:templates:backfill',
                    ],
                    'created_by' => null,
                    'updated_by' => null,
                ]);

                $createdVersion->audits()->create([
                    'action' => 'backfill',
                    'changed_by' => null,
                    'payload' => [
                        'previous_active_version_id' => $activeVersion?->id,
                        'template_path' => $storedPath,
                        'version_no' => $nextVersionNo,
                    ],
                ]);
            });

            if ($setCreated) {
                $stats['sets_created']++;
            }

            $stats['versions_created']++;
            $registry->invalidate((int) $type->id);
            $this->info("Backfilled order_type #{$type->id}: created active version v{$nextVersionNo}.");
        }

        $this->table(
            ['metric', 'value'],
            collect($stats)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
        );

        return self::SUCCESS;
    }

    private function resolveChecksum(string $storedPath): ?string
    {
        $resolvedPath = $this->resolveTemplatePath($storedPath);
        if (! $resolvedPath || ! is_file($resolvedPath)) {
            return null;
        }

        $checksum = @hash_file('sha256', $resolvedPath);

        return is_string($checksum) ? $checksum : null;
    }

    private function resolveTemplatePath(string $storedPath): ?string
    {
        if (is_file($storedPath)) {
            return $storedPath;
        }

        $path = ltrim($storedPath, '/');
        $candidates = [
            storage_path($path),
            storage_path('app/' . $path),
            public_path('storage/' . $path),
            base_path('storage/' . $path),
            base_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
