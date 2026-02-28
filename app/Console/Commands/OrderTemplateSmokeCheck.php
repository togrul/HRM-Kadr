<?php

namespace App\Console\Commands;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Services\Orders\OrderTemplateRenderer;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class OrderTemplateSmokeCheck extends Command
{
    protected $signature = 'orders:templates:smoke
        {--order-type= : Limit to a specific order_type_id}
        {--json : Print report as JSON}';

    protected $description = 'Run DOCX smoke checks for active order template versions (XML integrity + placeholder replacement)';

    public function handle(
        TemplatePlaceholderCoverageService $coverageService,
        OrderTemplateRenderer $renderer
    ): int {
        $orderTypeId = (int) ($this->option('order-type') ?? 0);

        $versionsQuery = OrderTemplateVersion::query()
            ->with([
                'templateSet.orderType:id,name',
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('is_active', true)
            ->orderBy('order_template_set_id')
            ->orderByDesc('version_no');

        if ($orderTypeId > 0) {
            $versionsQuery->whereHas('templateSet', fn ($query) => $query->where('order_type_id', $orderTypeId));
        }

        $versions = $versionsQuery->get();
        if ($versions->isEmpty()) {
            $this->warn('No active template version found for smoke check.');
            return self::SUCCESS;
        }

        $rows = $versions->map(function (OrderTemplateVersion $version) use ($coverageService, $renderer): array {
            $orderType = $version->templateSet?->orderType;
            $templatePath = trim((string) $version->template_path);
            $absoluteTemplatePath = $templatePath !== '' ? Storage::disk('public')->path($templatePath) : null;

            $coverage = $coverageService->analyzeForVersion($version, $version->mappings
                ->map(fn (OrderTemplateMapping $mapping) => [
                    'placeholder' => (string) $mapping->placeholder,
                    'field_key' => (string) $mapping->field_key,
                    'scope' => (string) $mapping->scope,
                ])
                ->all());

            $xmlIntegrityOk = false;
            $xmlError = null;
            if ($templatePath !== '' && $absoluteTemplatePath && is_file($absoluteTemplatePath)) {
                try {
                    new TemplateProcessor($absoluteTemplatePath);
                    $xmlIntegrityOk = true;
                } catch (Throwable $e) {
                    $xmlError = $e->getMessage();
                }
            } else {
                $xmlError = 'template_file_not_found';
            }

            $previewRendered = false;
            $previewError = null;
            $unresolvedAfterRender = [];
            $outputPath = null;

            if ($xmlIntegrityOk && ! empty($coverage['inspectable']) && empty($coverage['missing_placeholders'])) {
                try {
                    $scalarValues = $this->buildScalarValues($version->mappings, $version->fields);
                    $rowValues = [$this->buildRowValues($version->mappings, $version->fields)];

                    $outputPath = $renderer->render(
                        (string) $templatePath,
                        $scalarValues,
                        $rowValues,
                        sprintf(
                            'smoke-order-type-%d-v%d',
                            (int) ($orderType?->id ?? 0),
                            (int) $version->version_no
                        ),
                        [
                            'source' => 'orders_template_smoke_check',
                            'order_type_id' => $orderType?->id,
                            'template_version_id' => $version->id,
                        ]
                    );

                    $renderedDoc = new TemplateProcessor($outputPath);
                    $remainingVariables = collect($renderedDoc->getVariables())
                        ->map(fn ($token) => $this->normalizePlaceholder((string) $token))
                        ->filter(fn ($token) => $this->isCoverageRelevantPlaceholder($token))
                        ->unique()
                        ->values()
                        ->all();

                    $unresolvedAfterRender = collect($coverage['template_placeholders'] ?? [])
                        ->intersect($remainingVariables)
                        ->values()
                        ->all();

                    $previewRendered = true;
                } catch (Throwable $e) {
                    $previewError = $e->getMessage();
                } finally {
                    if (is_string($outputPath) && is_file($outputPath)) {
                        @unlink($outputPath);
                    }
                }
            }

            $status = 'ok';
            if (! $xmlIntegrityOk) {
                $status = 'failed_xml';
            } elseif (! empty($coverage['missing_placeholders'])) {
                $status = 'failed_missing_mappings';
            } elseif (! $previewRendered) {
                $status = 'failed_render';
            } elseif (! empty($unresolvedAfterRender)) {
                $status = 'failed_unresolved_placeholders';
            }

            return [
                'order_type_id' => (int) ($orderType?->id ?? 0),
                'order_type_name' => (string) ($orderType?->name ?? ''),
                'template_version_id' => (int) $version->id,
                'version_no' => (int) $version->version_no,
                'status' => $status,
                'template_path' => $templatePath,
                'xml_integrity_ok' => $xmlIntegrityOk,
                'missing_placeholders' => $coverage['missing_placeholders'] ?? [],
                'orphan_mappings' => $coverage['orphan_mappings'] ?? [],
                'unresolved_after_render' => $unresolvedAfterRender,
                'xml_error' => $xmlError,
                'render_error' => $previewError,
            ];
        })->values();

        $summary = [
            'checked_versions' => $rows->count(),
            'ok' => $rows->where('status', 'ok')->count(),
            'failed_xml' => $rows->where('status', 'failed_xml')->count(),
            'failed_missing_mappings' => $rows->where('status', 'failed_missing_mappings')->count(),
            'failed_render' => $rows->where('status', 'failed_render')->count(),
            'failed_unresolved_placeholders' => $rows->where('status', 'failed_unresolved_placeholders')->count(),
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'rows' => $rows->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ($summary['ok'] === $summary['checked_versions']) ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['order_type_id', 'order_type_name', 'version_no', 'status', 'missing_placeholders', 'unresolved_after_render'],
            $rows->map(function (array $row): array {
                return [
                    $row['order_type_id'],
                    $row['order_type_name'],
                    $row['version_no'],
                    $row['status'],
                    implode(', ', $row['missing_placeholders']),
                    implode(', ', $row['unresolved_after_render']),
                ];
            })->all()
        );

        $this->newLine();
        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, (string) $value])->values()->all()
        );

        return ($summary['ok'] === $summary['checked_versions']) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param Collection<int,OrderTemplateMapping> $mappings
     * @param Collection<int,OrderTemplateField> $fields
     * @return array<string,string>
     */
    private function buildScalarValues(Collection $mappings, Collection $fields): array
    {
        $fieldsByKey = $fields->keyBy(fn (OrderTemplateField $field) => ltrim((string) $field->field_key, '$'));

        $values = $mappings
            ->filter(fn (OrderTemplateMapping $mapping) => (string) $mapping->scope === 'scalar')
            ->mapWithKeys(function (OrderTemplateMapping $mapping) use ($fieldsByKey): array {
                $fieldKey = ltrim((string) $mapping->field_key, '$');
                $field = $fieldsByKey->get($fieldKey);
                $placeholder = $this->normalizePlaceholder((string) $mapping->placeholder);

                return [$placeholder => $this->sampleValueForField($field)];
            })
            ->all();

        foreach ($this->implicitScalarValues() as $placeholder => $value) {
            if (! array_key_exists($placeholder, $values)) {
                $values[$placeholder] = $value;
            }
        }

        return $values;
    }

    /**
     * @param Collection<int,OrderTemplateMapping> $mappings
     * @param Collection<int,OrderTemplateField> $fields
     * @return array<string,string>
     */
    private function buildRowValues(Collection $mappings, Collection $fields): array
    {
        $fieldsByKey = $fields->keyBy(fn (OrderTemplateField $field) => ltrim((string) $field->field_key, '$'));

        return $mappings
            ->filter(fn (OrderTemplateMapping $mapping) => (string) $mapping->scope !== 'scalar')
            ->mapWithKeys(function (OrderTemplateMapping $mapping) use ($fieldsByKey): array {
                $fieldKey = ltrim((string) $mapping->field_key, '$');
                $field = $fieldsByKey->get($fieldKey);
                $placeholder = $this->normalizePlaceholder((string) $mapping->placeholder);

                return [$placeholder => $this->sampleValueForField($field)];
            })
            ->all();
    }

    private function sampleValueForField(?OrderTemplateField $field): string
    {
        if (! $field) {
            return 'sample';
        }

        $label = trim((string) $field->label);
        if ($label !== '') {
            return $label;
        }

        $fieldKey = ltrim((string) $field->field_key, '$');

        return $fieldKey !== '' ? $fieldKey : 'sample';
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
     * @return array<string,string>
     */
    private function implicitScalarValues(): array
    {
        return [
            '$day' => '1',
            '$month' => 'yanvar',
            '$year' => '2026',
            '$rank_director' => 'general-mayor',
            '$name_director' => 'Director Name',
        ];
    }
}
