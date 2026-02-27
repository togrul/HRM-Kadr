<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateVersion;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class TemplatePlaceholderCoverageService
{
    public function analyzeForVersion(?OrderTemplateVersion $version, array $mappings): array
    {
        $templatePath = trim((string) ($version?->template_path ?? ''));
        $result = [
            'inspectable' => false,
            'template_path' => $templatePath,
            'template_placeholders' => [],
            'mapped_placeholders' => [],
            'missing_placeholders' => [],
            'orphan_mappings' => [],
            'error' => null,
        ];

        if ($templatePath === '') {
            return $result;
        }

        $absolutePath = Storage::disk('public')->path($templatePath);
        if (! is_file($absolutePath)) {
            $result['error'] = 'template_file_not_found';

            return $result;
        }

        try {
            $templateProcessor = new TemplateProcessor($absolutePath);
            $variables = $templateProcessor->getVariables();
        } catch (Throwable) {
            $result['error'] = 'template_read_failed';

            return $result;
        }

        $templatePlaceholders = collect($variables)
            ->filter(fn ($token) => is_string($token) && trim((string) $token) !== '')
            ->map(fn ($token) => $this->normalizePlaceholder((string) $token))
            ->filter(fn ($placeholder) => $this->isCoverageRelevantPlaceholder($placeholder))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $mappedPlaceholders = collect($mappings)
            ->map(function ($mapping): array {
                if (is_array($mapping)) {
                    return [
                        'placeholder' => (string) ($mapping['placeholder'] ?? ''),
                        'scope' => is_string($mapping['scope'] ?? null) ? (string) $mapping['scope'] : null,
                    ];
                }

                return [
                    'placeholder' => is_string($mapping) ? $mapping : '',
                    'scope' => null,
                ];
            })
            // Coverage compares template DOCX placeholders; row-scoped mappings do not appear there.
            ->filter(function (array $mapping): bool {
                $scope = strtolower(trim((string) ($mapping['scope'] ?? '')));
                if ($scope === '') {
                    return true;
                }

                return $scope === 'scalar';
            })
            ->map(fn (array $mapping) => (string) ($mapping['placeholder'] ?? ''))
            ->filter(fn ($placeholder) => trim((string) $placeholder) !== '')
            ->map(fn ($placeholder) => $this->normalizePlaceholder((string) $placeholder))
            ->filter(fn ($placeholder) => $this->isCoverageRelevantPlaceholder($placeholder))
            ->merge($this->implicitScalarPlaceholders())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $missingPlaceholders = collect($templatePlaceholders)
            ->diff($mappedPlaceholders)
            ->values()
            ->all();

        $orphanMappings = collect($mappedPlaceholders)
            ->diff($templatePlaceholders)
            ->values()
            ->all();

        $result['inspectable'] = true;
        $result['template_placeholders'] = $templatePlaceholders;
        $result['mapped_placeholders'] = $mappedPlaceholders;
        $result['missing_placeholders'] = $missingPlaceholders;
        $result['orphan_mappings'] = $orphanMappings;

        return $result;
    }

    private function normalizePlaceholder(string $placeholder): string
    {
        $normalized = trim($placeholder);
        $normalized = preg_replace('/^\$\{(.+)\}$/', '$1', $normalized) ?: $normalized;
        $normalized = trim($normalized, '{} ');
        $normalized = ltrim($normalized, '$');
        $normalized = preg_replace('/#\d+$/', '', $normalized);
        if (! is_string($normalized)) {
            return '';
        }

        $normalized = trim($normalized);
        if ($normalized === '') {
            return '';
        }

        return '$'.$normalized;
    }

    private function isCoverageRelevantPlaceholder(string $placeholder): bool
    {
        $value = ltrim(trim($placeholder), '$');
        if ($value === '') {
            return false;
        }

        $value = ltrim($value, '/');
        if (in_array($value, ['content', 'newline'], true)) {
            return false;
        }

        if (str_starts_with($value, 'content_text')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int,string>
     */
    private function implicitScalarPlaceholders(): array
    {
        return [
            '$day',
            '$month',
            '$year',
            '$rank_director',
            '$name_director',
        ];
    }
}
