<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SetTypeUiConfigWriteUseCase
{
    public function __construct(
        private readonly OrderTemplateRegistry $templateRegistry,
        private readonly OrderTemplateFormSchemaService $schemaService,
        private readonly OrderTemplateAuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string,mixed>  $uiConfig
     */
    public function addMetadataField(
        int $orderTypeId,
        int $versionId,
        string $fieldKey,
        string $fieldLabel,
        string $fieldType,
        bool $isRequired,
        int $nextSort,
        array $uiConfig,
        string $rules,
        ?int $actorId
    ): void {
        DB::transaction(function () use ($versionId, $fieldKey, $fieldLabel, $fieldType, $isRequired, $nextSort, $uiConfig, $rules): void {
            OrderTemplateField::query()->create([
                'order_template_version_id' => $versionId,
                'field_key' => $fieldKey,
                'label' => $fieldLabel,
                'field_type' => $fieldType,
                'is_required' => $isRequired,
                'sort_order' => $nextSort,
                'default_value' => null,
                'data_source' => null,
                'ui_config' => $uiConfig,
                'transform_config' => null,
                'validation_config' => ['rules' => $rules],
            ]);

            OrderTemplateMapping::query()->firstOrCreate(
                [
                    'order_template_version_id' => $versionId,
                    'placeholder' => '$'.$fieldKey,
                    'scope' => 'row',
                ],
                [
                    'field_key' => $fieldKey,
                    'sort_order' => $nextSort,
                    'mapping_config' => null,
                ]
            );
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId, $versionId);
        $this->auditLogger->log($versionId, 'metadata_field_added', [
            'field_key' => $fieldKey,
            'input' => data_get($uiConfig, 'input'),
            'required' => $isRequired,
        ], $actorId);
    }

    public function removeMetadataField(int $orderTypeId, int $versionId, int $fieldId, ?int $actorId): bool
    {
        $field = OrderTemplateField::query()
            ->where('order_template_version_id', $versionId)
            ->find($fieldId);

        if (! $field) {
            return false;
        }

        $fieldKey = (string) $field->field_key;

        DB::transaction(function () use ($field, $versionId, $fieldKey): void {
            $field->delete();

            OrderTemplateMapping::query()
                ->where('order_template_version_id', $versionId)
                ->where('field_key', $fieldKey)
                ->delete();
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId, $versionId);
        $this->auditLogger->log($versionId, 'metadata_field_removed', [
            'field_key' => $fieldKey,
            'field_id' => $fieldId,
        ], $actorId);

        return true;
    }

    /**
     * @param  EloquentCollection<int, OrderTemplateField>  $fields
     * @param  array<int,array{field_type:string,is_required:bool,validation_config:array<string,mixed>|null,ui_config:array<string,mixed>}>  $fieldUpdatePayloads
     * @param  array<int,array{key:string,title:string,enabled:bool,order:int}>  $sectionBlocks
     * @param  Collection<int,array{placeholder:string,field_key:string,scope:string,sort_order:int,mapping_config:array<string,mixed>|null}>  $normalizedMappings
     */
    public function saveUiConfig(
        int $orderTypeId,
        int $versionId,
        EloquentCollection $fields,
        array $fieldUpdatePayloads,
        array $sectionBlocks,
        Collection $normalizedMappings
    ): ?OrderTemplateVersion {
        $templateVersion = OrderTemplateVersion::query()
            ->with([
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find($versionId);

        if (! $templateVersion) {
            return null;
        }

        DB::transaction(function () use ($fields, $fieldUpdatePayloads, $templateVersion, $normalizedMappings, $sectionBlocks): void {
            foreach ($fields as $field) {
                $payload = $fieldUpdatePayloads[(int) $field->id] ?? null;
                if (! is_array($payload)) {
                    continue;
                }

                $field->update([
                    'field_type' => (string) ($payload['field_type'] ?? $field->field_type),
                    'is_required' => (bool) ($payload['is_required'] ?? $field->is_required),
                    'validation_config' => $payload['validation_config'] ?? $field->validation_config,
                    'ui_config' => $payload['ui_config'] ?? $field->ui_config,
                ]);
            }

            $meta = is_array($templateVersion->meta) ? $templateVersion->meta : [];
            data_set($meta, 'form.section_blocks', collect($sectionBlocks)->sortBy('order')->values()->all());
            $templateVersion->update(['meta' => $meta]);

            OrderTemplateMapping::query()
                ->where('order_template_version_id', (int) $templateVersion->id)
                ->delete();

            foreach ($normalizedMappings as $mapping) {
                OrderTemplateMapping::query()->create([
                    'order_template_version_id' => (int) $templateVersion->id,
                    'placeholder' => $mapping['placeholder'],
                    'field_key' => $mapping['field_key'],
                    'scope' => $mapping['scope'],
                    'sort_order' => $mapping['sort_order'],
                    'mapping_config' => $mapping['mapping_config'],
                ]);
            }
        });

        $templateVersion->refresh()->load([
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId, $versionId);

        return $templateVersion;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function logUiConfigSaved(int $versionId, array $payload, ?int $actorId): void
    {
        $this->auditLogger->log($versionId, 'ui_config_saved', $payload, $actorId);
    }
}
