<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateVersion;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use App\Support\Translations\ModuleTranslation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class OrderTemplateFormSchemaService
{
    private int $schemaCacheMinutes;

    /**
     * @var array<string,array>
     */
    private array $resolvedSchemaMemory = [];

    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
    ) {
        $this->schemaCacheMinutes = max(1, (int) config('orders.form.schema_cache_minutes', 15));
    }

    public function resolveForOrderType(?int $orderTypeId): array
    {
        if (! $orderTypeId || $orderTypeId <= 0) {
            return $this->metadataRequiredPayload();
        }

        $version = $this->templateRegistry->activeVersionForOrderType($orderTypeId);
        if (! $version || $version->mappings->isEmpty()) {
            return $this->metadataRequiredPayload();
        }

        $cacheKey = $this->schemaCacheKey((int) $orderTypeId, (int) $version->id);
        if (array_key_exists($cacheKey, $this->resolvedSchemaMemory)) {
            return $this->resolvedSchemaMemory[$cacheKey];
        }

        $resolver = fn () => $this->buildMetadataPayload($version);

        if (app()->runningUnitTests()) {
            return $this->resolvedSchemaMemory[$cacheKey] = $resolver();
        }

        return $this->resolvedSchemaMemory[$cacheKey] = Cache::remember(
            $cacheKey,
            now()->addMinutes($this->schemaCacheMinutes),
            $resolver
        );
    }

    private function buildMetadataPayload(OrderTemplateVersion $version): array
    {
        $version->loadMissing(['fields', 'mappings']);

        $fieldDefinitions = $version->fields
            ->keyBy(fn ($field) => $this->normalizeFieldKey((string) $field->field_key));

        $rowMappings = $version->mappings
            ->filter(fn ($mapping) => (string) $mapping->scope !== 'scalar')
            ->sortBy('sort_order')
            ->values();

        if ($rowMappings->isEmpty()) {
            return $this->metadataRequiredPayload();
        }

        $rowFieldKeys = [];
        $fieldCatalog = [];
        $dropdownFields = [];

        foreach ($rowMappings as $mapping) {
            $normalizedFieldKey = $this->normalizeFieldKey((string) $mapping->field_key);
            if ($normalizedFieldKey === '') {
                continue;
            }

            $token = '$' . $normalizedFieldKey;
            if (! in_array($token, $rowFieldKeys, true)) {
                $rowFieldKeys[] = $token;
            }

            $fieldDefinition = $fieldDefinitions->get($normalizedFieldKey);
            $uiConfig = $this->mergeUiConfigDefaults(
                is_array($fieldDefinition?->ui_config) ? $fieldDefinition->ui_config : [],
                $this->canonicalFieldUiDefaults($normalizedFieldKey),
            );

            $resolvedField = (string) ($uiConfig['field'] ?? $normalizedFieldKey);
            $resolvedInput = (string) (
                $uiConfig['input']
                ?? (
                    ! empty($uiConfig['model']) || ! empty($uiConfig['searchField']) || ! empty($uiConfig['selectedName'])
                        ? 'select'
                        : $this->mapFieldTypeToInput((string) ($fieldDefinition?->field_type ?? ''))
                )
            );

            // Keep structure picker on legacy tree-list behavior regardless of stored metadata input.
            if ($resolvedField === 'structure_id' || $normalizedFieldKey === 'structure') {
                $resolvedInput = 'radio-list';
            }

            $resolvedTitle = (string) (
                $uiConfig['title']
                ?? $fieldDefinition?->label
                ?? Str::headline(str_replace('_', ' ', $normalizedFieldKey))
            );

            $resolvedDefinition = array_filter([
                'field' => $resolvedField,
                'title' => $this->resolveStoredTitle($resolvedTitle),
                'model' => $uiConfig['model'] ?? null,
                'selectedName' => $uiConfig['selectedName'] ?? null,
                'searchField' => $uiConfig['searchField'] ?? null,
                'input' => $resolvedInput,
                'required' => (bool) ($fieldDefinition?->is_required ?? false),
                'field_order' => (int) ($uiConfig['field_order'] ?? ($fieldDefinition?->sort_order ?? 0)),
                'group' => (string) ($uiConfig['group'] ?? 'main'),
                'group_title' => $uiConfig['group_title'] ?? null,
                'group_order' => (int) ($uiConfig['group_order'] ?? 0),
                'grid_cols' => $this->normalizeResponsiveNumberConfig(
                    $uiConfig['grid_cols'] ?? null,
                    ['default' => 1, 'sm' => 2, 'md' => 3]
                ),
                'col_span' => $this->normalizeResponsiveNumberConfig(
                    $uiConfig['col_span'] ?? null,
                    ['default' => 1]
                ),
                'rules' => $this->resolveValidationRules(
                    is_array($fieldDefinition?->validation_config) ? $fieldDefinition?->validation_config : null,
                    $resolvedInput,
                    (bool) ($fieldDefinition?->is_required ?? false)
                ),
            ], static fn ($value) => $value !== null && $value !== '');

            $fieldCatalog[$token] = $resolvedDefinition;

            if (in_array($resolvedInput, ['select', 'radio-list'], true)) {
                $dropdownFields[] = $resolvedField;
            }
        }

        return [
            'source' => 'metadata',
            'row_field_keys' => $rowFieldKeys,
            'row_groups' => $this->buildRowGroups($rowFieldKeys, $fieldCatalog),
            'section_blocks' => $this->resolveSectionBlocks($version),
            'field_catalog' => $fieldCatalog,
            'dropdown_fields' => array_values(array_unique($dropdownFields)),
            'template_version_id' => (int) $version->id,
        ];
    }

    private function schemaCacheKey(int $orderTypeId, int $versionId): string
    {
        $locale = app()->getLocale();

        return "orders:template_form_schema:{$locale}:{$orderTypeId}:{$versionId}";
    }

    private function metadataRequiredPayload(): array
    {
        return [
            'source' => 'metadata_required',
            'row_field_keys' => [],
            'row_groups' => [],
            'section_blocks' => [],
            'field_catalog' => [],
            'dropdown_fields' => [],
            'template_version_id' => null,
        ];
    }

    public function invalidateCachedSchema(int $orderTypeId, ?int $versionId = null): void
    {
        if ($versionId !== null && $versionId > 0) {
            Cache::forget($this->schemaCacheKey($orderTypeId, $versionId));
            unset($this->resolvedSchemaMemory[$this->schemaCacheKey($orderTypeId, $versionId)]);
            return;
        }

        $locale = app()->getLocale();
        $prefix = "orders:template_form_schema:{$locale}:{$orderTypeId}:";

        foreach (array_keys($this->resolvedSchemaMemory) as $key) {
            if (str_starts_with($key, $prefix)) {
                Cache::forget($key);
                unset($this->resolvedSchemaMemory[$key]);
            }
        }
    }

    private function mapFieldTypeToInput(string $fieldType): string
    {
        return match (strtolower(trim($fieldType))) {
            'select', 'dropdown', 'enum', 'relation' => 'select',
            'structure', 'tree' => 'radio-list',
            'date', 'datetime' => 'date-input',
            'integer', 'int', 'number', 'numeric' => 'numeric-input',
            default => 'text-input',
        };
    }

    private function canonicalFieldUiDefaults(string $normalizedFieldKey): array
    {
        return match ($normalizedFieldKey) {
            'fullname', 'personnel', 'personnel_id' => [
                'field' => 'personnel_id',
                'title' => 'orders::template_metadata_defaults.fields.select_personnel',
                'model' => '_personnels',
                'selectedName' => 'personnel',
                'searchField' => 'search.personnel',
                'input' => 'select',
                'group' => 'personnel',
                'group_title' => 'orders::template_metadata_defaults.groups.personnel',
                'group_order' => 10,
            ],
            'rank', 'rank_id' => [
                'field' => 'rank_id',
                'title' => 'orders::template_metadata_defaults.fields.select_rank',
                'model' => '_ranks',
                'selectedName' => 'rank',
                'searchField' => 'search.rank',
                'input' => 'select',
                'group' => 'assignment_details',
                'group_title' => 'orders::template_metadata_defaults.groups.assignment_details',
                'group_order' => 20,
            ],
            'structure_main', 'structure_main_id', 'main_structure', 'main_structure_id' => [
                'field' => 'structure_main_id',
                'title' => 'orders::template_metadata_defaults.fields.select_main_structure',
                'model' => '_main_structures',
                'selectedName' => 'mainStructure',
                'searchField' => 'search.mainStructure',
                'input' => 'select',
                'group' => 'assignment_details',
                'group_title' => 'orders::template_metadata_defaults.groups.assignment_details',
                'group_order' => 20,
            ],
            'structure', 'structure_id' => [
                'field' => 'structure_id',
                'title' => 'orders::template_metadata_defaults.fields.select_structure',
                'model' => '_structures',
                'selectedName' => 'structure',
                'searchField' => 'search.structure',
                'input' => 'radio-list',
                'group' => 'assignment_details',
                'group_title' => 'orders::template_metadata_defaults.groups.assignment_details',
                'group_order' => 20,
            ],
            'position', 'position_id' => [
                'field' => 'position_id',
                'title' => 'orders::template_metadata_defaults.fields.select_position',
                'model' => '_positions',
                'selectedName' => 'position',
                'searchField' => 'search.position',
                'input' => 'select',
                'group' => 'assignment_details',
                'group_title' => 'orders::template_metadata_defaults.groups.assignment_details',
                'group_order' => 20,
            ],
            'day', 'days' => [
                'field' => $normalizedFieldKey,
                'title' => 'orders::template_metadata_defaults.fields.day',
                'input' => 'numeric-input',
                'group' => 'timing',
                'group_title' => 'orders::template_metadata_defaults.groups.timing',
                'group_order' => 30,
            ],
            'month' => [
                'field' => 'month',
                'title' => 'orders::template_metadata_defaults.fields.month',
                'input' => 'text-input',
                'group' => 'timing',
                'group_title' => 'orders::template_metadata_defaults.groups.timing',
                'group_order' => 30,
            ],
            'year' => [
                'field' => 'year',
                'title' => 'orders::template_metadata_defaults.fields.year',
                'input' => 'numeric-input',
                'group' => 'timing',
                'group_title' => 'orders::template_metadata_defaults.groups.timing',
                'group_order' => 30,
            ],
            default => [],
        };
    }

    private function resolveValidationRules(?array $validationConfig, string $input, bool $isRequired): string
    {
        if (is_array($validationConfig)) {
            $configured = $validationConfig['rules'] ?? $validationConfig;

            if (is_string($configured) && trim($configured) !== '') {
                return $configured;
            }

            if (is_array($configured)) {
                $normalized = implode('|', array_filter(array_map('trim', $configured)));
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        $prefix = $isRequired ? 'required' : 'nullable';

        return match ($input) {
            'select', 'radio-list' => $prefix . '|int',
            'date-input' => $prefix . '|date',
            'numeric-input' => $prefix . '|numeric',
            default => $prefix . '|string',
        };
    }

    private function normalizeFieldKey(string $key): string
    {
        $trimmed = trim($key);
        if (str_starts_with($trimmed, '$')) {
            $trimmed = ltrim($trimmed, '$');
        }

        return trim($trimmed);
    }

    private function mergeUiConfigDefaults(array $current, array $defaults): array
    {
        if (empty($defaults)) {
            return $current;
        }

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

    private function buildRowGroups(array $rowFieldKeys, array $fieldCatalog): array
    {
        $groups = [];

        foreach ($rowFieldKeys as $token) {
            $definition = $fieldCatalog[$token] ?? [];
            $groupKey = (string) ($definition['group'] ?? 'main');
            $groupTitle = $definition['group_title'] ?? null;
            $groupOrder = (int) ($definition['group_order'] ?? 0);
            $gridCols = $definition['grid_cols'] ?? ['default' => 1, 'sm' => 2, 'md' => 3];

            if (! array_key_exists($groupKey, $groups)) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'title' => is_string($groupTitle) && trim($groupTitle) !== '' ? $this->resolveStoredTitle($groupTitle) : null,
                    'order' => $groupOrder,
                    'grid_cols' => $gridCols,
                    'fields' => [],
                ];
            }

            $groups[$groupKey]['fields'][] = $token;
        }

        foreach ($groups as &$group) {
            $group['fields'] = collect($group['fields'])
                ->sortBy(fn ($token) => (int) ($fieldCatalog[$token]['field_order'] ?? 0))
                ->values()
                ->all();
        }
        unset($group);

        return collect($groups)
            ->sortBy('order')
            ->values()
            ->all();
    }

    private function normalizeResponsiveNumberConfig(mixed $config, array $fallback): array
    {
        if (is_numeric($config)) {
            $value = max(1, (int) $config);
            return ['default' => $value];
        }

        if (! is_array($config)) {
            return $fallback;
        }

        $allowedBreakpoints = ['default', 'sm', 'md', 'lg', 'xl', '2xl'];
        $normalized = [];

        foreach ($allowedBreakpoints as $breakpoint) {
            if (! array_key_exists($breakpoint, $config)) {
                continue;
            }

            $value = $config[$breakpoint];
            if (! is_numeric($value)) {
                continue;
            }

            $normalized[$breakpoint] = max(1, (int) $value);
        }

        if (empty($normalized)) {
            return $fallback;
        }

        if (! array_key_exists('default', $normalized) && array_key_exists('default', $fallback)) {
            $normalized['default'] = (int) $fallback['default'];
        }

        return $normalized;
    }

    private function resolveSectionBlocks(OrderTemplateVersion $version): array
    {
        $blade = (string) data_get($version->meta, 'blade', '');
        $defaults = $this->defaultSectionBlocksForBlade($blade);
        $configured = data_get($version->meta, 'form.section_blocks', []);

        if (! is_array($configured) || empty($configured)) {
            return $defaults;
        }

        $byKey = collect($defaults)->keyBy('key');

        foreach ($configured as $block) {
            if (! is_array($block)) {
                continue;
            }

            $key = trim((string) ($block['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $existing = $byKey->get($key, [
                'key' => $key,
                'title' => null,
                'enabled' => true,
                'order' => 100,
            ]);

            $merged = array_merge($existing, [
                'title' => isset($block['title']) && is_string($block['title']) && trim($block['title']) !== ''
                    ? $this->resolveStoredTitle((string) $block['title'])
                    : ($existing['title'] ?? null),
                'enabled' => array_key_exists('enabled', $block) ? (bool) $block['enabled'] : (bool) ($existing['enabled'] ?? true),
                'order' => array_key_exists('order', $block) ? (int) $block['order'] : (int) ($existing['order'] ?? 100),
            ]);

            $byKey->put($key, $merged);
        }

        return $byKey
            ->values()
            ->sortBy('order')
            ->values()
            ->all();
    }

    private function defaultSectionBlocksForBlade(string $blade): array
    {
        if (in_array($blade, ['vacation', 'business-trips'], true)) {
            return [
                ['key' => 'row_fields', 'title' => null, 'enabled' => true, 'order' => 10],
                ['key' => 'personnel_search', 'title' => null, 'enabled' => true, 'order' => 20],
                ['key' => 'personnel_selected', 'title' => null, 'enabled' => true, 'order' => 30],
            ];
        }

        return [
            ['key' => 'row_fields', 'title' => null, 'enabled' => true, 'order' => 10],
        ];
    }

    private function resolveStoredTitle(string $value): string
    {
        $resolved = ModuleTranslation::resolveStoredText($value);
        if ($resolved !== $value) {
            return $resolved;
        }

        $literalKey = $this->literalTitleTranslationKey($value);

        return $literalKey ? __($literalKey) : $resolved;
    }

    private function literalTitleTranslationKey(string $value): ?string
    {
        return match (Str::of($value)->squish()->lower()->toString()) {
            'fullname' => 'orders::template_metadata_defaults.fields.select_personnel',
            'rank' => 'orders::template_metadata_defaults.fields.select_rank',
            'select personnel' => 'orders::template_metadata_defaults.fields.select_personnel',
            'select rank' => 'orders::template_metadata_defaults.fields.select_rank',
            'day' => 'orders::template_metadata_defaults.fields.day',
            'month' => 'orders::template_metadata_defaults.fields.month',
            'year' => 'orders::template_metadata_defaults.fields.year',
            'name' => 'orders::template_metadata_defaults.fields.name',
            'surname' => 'orders::template_metadata_defaults.fields.surname',
            'structure main', 'main structure' => 'orders::template_metadata_defaults.fields.select_main_structure',
            'structure' => 'orders::template_metadata_defaults.fields.select_structure',
            'position' => 'orders::template_metadata_defaults.fields.select_position',
            'select main structure' => 'orders::template_metadata_defaults.fields.select_main_structure',
            'select structure' => 'orders::template_metadata_defaults.fields.select_structure',
            'select position' => 'orders::template_metadata_defaults.fields.select_position',
            'start date' => 'orders::template_metadata_defaults.fields.start_date',
            'end date' => 'orders::template_metadata_defaults.fields.end_date',
            'location' => 'orders::template_metadata_defaults.fields.location',
            'trip start day' => 'orders::template_metadata_defaults.fields.trip_start_day',
            'trip start month' => 'orders::template_metadata_defaults.fields.trip_start_month',
            'trip start year' => 'orders::template_metadata_defaults.fields.trip_start_year',
            'select transportation' => 'orders::template_metadata_defaults.fields.select_transportation',
            'meeting hour' => 'orders::template_metadata_defaults.fields.meeting_hour',
            'return month' => 'orders::template_metadata_defaults.fields.return_month',
            'return day' => 'orders::template_metadata_defaults.fields.return_day',
            'weapon' => 'orders::template_metadata_defaults.fields.weapon',
            'car' => 'orders::template_metadata_defaults.fields.car',
            'personnel' => 'orders::template_metadata_defaults.groups.personnel',
            'timing' => 'orders::template_metadata_defaults.groups.timing',
            'order details' => 'orders::template_metadata_defaults.groups.order_details',
            'assignment details' => 'orders::template_metadata_defaults.groups.assignment_details',
            'candidate' => 'orders::template_metadata_defaults.groups.candidate',
            default => null,
        };
    }
}
