<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use Illuminate\Support\Collection;

class OrderTemplateSnapshotService
{
    public function __construct(private readonly TemplateRegistry $templateRegistry)
    {
    }

    public function capture(?int $orderTypeId, ?string $legacyTemplatePath = null): array
    {
        $resolvedOrderTypeId = is_numeric($orderTypeId) ? (int) $orderTypeId : 0;
        $resolvedLegacyPath = trim((string) ($legacyTemplatePath ?? ''));

        if ($resolvedOrderTypeId <= 0) {
            return [
                'order_template_version_id' => null,
                'template_render_mode' => 'legacy',
                'template_snapshot' => $this->buildLegacySnapshot($resolvedLegacyPath),
            ];
        }

        $version = $this->templateRegistry->activeVersionForOrderType($resolvedOrderTypeId);
        if (! $version) {
            return [
                'order_template_version_id' => null,
                'template_render_mode' => 'legacy',
                'template_snapshot' => $this->buildLegacySnapshot($resolvedLegacyPath),
            ];
        }

        $version->loadMissing([
            'templateSet:id,order_type_id,name',
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $hasRowMappings = $version->mappings->contains(
            fn (OrderTemplateMapping $mapping) => (string) $mapping->scope !== 'scalar'
        );
        $renderMode = $hasRowMappings ? 'metadata' : 'legacy';
        $templatePath = trim((string) $version->template_path) !== ''
            ? (string) $version->template_path
            : $resolvedLegacyPath;

        return [
            'order_template_version_id' => (int) $version->id,
            'template_render_mode' => $renderMode,
            'template_snapshot' => [
                'captured_at' => now()->toISOString(),
                'render_mode' => $renderMode,
                'template_path' => $templatePath,
                'version' => [
                    'id' => (int) $version->id,
                    'order_template_set_id' => (int) $version->order_template_set_id,
                    'order_type_id' => (int) ($version->templateSet?->order_type_id ?? $resolvedOrderTypeId),
                    'version_no' => (int) $version->version_no,
                    'status' => (string) $version->status,
                    'is_active' => (bool) $version->is_active,
                    'published_at' => $version->published_at?->toISOString(),
                    'meta' => is_array($version->meta) ? $version->meta : [],
                ],
                'fields' => $version->fields
                    ->map(fn (OrderTemplateField $field) => [
                        'id' => (int) $field->id,
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
                    ])
                    ->values()
                    ->all(),
                'mappings' => $version->mappings
                    ->map(fn (OrderTemplateMapping $mapping) => [
                        'id' => (int) $mapping->id,
                        'placeholder' => (string) $mapping->placeholder,
                        'field_key' => (string) $mapping->field_key,
                        'scope' => (string) $mapping->scope,
                        'sort_order' => (int) $mapping->sort_order,
                        'mapping_config' => is_array($mapping->mapping_config) ? $mapping->mapping_config : null,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    public function versionFromSnapshot(array $snapshot): ?OrderTemplateVersion
    {
        $versionData = data_get($snapshot, 'version');
        if (! is_array($versionData)) {
            return null;
        }

        $fields = $this->normalizeSnapshotRows(data_get($snapshot, 'fields'));
        $mappings = $this->normalizeSnapshotRows(data_get($snapshot, 'mappings'));
        if ($fields->isEmpty() || $mappings->isEmpty()) {
            return null;
        }

        $version = new OrderTemplateVersion([
            'order_template_set_id' => (int) ($versionData['order_template_set_id'] ?? 0),
            'version_no' => (int) ($versionData['version_no'] ?? 0),
            'template_path' => (string) data_get($snapshot, 'template_path', ''),
            'status' => (string) ($versionData['status'] ?? 'published'),
            'is_active' => (bool) ($versionData['is_active'] ?? false),
            'meta' => is_array($versionData['meta'] ?? null) ? $versionData['meta'] : [],
        ]);
        $version->id = is_numeric($versionData['id'] ?? null) ? (int) $versionData['id'] : null;

        $version->setRelation('fields', $fields->map(fn (array $row) => new OrderTemplateField($row)));
        $version->setRelation('mappings', $mappings->map(fn (array $row) => new OrderTemplateMapping($row)));

        return $version;
    }

    private function buildLegacySnapshot(string $legacyTemplatePath): array
    {
        return [
            'captured_at' => now()->toISOString(),
            'render_mode' => 'legacy',
            'template_path' => $legacyTemplatePath,
            'version' => null,
            'fields' => [],
            'mappings' => [],
        ];
    }

    private function normalizeSnapshotRows(mixed $rows): Collection
    {
        if (! is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->values();
    }
}

