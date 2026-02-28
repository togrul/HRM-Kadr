<?php

namespace App\Services\Orders;

use App\Models\OrderLog;
use App\Models\OrderTemplateVersion;
use Carbon\Carbon;

class OrderMetadataRenderPayloadBuilder
{
    public function __construct(private readonly OrderFieldTransformPipeline $transformPipeline)
    {
    }

    public function build(OrderLog $orderLog, OrderTemplateVersion $templateVersion): array
    {
        $orderLog->loadMissing(['order', 'attributes.component']);
        $templateVersion->loadMissing(['fields', 'mappings']);

        $fieldDefinitions = $templateVersion->fields
            ->keyBy(fn ($field) => $this->normalizeFieldKey((string) $field->field_key));

        $scalarSource = $this->buildScalarSource($orderLog);
        $rowSource = $this->buildRowSource($orderLog);

        $scalarMappings = $templateVersion->mappings
            ->filter(fn ($mapping) => (string) $mapping->scope === 'scalar')
            ->sortBy('sort_order')
            ->values();

        $rowMappings = $templateVersion->mappings
            ->filter(fn ($mapping) => (string) $mapping->scope !== 'scalar')
            ->sortBy('sort_order')
            ->values();

        $scalarValues = $this->buildCompatibilityScalarValues($scalarSource);
        foreach ($scalarMappings as $mapping) {
            $fieldKey = $this->normalizeFieldKey((string) $mapping->field_key);
            $placeholder = $this->normalizePlaceholder((string) $mapping->placeholder);
            $field = $fieldDefinitions->get($fieldKey);

            $rawValue = $scalarSource[$fieldKey] ?? $field?->default_value;
            $scalarValues[$placeholder] = $this->transformPipeline->apply(
                $rawValue,
                $this->resolveTransformConfig($mapping->mapping_config, $field?->transform_config)
            );
        }

        $rows = [];
        if ($rowMappings->isNotEmpty()) {
            foreach (array_keys($rowSource) as $rowNumber) {
                $rowData = $rowSource[$rowNumber] ?? [];
                $resolved = [];
                $resolvedFieldValues = [];

                foreach ($rowMappings as $mapping) {
                    $fieldKey = $this->normalizeFieldKey((string) $mapping->field_key);
                    $placeholder = $this->normalizePlaceholder((string) $mapping->placeholder);
                    $field = $fieldDefinitions->get($fieldKey);

                    $rawValue = $rowData[$fieldKey]
                        ?? $scalarSource[$fieldKey]
                        ?? $field?->default_value;

                    $resolvedValue = $this->transformPipeline->apply(
                        $rawValue,
                        $this->resolveTransformConfig($mapping->mapping_config, $field?->transform_config)
                    );
                    $resolved[$placeholder] = $resolvedValue;
                    if ($fieldKey !== '') {
                        $resolvedFieldValues[$fieldKey] = $resolvedValue;
                    }
                    $resolvedFieldValues[$placeholder] = $resolvedValue;
                }

                if (
                    ! array_key_exists('content_text', $resolved)
                    && is_string($rowData['component_content'] ?? null)
                    && trim((string) $rowData['component_content']) !== ''
                ) {
                    $resolved['content_text'] = $this->renderComponentContent(
                        template: (string) $rowData['component_content'],
                        rowData: $rowData,
                        scalarValues: $scalarValues,
                        resolvedFieldValues: $resolvedFieldValues
                    );
                }

                if (! empty($resolved)) {
                    $rows[] = $resolved;
                }
            }
        }

        return [
            'scalar_values' => $scalarValues,
            'rows' => $rows,
            'mode' => 'metadata',
            'template_version_id' => (int) $templateVersion->id,
        ];
    }

    private function resolveTransformConfig(mixed $mappingConfig, mixed $fieldConfig): mixed
    {
        if (is_array($mappingConfig) && isset($mappingConfig['transform'])) {
            return $mappingConfig['transform'];
        }

        return $fieldConfig;
    }

    private function buildScalarSource(OrderLog $orderLog): array
    {
        $givenDate = Carbon::parse($orderLog->given_date);
        $source = [
            'day' => $givenDate->format('d'),
            'month' => $givenDate->locale('AZ')->monthName,
            'year' => $givenDate->format('Y'),
            'rank_director' => (string) $orderLog->given_by_rank,
            'name_director' => (string) $orderLog->given_by,
            'order_no' => (string) $orderLog->order_no,
            'given_date' => $givenDate->format('Y-m-d'),
            'blade' => (string) ($orderLog->order?->blade ?? ''),
        ];

        if (is_array($orderLog->description)) {
            foreach ($orderLog->description as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }
                $source[$this->normalizeFieldKey($key)] = $value;
            }
        }

        return $source;
    }

    private function buildRowSource(OrderLog $orderLog): array
    {
        $rows = [];

        foreach ($orderLog->attributes as $attributeRow) {
            $rowNumber = (int) $attributeRow->row_number;
            $rows[$rowNumber] ??= [];

            $flattened = $this->flattenAttributes((array) $attributeRow->attributes);
            $rows[$rowNumber] = array_merge($rows[$rowNumber], $flattened);

            if ($attributeRow->component) {
                $rows[$rowNumber]['component_title'] = (string) $attributeRow->component->title;
                $rows[$rowNumber]['component_content'] = (string) $attributeRow->component->content;
            }
        }

        ksort($rows);

        return $rows;
    }

    private function flattenAttributes(array $attributes): array
    {
        $flattened = [];

        foreach ($attributes as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalizedKey = $this->normalizeFieldKey($key);

            if (is_array($value) && array_key_exists('value', $value)) {
                $flattened[$normalizedKey] = $value['value'];
                continue;
            }

            $flattened[$normalizedKey] = $value;
        }

        return $flattened;
    }

    private function normalizeFieldKey(string $key): string
    {
        $trimmed = trim($key);
        if (str_starts_with($trimmed, '$')) {
            $trimmed = ltrim($trimmed, '$');
        }

        return trim($trimmed);
    }

    private function normalizePlaceholder(string $placeholder): string
    {
        $value = trim($placeholder);
        $value = preg_replace('/^\$\{(.+)\}$/', '$1', $value) ?: $value;
        $value = ltrim(trim($value), '$');
        $value = preg_replace('/#\d+$/', '', $value) ?: $value;
        return trim($value);
    }

    private function buildCompatibilityScalarValues(array $scalarSource): array
    {
        $defaults = [];
        foreach (['day', 'month', 'year', 'rank_director', 'name_director'] as $key) {
            if (array_key_exists($key, $scalarSource)) {
                $defaults[$key] = $scalarSource[$key];
            }
        }

        return $defaults;
    }

    private function renderComponentContent(
        string $template,
        array $rowData,
        array $scalarValues,
        array $resolvedFieldValues
    ): string {
        $replacementSource = array_merge($scalarValues, $rowData, $resolvedFieldValues);
        $replacements = [];

        foreach ($replacementSource as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $normalizedKey = $this->normalizeFieldKey($key);
            if ($normalizedKey === '') {
                continue;
            }

            $stringValue = $this->stringifyReplacementValue($value);
            $replacements['$'.$normalizedKey] = $stringValue;
            $replacements['${'.$normalizedKey.'}'] = $stringValue;
        }

        if (empty($replacements)) {
            return $template;
        }

        uksort($replacements, fn (string $left, string $right) => strlen($right) <=> strlen($left));

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function stringifyReplacementValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
