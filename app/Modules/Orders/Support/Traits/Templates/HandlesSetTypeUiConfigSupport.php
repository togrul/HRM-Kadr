<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Models\Order;
use App\Models\OrderTemplateVersion;
use Illuminate\Support\Str;

trait HandlesSetTypeUiConfigSupport
{
    public function resolveUiAuditActionLabel(string $action): string
    {
        return match (trim($action)) {
            'metadata_bootstrapped' => __('Metadata generated'),
            'metadata_field_added' => __('Metadata field added'),
            'metadata_field_removed' => __('Metadata field removed'),
            'ui_config_saved' => __('UI config saved'),
            'version_drafted' => __('Draft version created'),
            'version_published' => __('Version published'),
            'version_rolled_back' => __('Version rolled back'),
            default => Str::headline(str_replace('_', ' ', trim($action))),
        };
    }

    private function normalizeUiConfigDraft(
        mixed $uiConfig,
        int $sortOrder,
        string $fieldKey,
        string $fieldType,
        bool $isRequired,
        mixed $validationConfig
    ): array {
        $config = is_array($uiConfig) ? $uiConfig : [];
        $resolvedFieldAlias = trim((string) ($config['field'] ?? $fieldKey));
        $resolvedInput = trim((string) ($config['input'] ?? $this->mapFieldTypeToInput($fieldType)));
        if (! array_key_exists($resolvedInput, $this->inputTypeOptions())) {
            $resolvedInput = $this->mapFieldTypeToInput($fieldType);
        }
        $resolvedRules = '';
        if (is_array($validationConfig)) {
            $rules = $validationConfig['rules'] ?? null;
            if (is_string($rules)) {
                $resolvedRules = trim($rules);
            } elseif (is_array($rules)) {
                $resolvedRules = implode('|', array_filter(array_map('trim', $rules)));
            }
        }

        return [
            'field' => ltrim($resolvedFieldAlias, '$'),
            'input' => $resolvedInput,
            'model' => (string) ($config['model'] ?? ''),
            'selectedName' => (string) ($config['selectedName'] ?? ''),
            'searchField' => (string) ($config['searchField'] ?? ''),
            'required' => $isRequired,
            'rules' => $resolvedRules,
            'group' => (string) ($config['group'] ?? 'main'),
            'group_title' => (string) ($config['group_title'] ?? ''),
            'group_order' => (int) ($config['group_order'] ?? 0),
            'field_order' => (int) ($config['field_order'] ?? $sortOrder),
            'grid_cols_default' => (int) data_get($config, 'grid_cols.default', 1),
            'grid_cols_sm' => (int) data_get($config, 'grid_cols.sm', 2),
            'grid_cols_md' => (int) data_get($config, 'grid_cols.md', 3),
            'col_span_default' => (int) data_get($config, 'col_span.default', 1),
            'col_span_sm' => (int) data_get($config, 'col_span.sm', 1),
            'col_span_md' => (int) data_get($config, 'col_span.md', 1),
        ];
    }

    private function normalizeSectionBlocksDraft(OrderTemplateVersion $version, ?string $fallbackBlade = null): array
    {
        $blade = (string) data_get($version->meta, 'blade', $fallbackBlade ?? '');

        $defaults = collect($this->defaultSectionBlocksForBlade($blade))
            ->keyBy('key');

        $configured = collect(data_get($version->meta, 'form.section_blocks', []))
            ->filter(fn ($block) => is_array($block) && trim((string) ($block['key'] ?? '')) !== '')
            ->map(function ($block) {
                $key = $this->normalizeSectionBlockKey((string) ($block['key'] ?? ''));
                if ($key === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => is_string($block['title'] ?? null) ? (string) $block['title'] : '',
                    'enabled' => array_key_exists('enabled', $block) ? (bool) $block['enabled'] : true,
                    'order' => array_key_exists('order', $block) ? max(0, (int) $block['order']) : 100,
                ];
            })
            ->filter()
            ->keyBy('key');

        $resolved = collect();

        foreach ($defaults as $key => $default) {
            $resolved->put($key, array_merge($default, $configured->get($key, [])));
        }

        foreach ($configured as $key => $block) {
            if (! $resolved->has($key)) {
                $resolved->put($key, $block);
            }
        }

        return $resolved
            ->values()
            ->map(fn ($block) => [
                'key' => (string) ($block['key'] ?? ''),
                'title' => is_string($block['title'] ?? null) ? (string) $block['title'] : '',
                'enabled' => (bool) ($block['enabled'] ?? true),
                'order' => max(0, (int) ($block['order'] ?? 100)),
            ])
            ->sortBy('order')
            ->values()
            ->all();
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

    private function normalizeSectionBlockPayload(mixed $draft): ?array
    {
        if (! is_array($draft)) {
            return null;
        }

        $key = $this->normalizeSectionBlockKey((string) ($draft['key'] ?? ''));
        if ($key === '') {
            return null;
        }

        $title = trim((string) ($draft['title'] ?? ''));

        return [
            'key' => $key,
            'title' => $title !== '' ? $title : null,
            'enabled' => (bool) ($draft['enabled'] ?? true),
            'order' => max(0, (int) ($draft['order'] ?? 100)),
        ];
    }

    private function normalizeSectionBlockKey(string $key): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($key));

        return is_string($normalized) ? $normalized : '';
    }

    private function resolveLegacyInput(array $legacyDefinition): string
    {
        $configured = trim((string) ($legacyDefinition['input'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        if (! empty($legacyDefinition['model']) || ! empty($legacyDefinition['searchField']) || ! empty($legacyDefinition['selectedName'])) {
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

    private function mapFieldTypeToInput(string $fieldType): string
    {
        return match (trim($fieldType)) {
            'relation' => 'select',
            'date' => 'date-input',
            'integer' => 'numeric-input',
            default => 'text-input',
        };
    }

    private function fallbackTokensForBlade(string $blade): array
    {
        return match ($blade) {
            Order::BLADE_VACATION => ['$start_date', '$end_date', '$days', '$fullname', '$location'],
            Order::BLADE_BUSINESS_TRIP => ['$start_date', '$end_date', '$location', '$fullname', '$transportation'],
            default => ['$fullname', '$rank', '$day', '$month', '$year', '$structure_main', '$structure', '$position'],
        };
    }

    private function extractDynamicTokens(mixed $fields): array
    {
        if (is_array($fields)) {
            return collect($fields)
                ->map(fn ($field) => is_string($field) ? trim($field) : '')
                ->filter()
                ->values()
                ->all();
        }

        if (is_string($fields)) {
            $trimmed = trim($fields);
            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $this->extractDynamicTokens($decoded);
            }

            return collect(explode(',', $trimmed))
                ->map(fn ($field) => trim((string) $field))
                ->filter()
                ->values()
                ->all();
        }

        return [];
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

    private function inputTypeOptions(): array
    {
        return [
            'text-input' => __('Text input'),
            'numeric-input' => __('Numeric input'),
            'date-input' => __('Date input'),
            'select' => __('Select'),
            'radio-list' => __('Tree list'),
        ];
    }

    private function resetNewFieldDraft(): void
    {
        $this->newFieldKey = '';
        $this->newFieldLabel = '';
        $this->newFieldAlias = '';
        $this->newFieldInput = 'text-input';
        $this->newFieldModel = '';
        $this->newFieldSelectedName = '';
        $this->newFieldSearchField = '';
        $this->newFieldRequired = false;
        $this->newFieldRules = '';
    }

    private function normalizeRulesDraftValue(string $rules, string $input, bool $isRequired): string
    {
        $normalized = trim($rules);
        if ($normalized === '') {
            $base = $isRequired ? 'required' : 'nullable';

            return match ($input) {
                'select', 'radio-list' => $base.'|int',
                'date-input' => $base.'|date',
                'numeric-input' => $base.'|numeric',
                default => $base.'|string',
            };
        }

        $parts = collect(explode('|', $normalized))
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->reject(fn ($part) => in_array($part, ['required', 'nullable'], true))
            ->values()
            ->all();

        array_unshift($parts, $isRequired ? 'required' : 'nullable');

        return implode('|', $parts);
    }
}
