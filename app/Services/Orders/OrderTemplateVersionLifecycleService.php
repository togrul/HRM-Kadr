<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderTemplateVersionLifecycleService
{
    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly OrderTemplateFormSchemaService $schemaService,
        private readonly OrderTemplateAuditLogger $auditLogger,
        private readonly TemplatePlaceholderCoverageService $coverageService,
    ) {
    }

    public function createDraftFromVersion(int $orderTypeId, ?int $baseVersionId = null, ?int $actorId = null): ?OrderTemplateVersion
    {
        $set = OrderTemplateSet::query()
            ->with(['versions' => fn ($query) => $query->orderByDesc('version_no')])
            ->where('order_type_id', $orderTypeId)
            ->first();

        if (! $set) {
            return null;
        }

        $baseVersion = $baseVersionId
            ? $set->versions->firstWhere('id', $baseVersionId)
            : $set->versions->firstWhere('is_active', true);

        if (! $baseVersion) {
            $baseVersion = $set->versions->first();
        }

        if (! $baseVersion) {
            return null;
        }

        $baseVersion->loadMissing([
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $newVersion = DB::transaction(function () use ($set, $baseVersion, $actorId) {
            OrderTemplateSet::query()
                ->whereKey((int) $set->id)
                ->lockForUpdate()
                ->first(['id']);

            /** @var EloquentCollection<int, OrderTemplateVersion> $lockedVersions */
            $lockedVersions = OrderTemplateVersion::query()
                ->where('order_template_set_id', (int) $set->id)
                ->lockForUpdate()
                ->get(['id', 'version_no']);

            $nextVersionNo = ((int) ($lockedVersions->max('version_no') ?? 0)) + 1;

            /** @var OrderTemplateVersion $created */
            $created = $set->versions()->create([
                'version_no' => $nextVersionNo,
                'template_name' => $baseVersion->template_name,
                'template_path' => $baseVersion->template_path,
                'checksum' => $baseVersion->checksum,
                'status' => 'draft',
                'is_active' => false,
                'published_at' => null,
                'meta' => is_array($baseVersion->meta) ? $baseVersion->meta : [],
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            foreach ($baseVersion->fields as $field) {
                OrderTemplateField::query()->create([
                    'order_template_version_id' => (int) $created->id,
                    'field_key' => (string) $field->field_key,
                    'label' => (string) $field->label,
                    'field_type' => (string) $field->field_type,
                    'is_required' => (bool) $field->is_required,
                    'sort_order' => (int) $field->sort_order,
                    'default_value' => $field->default_value,
                    'data_source' => is_array($field->data_source) ? $field->data_source : null,
                    'ui_config' => is_array($field->ui_config) ? $field->ui_config : null,
                    'transform_config' => is_array($field->transform_config) ? $field->transform_config : null,
                    'validation_config' => is_array($field->validation_config) ? $field->validation_config : null,
                ]);
            }

            foreach ($baseVersion->mappings as $mapping) {
                OrderTemplateMapping::query()->create([
                    'order_template_version_id' => (int) $created->id,
                    'placeholder' => (string) $mapping->placeholder,
                    'field_key' => (string) $mapping->field_key,
                    'scope' => (string) $mapping->scope,
                    'sort_order' => (int) $mapping->sort_order,
                    'mapping_config' => is_array($mapping->mapping_config) ? $mapping->mapping_config : null,
                ]);
            }

            return $created;
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId);
        $this->auditLogger->log((int) $newVersion->id, 'version_drafted', [
            'order_type_id' => $orderTypeId,
            'base_version_id' => (int) $baseVersion->id,
            'new_version_no' => (int) $newVersion->version_no,
        ], $actorId);

        return $newVersion;
    }

    public function publishVersion(int $versionId, ?int $actorId = null, string $action = 'version_published'): ?OrderTemplateVersion
    {
        $version = OrderTemplateVersion::query()
            ->with([
                'templateSet:id,order_type_id',
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find($versionId);

        if (! $version || ! $version->templateSet) {
            return null;
        }

        $coverage = $this->coverageService->analyzeForVersion(
            $version,
            $version->mappings
                ->map(fn (OrderTemplateMapping $mapping) => [
                    'placeholder' => (string) $mapping->placeholder,
                    'field_key' => (string) $mapping->field_key,
                    'scope' => (string) $mapping->scope,
                ])
                ->values()
                ->all()
        );

        if (empty($coverage['inspectable'])) {
            throw new RuntimeException(__('orders::template_lifecycle.messages.template_coverage_unavailable'));
        }

        if (! empty($coverage['missing_placeholders'])) {
            throw new RuntimeException(__('orders::template_lifecycle.messages.cannot_publish_missing_mappings', [
                'placeholders' => implode(', ', $coverage['missing_placeholders']),
            ]));
        }

        $orderTypeId = (int) $version->templateSet->order_type_id;
        $templateSetId = (int) $version->order_template_set_id;

        DB::transaction(function () use ($version, $actorId, $templateSetId): void {
            /** @var EloquentCollection<int, OrderTemplateVersion> $lockedVersions */
            $lockedVersions = OrderTemplateVersion::query()
                ->where('order_template_set_id', $templateSetId)
                ->lockForUpdate()
                ->get(['id']);

            if ($lockedVersions->doesntContain(fn (OrderTemplateVersion $item) => (int) $item->id === (int) $version->id)) {
                throw new RuntimeException(__('orders::template_lifecycle.messages.selected_template_version_missing'));
            }

            OrderTemplateVersion::query()
                ->where('order_template_set_id', $templateSetId)
                ->where('id', '!=', (int) $version->id)
                ->update([
                    'is_active' => false,
                    'updated_by' => $actorId,
                ]);

            OrderTemplateVersion::query()
                ->where('id', (int) $version->id)
                ->update([
                    'status' => 'published',
                    'is_active' => true,
                    'published_at' => now(),
                    'updated_by' => $actorId,
                ]);

            $this->assertSingleActiveInvariant($templateSetId);
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId);
        $this->auditLogger->log((int) $version->id, $action, [
            'order_type_id' => $orderTypeId,
            'version_no' => (int) $version->version_no,
            'status' => 'published',
        ], $actorId);

        return OrderTemplateVersion::query()
            ->with('templateSet')
            ->find((int) $version->id);
    }

    public function rollbackToVersion(int $versionId, ?int $actorId = null): ?OrderTemplateVersion
    {
        return $this->publishVersion($versionId, $actorId, 'version_rolled_back');
    }

    public function reconcileSingleActiveForSet(int $templateSetId, ?int $actorId = null, ?int $orderTypeId = null): ?OrderTemplateVersion
    {
        $winnerId = DB::transaction(function () use ($templateSetId, $actorId): ?int {
            /** @var EloquentCollection<int, OrderTemplateVersion> $lockedVersions */
            $lockedVersions = OrderTemplateVersion::query()
                ->where('order_template_set_id', $templateSetId)
                ->orderByDesc('version_no')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get(['id', 'status', 'is_active', 'version_no']);

            if ($lockedVersions->isEmpty()) {
                return null;
            }

            $winner = $this->resolveWinnerFromVersions($lockedVersions);
            if (! $winner) {
                return null;
            }

            $activeVersions = $lockedVersions->where('is_active', true);
            $needsReconcile = $activeVersions->count() !== 1
                || (int) $activeVersions->first()?->id !== (int) $winner->id;

            if ($needsReconcile) {
                OrderTemplateVersion::query()
                    ->where('order_template_set_id', $templateSetId)
                    ->update([
                        'is_active' => false,
                        'updated_by' => $actorId,
                    ]);

                OrderTemplateVersion::query()
                    ->where('id', (int) $winner->id)
                    ->update([
                        'is_active' => true,
                        'updated_by' => $actorId,
                    ]);
            }

            $this->assertSingleActiveInvariant($templateSetId);

            return (int) $winner->id;
        });

        if (! $winnerId) {
            return null;
        }

        $resolvedOrderTypeId = $orderTypeId;
        if (! $resolvedOrderTypeId) {
            $resolvedOrderTypeId = (int) OrderTemplateSet::query()
                ->whereKey($templateSetId)
                ->value('order_type_id');
        }

        if ($resolvedOrderTypeId > 0) {
            $this->templateRegistry->invalidate($resolvedOrderTypeId);
            $this->schemaService->invalidateCachedSchema($resolvedOrderTypeId);
        }

        return OrderTemplateVersion::query()->find((int) $winnerId);
    }

    public function deleteDraftVersion(int $versionId, ?int $actorId = null): bool
    {
        $orderTypeId = 0;
        $deleted = DB::transaction(function () use ($versionId, $actorId, &$orderTypeId): bool {
            /** @var OrderTemplateVersion|null $version */
            $version = OrderTemplateVersion::query()
                ->with('templateSet:id,order_type_id')
                ->lockForUpdate()
                ->find($versionId);

            if (! $version || ! $version->templateSet) {
                return false;
            }

            if ((bool) $version->is_active) {
                return false;
            }

            if (! in_array((string) $version->status, ['draft', 'published'], true)) {
                return false;
            }

            $templateSetId = (int) $version->order_template_set_id;
            $orderTypeId = (int) $version->templateSet->order_type_id;

            /** @var EloquentCollection<int, OrderTemplateVersion> $lockedVersions */
            $lockedVersions = OrderTemplateVersion::query()
                ->where('order_template_set_id', $templateSetId)
                ->orderByDesc('version_no')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get(['id', 'status', 'is_active', 'version_no']);

            if ($lockedVersions->count() <= 1) {
                return false;
            }

            OrderTemplateVersion::query()->whereKey((int) $version->id)->delete();

            $remainingVersions = $lockedVersions->reject(fn (OrderTemplateVersion $item) => (int) $item->id === (int) $version->id);
            if ($remainingVersions->isNotEmpty() && $remainingVersions->where('is_active', true)->isEmpty()) {
                $winner = $this->resolveWinnerFromVersions($remainingVersions);
                if ($winner) {
                    OrderTemplateVersion::query()
                        ->whereKey((int) $winner->id)
                        ->update([
                            'is_active' => true,
                            'updated_by' => $actorId,
                        ]);
                }
            }

            if ($remainingVersions->isNotEmpty()) {
                $this->assertSingleActiveInvariant($templateSetId);
            }

            return true;
        });

        if (! $deleted || $orderTypeId <= 0) {
            return false;
        }

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId);

        return true;
    }

    private function resolveWinnerFromVersions(EloquentCollection $versions): ?OrderTemplateVersion
    {
        $activeVersions = $versions->where('is_active', true);
        if ($activeVersions->isNotEmpty()) {
            return $activeVersions->first();
        }

        return $versions->firstWhere('status', 'published') ?? $versions->first();
    }

    private function assertSingleActiveInvariant(int $templateSetId): void
    {
        $activeCount = OrderTemplateVersion::query()
            ->where('order_template_set_id', $templateSetId)
            ->where('is_active', true)
            ->count();

        if ($activeCount !== 1) {
            throw new RuntimeException(__('orders::template_lifecycle.messages.single_active_version_invariant_failed', [
                'template_set_id' => $templateSetId,
            ]));
        }
    }
}
