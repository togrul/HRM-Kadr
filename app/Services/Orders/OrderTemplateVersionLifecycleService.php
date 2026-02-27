<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use Illuminate\Support\Facades\DB;

class OrderTemplateVersionLifecycleService
{
    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly OrderTemplateFormSchemaService $schemaService,
        private readonly OrderTemplateAuditLogger $auditLogger,
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
            $nextVersionNo = ((int) ($set->versions()->max('version_no') ?? 0)) + 1;

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
            ->with('templateSet')
            ->find($versionId);

        if (! $version || ! $version->templateSet) {
            return null;
        }

        $orderTypeId = (int) $version->templateSet->order_type_id;

        DB::transaction(function () use ($version, $actorId): void {
            OrderTemplateVersion::query()
                ->where('order_template_set_id', (int) $version->order_template_set_id)
                ->lockForUpdate()
                ->get(['id']);

            OrderTemplateVersion::query()
                ->where('order_template_set_id', (int) $version->order_template_set_id)
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
        $versions = OrderTemplateVersion::query()
            ->where('order_template_set_id', $templateSetId)
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->get();

        if ($versions->isEmpty()) {
            return null;
        }

        $activeVersions = $versions->where('is_active', true);

        $winner = $activeVersions->isNotEmpty()
            ? $activeVersions->sortByDesc('version_no')->first()
            : ($versions->firstWhere('status', 'published') ?? $versions->first());

        if (! $winner) {
            return null;
        }

        $needsReconcile = $activeVersions->count() !== 1 || (int) $activeVersions->first()?->id !== (int) $winner->id;

        if (! $needsReconcile) {
            return $winner;
        }

        DB::transaction(function () use ($templateSetId, $winner, $actorId): void {
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
        });

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

        return OrderTemplateVersion::query()->find((int) $winner->id);
    }

    public function deleteDraftVersion(int $versionId, ?int $actorId = null): bool
    {
        $version = OrderTemplateVersion::query()
            ->with('templateSet')
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

        $remainingVersions = OrderTemplateVersion::query()
            ->where('order_template_set_id', (int) $version->order_template_set_id)
            ->count();

        if ($remainingVersions <= 1) {
            return false;
        }

        $orderTypeId = (int) $version->templateSet->order_type_id;

        DB::transaction(function () use ($version): void {
            $version->delete();
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId);

        return true;
    }
}
