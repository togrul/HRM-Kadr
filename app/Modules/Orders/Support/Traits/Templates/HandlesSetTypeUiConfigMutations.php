<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Application\UseCases\Templates\SetTypeUiConfigWriteUseCase;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait HandlesSetTypeUiConfigMutations
{
    public function addUiMetadataField(): void
    {
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

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

        app(SetTypeUiConfigWriteUseCase::class)->addMetadataField(
            (int) $this->uiConfigOrderTypeId,
            (int) $this->uiConfigVersionId,
            $fieldKey,
            $fieldLabel,
            $this->mapInputToFieldType($input),
            $isRequired,
            $nextSort,
            $uiConfig,
            $rules,
            auth()->id()
        );

        $this->resetNewFieldDraft();
        $this->openUiConfig((int) $this->uiConfigOrderTypeId);
        $this->dispatch('typesUpdated', __('Metadata field added successfully.'));
    }

    public function removeUiMetadataField(int $fieldId): void
    {
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId || ! $this->uiConfigVersionId) {
            return;
        }

        $removed = app(SetTypeUiConfigWriteUseCase::class)->removeMetadataField(
            (int) $this->uiConfigOrderTypeId,
            (int) $this->uiConfigVersionId,
            $fieldId,
            auth()->id()
        );

        if (! $removed) {
            return;
        }

        $this->openUiConfig((int) $this->uiConfigOrderTypeId);
        $this->dispatch('typesUpdated', __('Metadata field removed successfully.'));
    }

    public function addMappingRow(): void
    {
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

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
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

        if (! array_key_exists($index, $this->mappingDraft)) {
            return;
        }

        unset($this->mappingDraft[$index]);
        $this->mappingDraft = array_values($this->mappingDraft);
    }

    public function saveUiConfig(): void
    {
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

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

        $templateVersion = OrderTemplateVersion::query()
            ->with([
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find((int) $this->uiConfigVersionId);
        if (! $templateVersion) {
            $this->dispatch('typesUpdated', __('Active metadata template version not found.'));
            return;
        }

        $stateBefore = $this->captureUiConfigState($templateVersion);

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

        $fieldUpdatePayloads = $this->buildUiFieldUpdatePayloads($fields);
        $sectionBlocks = collect($this->sectionBlocksDraft)
            ->map(fn ($draft) => $this->normalizeSectionBlockPayload($draft))
            ->filter()
            ->values()
            ->all();

        $templateVersion = app(SetTypeUiConfigWriteUseCase::class)->saveUiConfig(
            (int) $this->uiConfigOrderTypeId,
            (int) $this->uiConfigVersionId,
            $fields,
            $fieldUpdatePayloads,
            $sectionBlocks,
            $normalizedMappings
        );
        if (! $templateVersion) {
            $this->dispatch('typesUpdated', __('Active metadata template version not found.'));
            return;
        }
        $stateAfter = $this->captureUiConfigState($templateVersion);
        $diff = $this->buildUiConfigStateDiff($stateBefore, $stateAfter);
        $summary = $this->summarizeUiConfigDiff($diff);
        $highlights = $this->buildUiConfigDiffHighlights($diff);

        app(SetTypeUiConfigWriteUseCase::class)->logUiConfigSaved((int) $this->uiConfigVersionId, [
            'fields_count' => $fields->count(),
            'mappings_count' => $normalizedMappings->count(),
            'section_blocks_count' => count($this->sectionBlocksDraft),
            'summary' => $summary,
            'diff' => $diff,
            'diff_highlights' => $highlights,
        ], auth()->id());

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
    private function buildUiFieldUpdatePayloads(EloquentCollection $fields): array
    {
        $payloads = [];

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

            $payloads[(int) $field->id] = [
                'field_type' => $this->mapInputToFieldType($resolvedInput),
                'is_required' => $isRequired,
                'validation_config' => $nextValidationConfig,
                'ui_config' => $uiConfig,
            ];
        }

        return $payloads;
    }

    private function captureUiConfigState(OrderTemplateVersion $templateVersion): array
    {
        $fieldState = $templateVersion->fields
            ->mapWithKeys(function (OrderTemplateField $field): array {
                $uiConfig = is_array($field->ui_config) ? $field->ui_config : [];
                $validation = is_array($field->validation_config) ? $field->validation_config : [];

                return [
                    ltrim((string) $field->field_key, '$') => [
                        'label' => (string) $field->label,
                        'field_type' => (string) $field->field_type,
                        'required' => (bool) $field->is_required,
                        'field' => ltrim((string) ($uiConfig['field'] ?? $field->field_key), '$'),
                        'input' => (string) ($uiConfig['input'] ?? $this->mapFieldTypeToInput((string) $field->field_type)),
                        'model' => (string) ($uiConfig['model'] ?? ''),
                        'selectedName' => (string) ($uiConfig['selectedName'] ?? ''),
                        'searchField' => (string) ($uiConfig['searchField'] ?? ''),
                        'rules' => is_string($validation['rules'] ?? null)
                            ? trim((string) $validation['rules'])
                            : implode('|', collect($validation['rules'] ?? [])->filter()->map(fn ($rule) => trim((string) $rule))->all()),
                        'group' => (string) ($uiConfig['group'] ?? ''),
                        'group_title' => (string) ($uiConfig['group_title'] ?? ''),
                        'group_order' => (int) ($uiConfig['group_order'] ?? 0),
                        'field_order' => (int) ($uiConfig['field_order'] ?? $field->sort_order),
                        'grid_cols' => [
                            'default' => (int) data_get($uiConfig, 'grid_cols.default', 1),
                            'sm' => (int) data_get($uiConfig, 'grid_cols.sm', 2),
                            'md' => (int) data_get($uiConfig, 'grid_cols.md', 3),
                        ],
                        'col_span' => [
                            'default' => (int) data_get($uiConfig, 'col_span.default', 1),
                            'sm' => (int) data_get($uiConfig, 'col_span.sm', 1),
                            'md' => (int) data_get($uiConfig, 'col_span.md', 1),
                        ],
                    ],
                ];
            })
            ->all();

        $mappingState = $templateVersion->mappings
            ->mapWithKeys(function (OrderTemplateMapping $mapping): array {
                $key = sprintf('%s|%s', (string) $mapping->placeholder, (string) $mapping->scope);

                return [
                    $key => [
                        'placeholder' => (string) $mapping->placeholder,
                        'field_key' => ltrim((string) $mapping->field_key, '$'),
                        'scope' => (string) $mapping->scope,
                        'sort_order' => (int) $mapping->sort_order,
                        'mapping_config' => is_array($mapping->mapping_config) ? $mapping->mapping_config : [],
                    ],
                ];
            })
            ->all();

        $sectionState = collect(data_get($templateVersion->meta, 'form.section_blocks', []))
            ->filter(fn ($block) => is_array($block) && trim((string) ($block['key'] ?? '')) !== '')
            ->mapWithKeys(fn ($block) => [
                (string) $block['key'] => [
                    'title' => (string) ($block['title'] ?? ''),
                    'enabled' => (bool) ($block['enabled'] ?? true),
                    'order' => (int) ($block['order'] ?? 0),
                ],
            ])
            ->all();

        ksort($fieldState);
        ksort($mappingState);
        ksort($sectionState);

        return [
            'fields' => $fieldState,
            'mappings' => $mappingState,
            'sections' => $sectionState,
        ];
    }

    private function buildUiConfigStateDiff(array $before, array $after): array
    {
        return [
            'fields' => $this->buildBucketDiff(
                is_array($before['fields'] ?? null) ? $before['fields'] : [],
                is_array($after['fields'] ?? null) ? $after['fields'] : []
            ),
            'mappings' => $this->buildBucketDiff(
                is_array($before['mappings'] ?? null) ? $before['mappings'] : [],
                is_array($after['mappings'] ?? null) ? $after['mappings'] : []
            ),
            'sections' => $this->buildBucketDiff(
                is_array($before['sections'] ?? null) ? $before['sections'] : [],
                is_array($after['sections'] ?? null) ? $after['sections'] : []
            ),
        ];
    }

    private function buildBucketDiff(array $before, array $after): array
    {
        $addedKeys = array_values(array_diff(array_keys($after), array_keys($before)));
        $removedKeys = array_values(array_diff(array_keys($before), array_keys($after)));
        $candidateKeys = array_values(array_intersect(array_keys($before), array_keys($after)));

        $updated = [];
        foreach ($candidateKeys as $key) {
            $changes = $this->buildItemChanges(
                is_array($before[$key] ?? null) ? $before[$key] : [],
                is_array($after[$key] ?? null) ? $after[$key] : []
            );

            if (! empty($changes)) {
                $updated[] = [
                    'key' => (string) $key,
                    'changes' => $changes,
                ];
            }
        }

        return [
            'added' => collect($addedKeys)->map(fn ($key) => ['key' => (string) $key, 'value' => $after[$key] ?? null])->values()->all(),
            'removed' => collect($removedKeys)->map(fn ($key) => ['key' => (string) $key, 'value' => $before[$key] ?? null])->values()->all(),
            'updated' => $updated,
        ];
    }

    private function buildItemChanges(array $before, array $after): array
    {
        $beforeFlat = $this->flattenDiffValue($before);
        $afterFlat = $this->flattenDiffValue($after);

        $keys = array_values(array_unique(array_merge(array_keys($beforeFlat), array_keys($afterFlat))));
        sort($keys);

        $changes = [];
        foreach ($keys as $key) {
            $old = $beforeFlat[$key] ?? null;
            $new = $afterFlat[$key] ?? null;
            if ($old === $new) {
                continue;
            }

            $changes[$key] = [
                'from' => $old,
                'to' => $new,
            ];
        }

        return $changes;
    }

    private function flattenDiffValue(array $value, string $prefix = ''): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($item)) {
                $result = [...$result, ...$this->flattenDiffValue($item, $path)];
                continue;
            }

            $result[$path] = is_bool($item) ? (int) $item : $item;
        }

        return $result;
    }

    private function summarizeUiConfigDiff(array $diff): array
    {
        return [
            'fields' => [
                'added' => count(data_get($diff, 'fields.added', [])),
                'removed' => count(data_get($diff, 'fields.removed', [])),
                'updated' => count(data_get($diff, 'fields.updated', [])),
            ],
            'mappings' => [
                'added' => count(data_get($diff, 'mappings.added', [])),
                'removed' => count(data_get($diff, 'mappings.removed', [])),
                'updated' => count(data_get($diff, 'mappings.updated', [])),
            ],
            'sections' => [
                'added' => count(data_get($diff, 'sections.added', [])),
                'removed' => count(data_get($diff, 'sections.removed', [])),
                'updated' => count(data_get($diff, 'sections.updated', [])),
            ],
        ];
    }

    private function buildUiConfigDiffHighlights(array $diff): array
    {
        $highlights = collect();

        foreach (['fields', 'mappings', 'sections'] as $bucket) {
            $added = collect(data_get($diff, "{$bucket}.added", []))
                ->pluck('key')
                ->filter()
                ->take(2)
                ->map(fn ($key) => sprintf('%s + %s', Str::title($bucket), $key));

            $removed = collect(data_get($diff, "{$bucket}.removed", []))
                ->pluck('key')
                ->filter()
                ->take(2)
                ->map(fn ($key) => sprintf('%s - %s', Str::title($bucket), $key));

            $updated = collect(data_get($diff, "{$bucket}.updated", []))
                ->take(2)
                ->map(function ($item) use ($bucket) {
                    $key = (string) ($item['key'] ?? '');
                    $changedPaths = collect(is_array($item['changes'] ?? null) ? array_keys($item['changes']) : [])
                        ->take(2)
                        ->implode(', ');

                    return $changedPaths !== ''
                        ? sprintf('%s ~ %s (%s)', Str::title($bucket), $key, $changedPaths)
                        : sprintf('%s ~ %s', Str::title($bucket), $key);
                });

            $highlights = $highlights
                ->merge($added)
                ->merge($removed)
                ->merge($updated);
        }

        return $highlights
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }
}
