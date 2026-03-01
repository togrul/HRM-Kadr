<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use Illuminate\Support\Collection;
use RuntimeException;

class OrderTemplateSnapshotService
{
    public function __construct(private readonly TemplateRegistry $templateRegistry)
    {
    }

    public function capture(?int $orderTypeId): array
    {
        $resolvedOrderTypeId = is_numeric($orderTypeId) ? (int) $orderTypeId : 0;

        if ($resolvedOrderTypeId <= 0) {
            throw new RuntimeException('Order type is required for template snapshot capture.');
        }

        $version = $this->templateRegistry->activeVersionForOrderType($resolvedOrderTypeId);
        if (! $version) {
            throw new RuntimeException('Active metadata template version not found for this order type.');
        }

        $version->loadMissing([
            'templateSet:id,order_type_id,name',
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $hasRowMappings = $version->mappings->contains(
            fn (OrderTemplateMapping $mapping) => (string) $mapping->scope !== 'scalar'
        );

        if (! $hasRowMappings) {
            throw new RuntimeException('Metadata template mappings are required for this order type.');
        }

        $templatePath = trim((string) $version->template_path);
        if ($templatePath === '') {
            throw new RuntimeException('Active metadata template file is missing for this order type.');
        }

        return [
            'order_template_version_id' => (int) $version->id,
            'template_render_mode' => 'metadata',
            'template_snapshot' => [
                'captured_at' => now()->toISOString(),
                'render_mode' => 'metadata',
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

        $version->setRelation('fields', $fields->map(fn (array $row) => $this->hydrateFieldFromSnapshot($row)));
        $version->setRelation('mappings', $mappings->map(fn (array $row) => $this->hydrateMappingFromSnapshot($row)));

        return $version;
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

    private function hydrateFieldFromSnapshot(array $row): OrderTemplateField
    {
        $field = new OrderTemplateField;
        $field->forceFill($row);

        return $field;
    }

    private function hydrateMappingFromSnapshot(array $row): OrderTemplateMapping
    {
        $mapping = new OrderTemplateMapping;
        $mapping->forceFill($row);

        return $mapping;
    }
}
