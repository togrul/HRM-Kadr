<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait HandlesSetTypeUiConfigMutations
{
    public function addUiMetadataField(
        TemplateRegistry $templateRegistry,
        OrderTemplateFormSchemaService $schemaService,
        OrderTemplateAuditLogger $auditLogger
    ): void {
        if (! $this->uiConfigOrderTypeId || ! $this->uiConfigVersionId) {
            $this->dispatch('typesUpdated', __('Please open UI config for an order type first.'));
            return;
        }

        $this->resetValidation([
            'newFieldKey',
            'newFieldLabel',
            'newFieldAlias',
            'newFieldInput',
            'newFieldModel',
            'newFieldSelectedName',
            'newFieldSearchField',
            'newFieldRules',
        ]);

        $validator = Validator::make([
            'newFieldKey' => $this->newFieldKey,
            'newFieldLabel' => $this->newFieldLabel,
            'newFieldAlias' => $this->newFieldAlias,
            'newFieldInput' => $this->newFieldInput,
            'newFieldModel' => $this->newFieldModel,
            'newFieldSelectedName' => $this->newFieldSelectedName,
            'newFieldSearchField' => $this->newFieldSearchField,
            'newFieldRules' => $this->newFieldRules,
        ], [
            'newFieldKey' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_]+$/'],
            'newFieldLabel' => 'required|string|max:120',
            'newFieldAlias' => 'nullable|string|max:120',
            'newFieldInput' => 'required|string|in:text-input,numeric-input,date-input,select,radio-list',
            'newFieldModel' => 'nullable|string|max:120',
            'newFieldSelectedName' => 'nullable|string|max:120',
            'newFieldSearchField' => 'nullable|string|max:120',
            'newFieldRules' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('addError', __('Please fix validation errors in Add metadata field.'));
            return;
        }

        $fieldKey = ltrim(trim($this->newFieldKey), '$');
        $fieldLabel = trim($this->newFieldLabel);
        $fieldAlias = ltrim(trim($this->newFieldAlias), '$');
        $input = trim($this->newFieldInput);
        $model = trim($this->newFieldModel);
        $selectedName = trim($this->newFieldSelectedName);
        $searchField = trim($this->newFieldSearchField);

        if ($fieldAlias === '') {
            $fieldAlias = $fieldKey;
        }

        if (in_array($input, ['select', 'radio-list'], true) && $model === '') {
            $this->addError('newFieldModel', __('Model is required for lookup input.'));
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('typesUpdated', __('Please fix validation errors in Add metadata field.'));
            return;
        }

        if (in_array($input, ['select', 'radio-list'], true) && $selectedName === '') {
            $selectedName = Str::camel($fieldAlias !== '' ? $fieldAlias : $fieldKey);
        }

        if ($input === 'select' && $searchField === '' && $selectedName !== '') {
            $searchField = 'search.'.$selectedName;
        }

        $exists = OrderTemplateField::query()
            ->where('order_template_version_id', (int) $this->uiConfigVersionId)
            ->where('field_key', $fieldKey)
            ->exists();

        if ($exists) {
            $this->addError('newFieldKey', __('This field key already exists in metadata.'));
            return;
        }

        $nextSort = ((int) (OrderTemplateField::query()
            ->where('order_template_version_id', (int) $this->uiConfigVersionId)
            ->max('sort_order') ?? 0)) + 10;

        $isRequired = (bool) $this->newFieldRequired;
        $rules = $this->normalizeRulesDraftValue($this->newFieldRules, $input, $isRequired);

        $uiConfig = [
            'field' => $fieldAlias,
            'input' => $input,
            'group' => 'main',
            'group_order' => 0,
            'field_order' => $nextSort,
            'grid_cols' => ['default' => 1, 'sm' => 2, 'md' => 3],
            'col_span' => ['default' => 1],
        ];

        if ($model !== '') {
            $uiConfig['model'] = $model;
        }
        if ($selectedName !== '') {
            $uiConfig['selectedName'] = $selectedName;
        }
        if ($searchField !== '') {
            $uiConfig['searchField'] = $searchField;
        }

        DB::transaction(function () use ($fieldKey, $fieldLabel, $input, $isRequired, $nextSort, $uiConfig, $rules): void {
            OrderTemplateField::query()->create([
                'order_template_version_id' => (int) $this->uiConfigVersionId,
                'field_key' => $fieldKey,
                'label' => $fieldLabel,
                'field_type' => $this->mapInputToFieldType($input),
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
                    'order_template_version_id' => (int) $this->uiConfigVersionId,
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

        $templateRegistry->invalidate((int) $this->uiConfigOrderTypeId);
        $schemaService->invalidateCachedSchema((int) $this->uiConfigOrderTypeId, (int) $this->uiConfigVersionId);
        $auditLogger->log((int) $this->uiConfigVersionId, 'metadata_field_added', [
            'field_key' => $fieldKey,
            'input' => $input,
            'required' => $isRequired,
        ]);

        $this->resetNewFieldDraft();
        $this->openUiConfig((int) $this->uiConfigOrderTypeId);
        $this->dispatch('typesUpdated', __('Metadata field added successfully.'));
    }

    public function removeUiMetadataField(
        int $fieldId,
        TemplateRegistry $templateRegistry,
        OrderTemplateFormSchemaService $schemaService,
        OrderTemplateAuditLogger $auditLogger
    ): void {
        if (! $this->uiConfigOrderTypeId || ! $this->uiConfigVersionId) {
            return;
        }

        $field = OrderTemplateField::query()
            ->where('order_template_version_id', (int) $this->uiConfigVersionId)
            ->find($fieldId);

        if (! $field) {
            return;
        }

        $fieldKey = (string) $field->field_key;
        DB::transaction(function () use ($field, $fieldKey): void {
            $field->delete();

            OrderTemplateMapping::query()
                ->where('order_template_version_id', (int) $this->uiConfigVersionId)
                ->where('field_key', $fieldKey)
                ->delete();
        });

        $templateRegistry->invalidate((int) $this->uiConfigOrderTypeId);
        $schemaService->invalidateCachedSchema((int) $this->uiConfigOrderTypeId, (int) $this->uiConfigVersionId);
        $auditLogger->log((int) $this->uiConfigVersionId, 'metadata_field_removed', [
            'field_key' => $fieldKey,
            'field_id' => $fieldId,
        ]);

        $this->openUiConfig((int) $this->uiConfigOrderTypeId);
        $this->dispatch('typesUpdated', __('Metadata field removed successfully.'));
    }

    public function addMappingRow(): void
    {
        $nextOrder = collect($this->mappingDraft)
            ->pluck('order')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->max() ?? 0;

        $this->mappingDraft[] = [
            'id' => null,
            'placeholder' => '',
            'field_key' => '',
            'scope' => 'row',
            'order' => $nextOrder + 10,
            'mapping_config_json' => '',
        ];
    }

    public function removeMappingRow(int $index): void
    {
        if (! array_key_exists($index, $this->mappingDraft)) {
            return;
        }

        unset($this->mappingDraft[$index]);
        $this->mappingDraft = array_values($this->mappingDraft);
    }

    public function saveUiConfig(
        TemplateRegistry $templateRegistry,
        OrderTemplateFormSchemaService $schemaService,
        OrderTemplateAuditLogger $auditLogger
    ): void {
        if (! $this->uiConfigOrderTypeId || ! $this->uiConfigVersionId) {
            return;
        }

        $this->validate([
            'uiConfigDraft.*.field' => 'nullable|string|max:120',
            'uiConfigDraft.*.input' => 'nullable|string|in:text-input,numeric-input,date-input,select,radio-list',
            'uiConfigDraft.*.model' => 'nullable|string|max:120',
            'uiConfigDraft.*.selectedName' => 'nullable|string|max:120',
            'uiConfigDraft.*.searchField' => 'nullable|string|max:120',
            'uiConfigDraft.*.required' => 'boolean',
            'uiConfigDraft.*.rules' => 'nullable|string|max:500',
            'uiConfigDraft.*.group' => 'nullable|string|max:80',
            'uiConfigDraft.*.group_title' => 'nullable|string|max:120',
            'uiConfigDraft.*.group_order' => 'nullable|integer|min:0|max:10000',
            'uiConfigDraft.*.field_order' => 'nullable|integer|min:0|max:10000',
            'uiConfigDraft.*.grid_cols_default' => 'nullable|integer|min:1|max:12',
            'uiConfigDraft.*.grid_cols_sm' => 'nullable|integer|min:1|max:12',
            'uiConfigDraft.*.grid_cols_md' => 'nullable|integer|min:1|max:12',
            'uiConfigDraft.*.col_span_default' => 'nullable|integer|min:1|max:12',
            'uiConfigDraft.*.col_span_sm' => 'nullable|integer|min:1|max:12',
            'uiConfigDraft.*.col_span_md' => 'nullable|integer|min:1|max:12',
            'sectionBlocksDraft.*.key' => 'required|string|max:80',
            'sectionBlocksDraft.*.title' => 'nullable|string|max:120',
            'sectionBlocksDraft.*.enabled' => 'boolean',
            'sectionBlocksDraft.*.order' => 'nullable|integer|min:0|max:10000',
            'mappingDraft.*.placeholder' => 'nullable|string|max:120',
            'mappingDraft.*.field_key' => 'nullable|string|max:120',
            'mappingDraft.*.scope' => 'nullable|string|in:row,scalar',
            'mappingDraft.*.order' => 'nullable|integer|min:0|max:10000',
            'mappingDraft.*.mapping_config_json' => 'nullable|string|max:10000',
        ]);

        /** @var EloquentCollection<int, OrderTemplateField> $fields */
        $fields = OrderTemplateField::query()
            ->where('order_template_version_id', (int) $this->uiConfigVersionId)
            ->get();

        $this->resetValidation(['uiConfigDraft.*.model', 'mappingDraft.*.placeholder', 'mappingDraft.*.mapping_config_json']);

        foreach ($fields as $field) {
            $draft = $this->uiConfigDraft[(int) $field->id] ?? null;
            if (! is_array($draft)) {
                continue;
            }

            $resolvedInput = trim((string) ($draft['input'] ?? ''));
            if (! array_key_exists($resolvedInput, $this->inputTypeOptions())) {
                $resolvedInput = $this->mapFieldTypeToInput((string) $field->field_type);
            }

            $model = trim((string) ($draft['model'] ?? ''));
            if (in_array($resolvedInput, ['select', 'radio-list'], true) && $model === '') {
                $this->addError("uiConfigDraft.{$field->id}.model", __('Model is required for lookup input.'));
            }
        }

        $knownFieldKeys = $fields
            ->map(fn (OrderTemplateField $field) => ltrim((string) $field->field_key, '$'))
            ->filter()
            ->values()
            ->all();

        $normalizedMappings = $this->buildNormalizedMappingsDraft($knownFieldKeys);

        if ($normalizedMappings->isEmpty()) {
            $this->addError('mappingDraft', __('At least one mapping is required.'));
        }

        $templateVersion = OrderTemplateVersion::query()->find((int) $this->uiConfigVersionId);
        if (! $templateVersion) {
            $this->dispatch('typesUpdated', __('Active metadata template version not found.'));
            return;
        }

        $coverage = app(TemplatePlaceholderCoverageService::class)
            ->analyzeForVersion($templateVersion, $normalizedMappings->all());
        $this->uiPlaceholderCoverage = $coverage;

        if (! empty($coverage['inspectable']) && ! empty($coverage['missing_placeholders'])) {
            $this->addError(
                'mappingDraft',
                __('Missing mappings for template placeholders: :placeholders', [
                    'placeholders' => implode(', ', $coverage['missing_placeholders']),
                ])
            );
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('typesUpdated', __('Please fix validation errors before saving UI config.'));
            return;
        }

        DB::transaction(function () use ($fields, $templateVersion, $normalizedMappings): void {
            $this->persistUiFieldDrafts($fields);

            $meta = is_array($templateVersion->meta) ? $templateVersion->meta : [];
            data_set(
                $meta,
                'form.section_blocks',
                collect($this->sectionBlocksDraft)
                    ->map(fn ($draft) => $this->normalizeSectionBlockPayload($draft))
                    ->filter()
                    ->sortBy('order')
                    ->values()
                    ->all()
            );
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

        $templateRegistry->invalidate((int) $this->uiConfigOrderTypeId);
        $schemaService->invalidateCachedSchema((int) $this->uiConfigOrderTypeId, (int) $this->uiConfigVersionId);
        $auditLogger->log((int) $this->uiConfigVersionId, 'ui_config_saved', [
            'fields_count' => $fields->count(),
            'mappings_count' => $normalizedMappings->count(),
            'section_blocks_count' => count($this->sectionBlocksDraft),
        ]);

        $this->openUiConfig((int) $this->uiConfigOrderTypeId, (int) $this->uiConfigVersionId);
        $this->dispatch('typesUpdated', __('UI config was updated successfully.'));
    }

    private function buildNormalizedMappingsDraft(array $knownFieldKeys = []): Collection
    {
        $normalizedMappings = collect();
        $seen = [];
        $knownFieldMap = array_fill_keys(
            collect($knownFieldKeys)
                ->map(fn ($key) => ltrim((string) $key, '$'))
                ->filter()
                ->values()
                ->all(),
            true
        );

        foreach ($this->mappingDraft as $index => $row) {
            $placeholderRaw = trim((string) ($row['placeholder'] ?? ''));
            $fieldKeyRaw = trim((string) ($row['field_key'] ?? ''));

            if ($placeholderRaw === '' && $fieldKeyRaw === '') {
                continue;
            }

            if ($placeholderRaw === '') {
                $this->addError("mappingDraft.{$index}.placeholder", __('Placeholder is required.'));
                continue;
            }

            if ($fieldKeyRaw === '') {
                $this->addError("mappingDraft.{$index}.field_key", __('Field key is required.'));
                continue;
            }

            $placeholder = '$'.ltrim($placeholderRaw, '$');
            $fieldKey = ltrim($fieldKeyRaw, '$');
            $scope = trim((string) ($row['scope'] ?? 'row'));
            $scope = in_array($scope, ['row', 'scalar'], true) ? $scope : 'row';
            $order = is_numeric($row['order'] ?? null) ? max(0, (int) $row['order']) : (($index + 1) * 10);
            $mappingConfig = $this->normalizeMappingConfigFromDraft($row['mapping_config_json'] ?? null, $index);

            if (! empty($knownFieldMap) && ! isset($knownFieldMap[$fieldKey])) {
                $this->addError("mappingDraft.{$index}.field_key", __('Field key must exist in metadata fields.'));
                continue;
            }

            $dupKey = $placeholder.'|'.$scope;
            if (isset($seen[$dupKey])) {
                $this->addError("mappingDraft.{$index}.placeholder", __('Duplicate placeholder/scope is not allowed.'));
                continue;
            }
            $seen[$dupKey] = true;

            $normalizedMappings->push([
                'placeholder' => $placeholder,
                'field_key' => $fieldKey,
                'scope' => $scope,
                'sort_order' => $order,
                'mapping_config' => $mappingConfig,
            ]);
        }

        return $normalizedMappings;
    }

    private function normalizeMappingConfigFromDraft(mixed $raw, int $index): ?array
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            $this->addError("mappingDraft.{$index}.mapping_config_json", __('Mapping config must be valid JSON object.'));
            return null;
        }

        return $decoded;
    }

    /**
     * @param EloquentCollection<int, OrderTemplateField> $fields
     */
    private function persistUiFieldDrafts(EloquentCollection $fields): void
    {
        foreach ($fields as $field) {
            $draft = $this->uiConfigDraft[(int) $field->id] ?? null;
            if (! is_array($draft)) {
                continue;
            }

            $uiConfig = is_array($field->ui_config) ? $field->ui_config : [];
            $resolvedFieldAlias = trim((string) ($draft['field'] ?? ''));
            $resolvedFieldAlias = $resolvedFieldAlias !== '' ? ltrim($resolvedFieldAlias, '$') : (string) $field->field_key;

            $resolvedInput = trim((string) ($draft['input'] ?? ''));
            if (! array_key_exists($resolvedInput, $this->inputTypeOptions())) {
                $resolvedInput = $this->mapFieldTypeToInput((string) $field->field_type);
            }

            if ($resolvedFieldAlias === 'structure_id' || (string) $field->field_key === 'structure') {
                $resolvedInput = 'radio-list';
            }

            $isRequired = (bool) ($draft['required'] ?? false);
            $rulesDraft = $this->normalizeRulesDraftValue((string) ($draft['rules'] ?? ''), $resolvedInput, $isRequired);
            $currentValidationConfig = is_array($field->validation_config) ? $field->validation_config : [];
            $nextValidationConfig = $currentValidationConfig;
            if ($rulesDraft !== '') {
                $nextValidationConfig['rules'] = $rulesDraft;
            } else {
                unset($nextValidationConfig['rules']);
            }
            if (empty($nextValidationConfig)) {
                $nextValidationConfig = null;
            }

            $uiConfig['field'] = $resolvedFieldAlias;
            $uiConfig['input'] = $resolvedInput;

            $model = trim((string) ($draft['model'] ?? ''));
            $selectedName = trim((string) ($draft['selectedName'] ?? ''));
            $searchField = trim((string) ($draft['searchField'] ?? ''));

            if (in_array($resolvedInput, ['select', 'radio-list'], true) && $selectedName === '') {
                $selectedName = Str::camel($resolvedFieldAlias !== '' ? $resolvedFieldAlias : (string) $field->field_key);
            }

            if ($resolvedInput === 'select' && $searchField === '' && $selectedName !== '') {
                $searchField = 'search.'.$selectedName;
            }

            if ($model !== '') {
                $uiConfig['model'] = $model;
            } else {
                unset($uiConfig['model']);
            }

            if ($selectedName !== '') {
                $uiConfig['selectedName'] = $selectedName;
            } else {
                unset($uiConfig['selectedName']);
            }

            if ($searchField !== '') {
                $uiConfig['searchField'] = $searchField;
            } else {
                unset($uiConfig['searchField']);
            }

            $group = trim((string) ($draft['group'] ?? ''));
            if ($group !== '') {
                $uiConfig['group'] = $group;
            } else {
                unset($uiConfig['group']);
            }

            $groupTitle = trim((string) ($draft['group_title'] ?? ''));
            if ($groupTitle !== '') {
                $uiConfig['group_title'] = $groupTitle;
            } else {
                unset($uiConfig['group_title']);
            }

            $uiConfig['group_order'] = (int) ($draft['group_order'] ?? 0);
            $uiConfig['field_order'] = (int) ($draft['field_order'] ?? $field->sort_order);
            $uiConfig['grid_cols'] = [
                'default' => max(1, (int) ($draft['grid_cols_default'] ?? 1)),
                'sm' => max(1, (int) ($draft['grid_cols_sm'] ?? 2)),
                'md' => max(1, (int) ($draft['grid_cols_md'] ?? 3)),
            ];
            $uiConfig['col_span'] = [
                'default' => max(1, (int) ($draft['col_span_default'] ?? 1)),
                'sm' => max(1, (int) ($draft['col_span_sm'] ?? 1)),
                'md' => max(1, (int) ($draft['col_span_md'] ?? 1)),
            ];

            $field->update([
                'field_type' => $this->mapInputToFieldType($resolvedInput),
                'is_required' => $isRequired,
                'validation_config' => $nextValidationConfig,
                'ui_config' => $uiConfig,
            ]);
        }
    }
}
