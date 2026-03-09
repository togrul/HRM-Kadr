<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderTemplateMetadataSyncService
{
    /**
     * @return array{
     *   token_count:int,
     *   created_fields:int,
     *   updated_fields:int,
     *   deleted_fields:int,
     *   created_mappings:int,
     *   updated_mappings:int,
     *   deleted_mappings:int
     * }
     */
    public function sync(
        OrderTemplateVersion $version,
        int $orderTypeId,
        ?string $blade = null,
        bool $strictSync = false,
        ?int $actorId = null
    ): array {
        $tokens = collect($this->coverageService->extractRelevantPlaceholdersForVersion($version))
            ->filter(fn (string $placeholder): bool => $this->isRowPlaceholderCandidate($placeholder))
            ->values();

        if ($tokens->isEmpty()) {
            $tokens = collect($this->fallbackTokensForBlade((string) $blade));
        }

        $normalizedTokenKeys = $tokens
            ->map(fn (string $token) => ltrim($token, '$'))
            ->filter(fn (string $token) => trim($token) !== '')
            ->values();

        $result = [
            'token_count' => $tokens->count(),
            'created_fields' => 0,
            'updated_fields' => 0,
            'deleted_fields' => 0,
            'created_mappings' => 0,
            'updated_mappings' => 0,
            'deleted_mappings' => 0,
        ];

        DB::transaction(function () use (
            $version,
            $orderTypeId,
            $strictSync,
            $actorId,
            $blade,
            $tokens,
            $normalizedTokenKeys,
            &$result
        ): void {
            $definitions = $this->defaultFieldDefinitions();

            $existingFields = OrderTemplateField::query()
                ->where('order_template_version_id', (int) $version->id)
                ->get()
                ->keyBy(fn (OrderTemplateField $field) => (string) $field->field_key);

            $existingMappings = OrderTemplateMapping::query()
                ->where('order_template_version_id', (int) $version->id)
                ->where('scope', 'row')
                ->get()
                ->keyBy(fn (OrderTemplateMapping $mapping) => (string) $mapping->placeholder.'|'.(string) $mapping->scope);

            foreach ($tokens->values() as $index => $token) {
                $fieldKey = ltrim((string) $token, '$');
                if ($fieldKey === '') {
                    continue;
                }

                $definition = is_array($definitions[$token] ?? null) ? $definitions[$token] : [];
                $resolvedField = (string) ($definition['field'] ?? $fieldKey);
                $resolvedInput = $this->resolveInput($definition);
                if ($resolvedField === 'structure_id' || $fieldKey === 'structure') {
                    $resolvedInput = 'radio-list';
                }

                $resolvedLabel = (string) ($definition['title'] ?? Str::headline(str_replace('_', ' ', $fieldKey)));
                $fieldType = $this->mapInputToFieldType($resolvedInput);
                $sortOrder = (($index + 1) * 10);

                $uiConfig = array_filter([
                    'field' => $resolvedField,
                    'input' => $resolvedInput,
                    'model' => $definition['model'] ?? null,
                    'selectedName' => $definition['selectedName'] ?? null,
                    'searchField' => $definition['searchField'] ?? null,
                    'group' => 'main',
                    'group_order' => 0,
                    'field_order' => $sortOrder,
                    'grid_cols' => ['default' => 1, 'sm' => 2, 'md' => 3],
                    'col_span' => ['default' => 1],
                ], static fn ($value) => $value !== null && $value !== '');

                $existingField = $existingFields->get($fieldKey);
                if (! $existingField) {
                    $created = OrderTemplateField::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'field_key' => $fieldKey,
                        'label' => $resolvedLabel,
                        'field_type' => $fieldType,
                        'is_required' => false,
                        'sort_order' => $sortOrder,
                        'default_value' => null,
                        'data_source' => null,
                        'ui_config' => $uiConfig,
                        'transform_config' => null,
                        'validation_config' => null,
                    ]);
                    $existingFields->put($fieldKey, $created);
                    $result['created_fields']++;
                } else {
                    $currentUiConfig = is_array($existingField->ui_config) ? $existingField->ui_config : [];
                    $updates = [];

                    if ($strictSync) {
                        if ((string) $existingField->label !== $resolvedLabel) {
                            $updates['label'] = $resolvedLabel;
                        }
                        if ((string) $existingField->field_type !== $fieldType) {
                            $updates['field_type'] = $fieldType;
                        }
                        if ((int) $existingField->sort_order !== $sortOrder) {
                            $updates['sort_order'] = $sortOrder;
                        }
                        if ($currentUiConfig !== $uiConfig) {
                            $updates['ui_config'] = $uiConfig;
                        }
                    } else {
                        $mergedUiConfig = $this->mergeUiConfigDefaults($currentUiConfig, $uiConfig);

                        if (trim((string) $existingField->label) === '') {
                            $updates['label'] = $resolvedLabel;
                        }
                        if (trim((string) $existingField->field_type) === '') {
                            $updates['field_type'] = $fieldType;
                        }
                        if ((int) $existingField->sort_order <= 0) {
                            $updates['sort_order'] = $sortOrder;
                        }
                        if ($mergedUiConfig !== $currentUiConfig) {
                            $updates['ui_config'] = $mergedUiConfig;
                        }
                    }

                    if (! empty($updates)) {
                        $existingField->update($updates);
                        $result['updated_fields']++;
                    }
                }

                $mappingKey = (string) $token.'|row';
                $existingMapping = $existingMappings->get($mappingKey);
                if (! $existingMapping) {
                    $created = OrderTemplateMapping::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'placeholder' => (string) $token,
                        'scope' => 'row',
                        'field_key' => $fieldKey,
                        'sort_order' => $sortOrder,
                        'mapping_config' => null,
                    ]);
                    $existingMappings->put($mappingKey, $created);
                    $result['created_mappings']++;
                } elseif ($strictSync) {
                    $updates = [];
                    if ((string) $existingMapping->field_key !== $fieldKey) {
                        $updates['field_key'] = $fieldKey;
                    }
                    if ((int) $existingMapping->sort_order !== $sortOrder) {
                        $updates['sort_order'] = $sortOrder;
                    }

                    if (! empty($updates)) {
                        $existingMapping->update($updates);
                        $result['updated_mappings']++;
                    }
                }
            }

            if ($strictSync) {
                $tokenSet = array_fill_keys($tokens->all(), true);
                $normalizedKeySet = array_fill_keys($normalizedTokenKeys->all(), true);

                $result['deleted_mappings'] = OrderTemplateMapping::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->where('scope', 'row')
                    ->get()
                    ->filter(function (OrderTemplateMapping $mapping) use ($tokenSet): bool {
                        return ! isset($tokenSet[(string) $mapping->placeholder]);
                    })
                    ->each(fn (OrderTemplateMapping $mapping) => $mapping->delete())
                    ->count();

                $result['deleted_fields'] = OrderTemplateField::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->get()
                    ->filter(function (OrderTemplateField $field) use ($normalizedKeySet): bool {
                        return ! isset($normalizedKeySet[(string) $field->field_key]);
                    })
                    ->each(fn (OrderTemplateField $field) => $field->delete())
                    ->count();
            }

            $meta = is_array($version->meta) ? $version->meta : [];
            if (! is_array(data_get($meta, 'form.section_blocks'))) {
                data_set(
                    $meta,
                    'form.section_blocks',
                    $this->defaultSectionBlocksForBlade((string) data_get($meta, 'blade', $blade ?? ''))
                );
            }

            if (! data_get($meta, 'source')) {
                data_set($meta, 'source', 'ui_config_bootstrap');
            }

            $metaChanged = $meta !== (is_array($version->meta) ? $version->meta : []);
            if ($metaChanged) {
                $version->update([
                    'meta' => $meta,
                    'updated_by' => $actorId,
                ]);
            }
        });

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId, (int) $version->id);

        return $result;
    }

    public function __construct(
        private readonly TemplatePlaceholderCoverageService $coverageService,
        private readonly TemplateRegistry $templateRegistry,
        private readonly OrderTemplateFormSchemaService $schemaService,
    ) {
    }

    private function defaultFieldDefinitions(): array
    {
        return [
            '$fullname' => [
                'field' => 'personnel_id',
                'title' => __('orders::template_metadata_defaults.fields.select_personnel'),
                'model' => '_personnels',
                'selectedName' => 'personnel',
                'searchField' => 'search.personnel',
            ],
            '$rank' => [
                'field' => 'rank_id',
                'title' => __('orders::template_metadata_defaults.fields.select_rank'),
                'model' => '_ranks',
                'selectedName' => 'rank',
            ],
            '$day' => ['field' => 'day', 'title' => __('orders::template_metadata_defaults.fields.day')],
            '$month' => ['field' => 'month', 'title' => __('orders::template_metadata_defaults.fields.month')],
            '$year' => ['field' => 'year', 'title' => __('orders::template_metadata_defaults.fields.year')],
            '$name' => ['field' => 'name', 'title' => __('orders::template_metadata_defaults.fields.name')],
            '$surname' => ['field' => 'surname', 'title' => __('orders::template_metadata_defaults.fields.surname')],
            '$structure_main' => [
                'field' => 'structure_main_id',
                'title' => __('orders::template_metadata_defaults.fields.select_main_structure'),
                'model' => '_main_structures',
                'selectedName' => 'mainStructure',
            ],
            '$structure' => [
                'field' => 'structure_id',
                'title' => __('orders::template_metadata_defaults.fields.select_structure'),
                'model' => '_structures',
                'selectedName' => 'structure',
                'searchField' => 'search.structure',
                'input' => 'radio-list',
            ],
            '$position' => [
                'field' => 'position_id',
                'title' => __('orders::template_metadata_defaults.fields.select_position'),
                'model' => '_positions',
                'selectedName' => 'position',
                'searchField' => 'search.position',
            ],
            '$start_date' => ['field' => 'start_date', 'title' => __('orders::template_metadata_defaults.fields.start_date')],
            '$end_date' => ['field' => 'end_date', 'title' => __('orders::template_metadata_defaults.fields.end_date')],
            '$days' => ['field' => 'days', 'title' => __('orders::template_metadata_defaults.fields.day')],
            '$location' => ['field' => 'location', 'title' => __('orders::template_metadata_defaults.fields.location')],
            '$trip_start_day' => ['field' => 'trip_start_day', 'title' => __('orders::template_metadata_defaults.fields.trip_start_day')],
            '$trip_start_month' => ['field' => 'trip_start_month', 'title' => __('orders::template_metadata_defaults.fields.trip_start_month')],
            '$trip_start_year' => ['field' => 'trip_start_year', 'title' => __('orders::template_metadata_defaults.fields.trip_start_year')],
            '$transportation' => [
                'field' => 'transportation',
                'title' => __('orders::template_metadata_defaults.fields.select_transportation'),
                'model' => '_transportations',
                'selectedName' => 'transportation',
            ],
            '$meeting_hour' => ['field' => 'meeting_hour', 'title' => __('orders::template_metadata_defaults.fields.meeting_hour')],
            '$return_month' => ['field' => 'return_month', 'title' => __('orders::template_metadata_defaults.fields.return_month')],
            '$return_day' => ['field' => 'return_day', 'title' => __('orders::template_metadata_defaults.fields.return_day')],
            '$weapon' => ['field' => 'weapon', 'title' => __('orders::template_metadata_defaults.fields.weapon')],
            '$car' => ['field' => 'car', 'title' => __('orders::template_metadata_defaults.fields.car')],
        ];
    }

    /**
     * @return array<int,string>
     */
    private function fallbackTokensForBlade(string $blade): array
    {
        return match ($blade) {
            Order::BLADE_VACATION => ['$start_date', '$end_date', '$days', '$fullname', '$location'],
            Order::BLADE_BUSINESS_TRIP => ['$start_date', '$end_date', '$location', '$fullname', '$transportation'],
            default => ['$fullname', '$rank', '$day', '$month', '$year', '$structure_main', '$structure', '$position'],
        };
    }

    private function isRowPlaceholderCandidate(string $placeholder): bool
    {
        $key = ltrim(trim($placeholder), '$');

        if ($key === '') {
            return false;
        }

        return ! in_array($key, ['rank_director', 'name_director'], true);
    }

    private function resolveInput(array $definition): string
    {
        $configured = trim((string) ($definition['input'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        if (! empty($definition['model']) || ! empty($definition['searchField']) || ! empty($definition['selectedName'])) {
            return 'select';
        }

        return 'text-input';
    }

    private function mapInputToFieldType(string $input): string
    {
        return match (trim($input)) {
            'select', 'radio-list' => 'relation',
            'date-input' => 'date',
            'numeric-input' => 'integer',
            default => 'text',
        };
    }

    private function mergeUiConfigDefaults(array $current, array $defaults): array
    {
        $merged = $current;

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $merged) || $merged[$key] === null || $merged[$key] === '') {
                $merged[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeUiConfigDefaults($merged[$key], $value);
            }
        }

        return $merged;
    }

    private function defaultSectionBlocksForBlade(string $blade): array
    {
        if (in_array($blade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP], true)) {
            return [
                ['key' => 'row_fields', 'title' => '', 'enabled' => true, 'order' => 10],
                ['key' => 'personnel_search', 'title' => '', 'enabled' => true, 'order' => 20],
                ['key' => 'personnel_selected', 'title' => '', 'enabled' => true, 'order' => 30],
            ];
        }

        return [
            ['key' => 'row_fields', 'title' => '', 'enabled' => true, 'order' => 10],
        ];
    }
}
