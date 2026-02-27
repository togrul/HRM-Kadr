<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Models\Component as OrderComponent;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\GenerateDynamicFieldsService;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HandlesSetTypeMetadataBootstrap
{
    private function ensureUiMetadataInitialized(
        OrderType $orderType,
        bool $syncMissing = false,
        bool $allowAutoCreateVersion = true
    ): ?OrderTemplateVersion
    {
        $set = $orderType->templateSet;
        if (! $set) {
            if (! $allowAutoCreateVersion) {
                return null;
            }

            $set = OrderTemplateSet::query()->create([
                'order_type_id' => (int) $orderType->id,
                'name' => (string) $orderType->name,
                'description' => 'Auto-created from UI config editor',
            ]);
        }

        $activeVersion = $set->relationLoaded('activeVersion')
            ? $set->getRelation('activeVersion')
            : $set->activeVersion()->first();
        if (! $activeVersion) {
            $loadedVersions = $set->relationLoaded('versions')
                ? collect($set->getRelation('versions'))->sortByDesc('id')->sortByDesc('version_no')->values()
                : null;

            $latestVersion = $loadedVersions
                ? $loadedVersions->first()
                : $set->versions()->orderByDesc('version_no')->orderByDesc('id')->first();

            // If versions exist but no active flag is set, recover by activating a winner.
            // This prevents accidental creation of extra versions from simple UI-open actions.
            if ($latestVersion) {
                $winner = $loadedVersions
                    ? ($loadedVersions->firstWhere('status', 'published') ?? $latestVersion)
                    : ($set->versions()
                        ->where('status', 'published')
                        ->orderByDesc('version_no')
                        ->orderByDesc('id')
                        ->first() ?? $latestVersion);

                DB::transaction(function () use ($set, $winner): void {
                    $set->versions()->update([
                        'is_active' => false,
                        'updated_by' => auth()->id(),
                    ]);

                    $winner->update([
                        'is_active' => true,
                        'updated_by' => auth()->id(),
                    ]);
                });

                $activeVersion = $winner->fresh();
            } else {
                if (! $allowAutoCreateVersion) {
                    return null;
                }

                $templatePath = trim((string) ($orderType->order?->content ?? ''));
                if ($templatePath === '') {
                    return null;
                }

                $nextVersionNo = 1;
                $activeVersion = $set->versions()->create([
                    'version_no' => $nextVersionNo,
                    'template_name' => (string) ($orderType->order?->name ?? $orderType->name),
                    'template_path' => $templatePath,
                    'checksum' => null,
                    'status' => 'published',
                    'is_active' => true,
                    'published_at' => now(),
                    'meta' => [
                        'order_id' => (int) ($orderType->order?->id ?? 0),
                        'order_type_id' => (int) $orderType->id,
                        'blade' => (string) ($orderType->order?->blade ?? ''),
                        'source' => 'ui_config_bootstrap',
                    ],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        $hasRowMappings = $activeVersion->relationLoaded('mappings')
            ? $activeVersion->mappings->where('scope', '!=', 'scalar')->isNotEmpty()
            : $activeVersion->mappings()->where('scope', '!=', 'scalar')->exists();

        $hasFields = $activeVersion->relationLoaded('fields')
            ? $activeVersion->fields->isNotEmpty()
            : $activeVersion->fields()->exists();
        $hasSectionBlocks = is_array(data_get($activeVersion->meta, 'form.section_blocks'));

        if ($syncMissing || ! $hasFields || ! $hasRowMappings || ! $hasSectionBlocks) {
            $this->bootstrapLegacyMetadata($orderType, $activeVersion, $syncMissing);
            $activeVersion->refresh();
        }

        return $activeVersion;
    }

    private function bootstrapLegacyMetadata(OrderType $orderType, OrderTemplateVersion $version, bool $strictSync = false): void
    {
        $legacyCatalog = app(GenerateDynamicFieldsService::class)->handle();

        $tokens = OrderComponent::query()
            ->where('order_type_id', $orderType->id)
            ->pluck('dynamic_fields')
            ->flatMap(fn ($fields) => $this->extractDynamicTokens($fields))
            ->filter(fn ($field) => is_string($field) && trim((string) $field) !== '')
            ->map(fn ($field) => '$'.ltrim(trim((string) $field), '$'))
            ->unique()
            ->values();

        if (! $strictSync && $tokens->isEmpty()) {
            $tokens = collect($this->fallbackTokensForBlade((string) ($orderType->order?->blade ?? '')));
        }

        $normalizedTokenKeys = $tokens
            ->map(fn ($token) => ltrim((string) $token, '$'))
            ->filter(fn ($token) => $token !== '')
            ->values();

        DB::transaction(function () use ($orderType, $version, $strictSync, $legacyCatalog, $tokens, $normalizedTokenKeys): void {
            $existingFields = OrderTemplateField::query()
                ->where('order_template_version_id', (int) $version->id)
                ->get()
                ->keyBy(fn (OrderTemplateField $field) => (string) $field->field_key);

            $existingMappings = OrderTemplateMapping::query()
                ->where('order_template_version_id', (int) $version->id)
                ->where('scope', 'row')
                ->get()
                ->keyBy(fn (OrderTemplateMapping $mapping) => (string) $mapping->placeholder.'|'.(string) $mapping->scope);

            foreach ($tokens as $index => $token) {
                $fieldKey = ltrim((string) $token, '$');
                if ($fieldKey === '') {
                    continue;
                }

                $legacyDefinition = is_array($legacyCatalog[$token] ?? null) ? $legacyCatalog[$token] : [];
                $resolvedField = (string) ($legacyDefinition['field'] ?? $fieldKey);
                $resolvedInput = $this->resolveLegacyInput($legacyDefinition);
                if ($resolvedField === 'structure_id' || $fieldKey === 'structure') {
                    $resolvedInput = 'radio-list';
                }
                $resolvedLabel = (string) ($legacyDefinition['title'] ?? Str::headline(str_replace('_', ' ', $fieldKey)));
                $fieldType = $this->mapInputToFieldType($resolvedInput);

                $uiConfig = array_filter([
                    'field' => $resolvedField,
                    'input' => $resolvedInput,
                    'model' => $legacyDefinition['model'] ?? null,
                    'selectedName' => $legacyDefinition['selectedName'] ?? null,
                    'searchField' => $legacyDefinition['searchField'] ?? null,
                    'group' => 'main',
                    'group_order' => 0,
                    'field_order' => (($index + 1) * 10),
                    'grid_cols' => ['default' => 1, 'sm' => 2, 'md' => 3],
                    'col_span' => ['default' => 1],
                ], static fn ($value) => $value !== null && $value !== '');

                $existingField = $existingFields->get($fieldKey);

                if (! $existingField) {
                    $createdField = OrderTemplateField::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'field_key' => $fieldKey,
                        'label' => $resolvedLabel,
                        'field_type' => $fieldType,
                        'is_required' => false,
                        'sort_order' => (($index + 1) * 10),
                        'default_value' => null,
                        'data_source' => null,
                        'ui_config' => $uiConfig,
                        'transform_config' => null,
                        'validation_config' => null,
                    ]);
                    $existingFields->put($fieldKey, $createdField);
                } else {
                    $currentUiConfig = is_array($existingField->ui_config) ? $existingField->ui_config : [];
                    $mergedUiConfig = $this->mergeUiConfigDefaults($currentUiConfig, $uiConfig);
                    $updates = [];

                    if ($existingField->field_type === null || $existingField->field_type === '') {
                        $updates['field_type'] = $fieldType;
                    }

                    if (trim((string) $existingField->label) === '') {
                        $updates['label'] = $resolvedLabel;
                    }

                    if ((int) $existingField->sort_order <= 0) {
                        $updates['sort_order'] = (($index + 1) * 10);
                    }

                    if ($mergedUiConfig !== $currentUiConfig) {
                        $updates['ui_config'] = $mergedUiConfig;
                    }

                    if (! empty($updates)) {
                        $existingField->update($updates);
                    }
                }

                $mappingKey = (string) $token.'|row';
                $existingMapping = $existingMappings->get($mappingKey);

                if (! $existingMapping) {
                    $createdMapping = OrderTemplateMapping::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'placeholder' => (string) $token,
                        'scope' => 'row',
                        'field_key' => $fieldKey,
                        'sort_order' => (($index + 1) * 10),
                        'mapping_config' => null,
                    ]);
                    $existingMappings->put($mappingKey, $createdMapping);
                }
            }

            if ($strictSync) {
                $normalizedKeySet = array_fill_keys($normalizedTokenKeys->all(), true);
                $tokenSet = array_fill_keys($tokens->all(), true);

                OrderTemplateMapping::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->where('scope', 'row')
                    ->get()
                    ->each(function (OrderTemplateMapping $mapping) use ($tokenSet): void {
                        $placeholder = (string) $mapping->placeholder;
                        if (! isset($tokenSet[$placeholder])) {
                            $mapping->delete();
                        }
                    });

                OrderTemplateField::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->get()
                    ->each(function (OrderTemplateField $field) use ($normalizedKeySet): void {
                        $fieldKey = (string) $field->field_key;
                        if (! isset($normalizedKeySet[$fieldKey])) {
                            $field->delete();
                        }
                    });
            }

            $meta = is_array($version->meta) ? $version->meta : [];
            if (! is_array(data_get($meta, 'form.section_blocks'))) {
                data_set(
                    $meta,
                    'form.section_blocks',
                    $this->defaultSectionBlocksForBlade((string) data_get($meta, 'blade', $orderType->order?->blade ?? ''))
                );
            }

            if (! data_get($meta, 'source')) {
                data_set($meta, 'source', 'ui_config_bootstrap');
            }

            $version->update([
                'meta' => $meta,
                'updated_by' => auth()->id(),
            ]);
        });

        app(TemplateRegistry::class)->invalidate((int) $orderType->id);
        app(OrderTemplateFormSchemaService::class)->invalidateCachedSchema((int) $orderType->id, (int) $version->id);
        app(OrderTemplateAuditLogger::class)->log((int) $version->id, 'metadata_bootstrapped', [
            'order_type_id' => (int) $orderType->id,
            'strict_sync' => $strictSync,
            'token_count' => $tokens->count(),
        ]);
    }
}
