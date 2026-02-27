<?php

namespace App\Services\Orders;

use App\Services\WordSuffixService;
use Carbon\Carbon;
use Throwable;

class OrderFieldTransformPipeline
{
    public function __construct(private readonly WordSuffixService $suffixService)
    {
    }

    public function apply(mixed $value, mixed $transformConfig = null): string
    {
        if ($transformConfig === null) {
            return $this->stringify($value);
        }

        $transforms = $this->normalizeTransforms($transformConfig);
        $current = $value;

        foreach ($transforms as $transform) {
            $current = $this->applySingle($current, $transform['type'], $transform['options']);
        }

        return $this->stringify($current);
    }

    private function applySingle(mixed $value, string $type, array $options): mixed
    {
        $stringValue = $this->stringify($value);

        return match ($type) {
            'trim' => trim($stringValue),
            'upper' => mb_strtoupper($stringValue),
            'lower' => mb_strtolower($stringValue),
            'ucfirst' => mb_strtoupper(mb_substr($stringValue, 0, 1)) . mb_substr($stringValue, 1),
            'title' => mb_convert_case($stringValue, MB_CASE_TITLE, 'UTF-8'),
            'prepend' => (string) ($options['value'] ?? '') . $stringValue,
            'append' => $stringValue . (string) ($options['value'] ?? ''),
            'replace' => str_replace(
                (string) ($options['search'] ?? ''),
                (string) ($options['replace'] ?? ''),
                $stringValue
            ),
            'date.format' => $this->formatDate($stringValue, (string) ($options['format'] ?? 'Y-m-d')),
            'suffix.number' => $stringValue . $this->suffixService->getNumberSuffix((int) $stringValue),
            'suffix.surname' => $this->suffixService->getSurnameSuffix($stringValue),
            'suffix.structure' => $this->suffixService->getStructureSuffix(
                $stringValue,
                (bool) ($options['only_suffix'] ?? false),
                (bool) ($options['main_structure'] ?? false),
                (bool) ($options['use_determine'] ?? false),
            ),
            'suffix.month_day' => $this->suffixService->getMonthDaySuffix($stringValue),
            'suffix.time' => $this->suffixService->getTimeSuffix($stringValue),
            default => $value,
        };
    }

    private function normalizeTransforms(mixed $transformConfig): array
    {
        if (is_string($transformConfig)) {
            return [['type' => $transformConfig, 'options' => []]];
        }

        if (! is_array($transformConfig)) {
            return [];
        }

        $rawItems = $transformConfig['transforms'] ?? $transformConfig;

        if (! is_array($rawItems)) {
            return [];
        }

        $normalized = [];
        foreach ($rawItems as $item) {
            if (is_string($item)) {
                $normalized[] = ['type' => $item, 'options' => []];
                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            if ($type === '') {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'options' => is_array($item['options'] ?? null)
                    ? $item['options']
                    : collect($item)->except('type')->all(),
            ];
        }

        return $normalized;
    }

    private function formatDate(string $value, string $format): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (Throwable) {
            return $value;
        }
    }

    private function stringify(mixed $value): string
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
