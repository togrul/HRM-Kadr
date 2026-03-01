<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\OrderTemplateReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\OrderTemplateMetadataSyncService;
use App\Services\Orders\OrderTemplateRenderer;
use App\Services\Orders\OrderTemplateVersionLifecycleService;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TemplateOnboardingWizardUseCase
{
    public function __construct(
        private readonly OrderTemplateReadRepository $readRepository,
        private readonly OrderTemplateVersionLifecycleService $lifecycleService,
        private readonly OrderTemplateMetadataSyncService $metadataSyncService,
        private readonly OrderTemplateRegistry $templateRegistry,
        private readonly OrderTemplateFormSchemaService $schemaService,
        private readonly OrderTemplateAuditLogger $auditLogger,
        private readonly TemplatePlaceholderCoverageService $coverageService,
        private readonly OrderTemplateRenderer $renderer,
    ) {}

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function templateOptions(): array
    {
        return $this->readRepository->templateOptions();
    }

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function orderTypeOptionsForTemplate(int $templateId): array
    {
        return $this->readRepository->orderTypeOptionsForTemplate($templateId);
    }

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function versionOptionsForOrderType(int $orderTypeId): array
    {
        return $this->readRepository->versionOptionsForOrderType($orderTypeId);
    }

    /**
     * @return array{created:int,existing:int,first_order_type_id:int|null}
     */
    public function ensureTemplateSets(int $templateId): array
    {
        $types = OrderType::query()
            ->where('order_id', $templateId)
            ->get(['id', 'name']);

        if ($types->isEmpty()) {
            throw new RuntimeException(__('No order types found for selected template.'));
        }

        $created = 0;
        $existing = 0;

        foreach ($types as $type) {
            $set = OrderTemplateSet::query()
                ->where('order_type_id', (int) $type->id)
                ->first();

            if ($set) {
                $existing++;
                continue;
            }

            OrderTemplateSet::query()->create([
                'order_type_id' => (int) $type->id,
                'name' => (string) $type->name,
                'description' => __('Auto-created from onboarding wizard'),
            ]);
            $created++;
        }

        return [
            'created' => $created,
            'existing' => $existing,
            'first_order_type_id' => (int) ($types->first()?->id ?? 0) ?: null,
        ];
    }

    public function createDraftVersion(int $orderTypeId, ?int $selectedVersionId, ?int $actorId): OrderTemplateVersion
    {
        $context = $this->resolveTypeContext($orderTypeId);
        if (! $context) {
            throw new RuntimeException(__('Order type not found.'));
        }

        $set = $context->templateSet;
        if (! $set) {
            $set = OrderTemplateSet::query()->create([
                'order_type_id' => (int) $context->id,
                'name' => (string) $context->name,
                'description' => __('Auto-created from onboarding wizard'),
            ]);
        }

        $set->load(['versions' => fn ($query) => $query->orderByDesc('version_no')->orderByDesc('id')]);
        $baseVersionId = $selectedVersionId ?: (int) ($set->versions->firstWhere('is_active', true)?->id ?? 0);

        $created = null;
        if ($set->versions->isNotEmpty()) {
            $created = $this->lifecycleService->createDraftFromVersion(
                (int) $context->id,
                $baseVersionId > 0 ? $baseVersionId : null,
                $actorId
            );
        }

        if ($created) {
            return $created;
        }

        $nextVersionNo = ((int) ($set->versions()->max('version_no') ?? 0)) + 1;
        $templatePath = trim((string) ($context->order?->content ?? ''));

        /** @var OrderTemplateVersion $createdVersion */
        $createdVersion = $set->versions()->create([
            'version_no' => $nextVersionNo,
            'template_name' => (string) ($context->order?->name ?? $context->name),
            'template_path' => $templatePath,
            'checksum' => $this->resolveStoredChecksum($templatePath),
            'status' => 'draft',
            'is_active' => false,
            'published_at' => null,
            'meta' => [
                'order_type_id' => (int) $context->id,
                'source' => 'onboarding_wizard',
            ],
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        return $createdVersion;
    }

    /**
     * @param  mixed  $docxFile
     * @return array{checksum:string|null}
     */
    public function uploadDocxToVersion(int $orderTypeId, int $versionId, mixed $docxFile, ?int $actorId): array
    {
        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with('templateSet.orderType.order')
            ->find($versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== $orderTypeId) {
            throw new RuntimeException(__('Selected version is invalid for this order type.'));
        }

        $templateName = (string) ($version->template_name ?: $version->templateSet->name ?: 'template');
        $fileName = sprintf(
            '%s-v%s-%s.docx',
            Str::slug($templateName),
            (int) $version->version_no,
            now()->format('Ymd_His')
        );

        $storedPath = $docxFile->storeAs(
            'templates/order-types/'.$orderTypeId,
            $fileName,
            'public'
        );

        $checksum = $this->resolveStoredChecksum((string) $storedPath);

        $version->update([
            'template_path' => (string) $storedPath,
            'checksum' => $checksum,
            'status' => 'draft',
            'updated_by' => $actorId,
        ]);

        return ['checksum' => $checksum];
    }

    /**
     * @return array{
     * created_fields:int,
     * created_mappings:int,
     * updated_fields:int,
     * updated_mappings:int,
     * deleted_fields:int,
     * deleted_mappings:int
     * }
     */
    public function generateMetadataAndMappings(int $orderTypeId, int $versionId, ?int $actorId): array
    {
        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with('templateSet.orderType.order')
            ->find($versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== $orderTypeId) {
            throw new RuntimeException(__('Selected version is invalid.'));
        }

        $result = $this->metadataSyncService->sync(
            $version,
            $orderTypeId,
            (string) ($version->templateSet->orderType?->order?->blade ?? ''),
            true,
            $actorId
        );

        $this->templateRegistry->invalidate($orderTypeId);
        $this->schemaService->invalidateCachedSchema($orderTypeId, $versionId);
        $this->auditLogger->log($versionId, 'wizard_metadata_generated', [
            'created_fields' => (int) ($result['created_fields'] ?? 0),
            'created_mappings' => (int) ($result['created_mappings'] ?? 0),
            'updated_fields' => (int) ($result['updated_fields'] ?? 0),
            'updated_mappings' => (int) ($result['updated_mappings'] ?? 0),
            'deleted_fields' => (int) ($result['deleted_fields'] ?? 0),
            'deleted_mappings' => (int) ($result['deleted_mappings'] ?? 0),
        ], $actorId);

        return [
            'created_fields' => (int) ($result['created_fields'] ?? 0),
            'created_mappings' => (int) ($result['created_mappings'] ?? 0),
            'updated_fields' => (int) ($result['updated_fields'] ?? 0),
            'updated_mappings' => (int) ($result['updated_mappings'] ?? 0),
            'deleted_fields' => (int) ($result['deleted_fields'] ?? 0),
            'deleted_mappings' => (int) ($result['deleted_mappings'] ?? 0),
        ];
    }

    public function runCoverageScan(int $versionId): array
    {
        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with(['mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->find($versionId);

        if (! $version) {
            throw new RuntimeException(__('Version not found.'));
        }

        $mappings = $version->mappings
            ->map(fn (OrderTemplateMapping $mapping) => [
                'placeholder' => (string) $mapping->placeholder,
                'field_key' => (string) $mapping->field_key,
                'scope' => (string) $mapping->scope,
            ])
            ->values()
            ->all();

        return $this->coverageService->analyzeForVersion($version, $mappings);
    }

    /**
     * @param  array<string,mixed>  $coverage
     * @return array{published:OrderTemplateVersion,coverage:array<string,mixed>}
     */
    public function publishSelectedVersion(
        int $orderTypeId,
        int $versionId,
        bool $previewSucceeded,
        ?string $previewOutputPath,
        array $coverage,
        ?int $actorId
    ): array {
        if (! $previewSucceeded || ! $previewOutputPath || ! is_file($previewOutputPath)) {
            throw new RuntimeException(__('Run preview render before publishing.'));
        }

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with('templateSet:id,order_type_id')
            ->find($versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== $orderTypeId) {
            throw new RuntimeException(__('Selected version is invalid for this order type.'));
        }

        if ($coverage === []) {
            $coverage = $this->runCoverageScan($versionId);
        }

        if (empty($coverage['inspectable'])) {
            throw new RuntimeException(__('Template coverage is not inspectable. Upload DOCX first.'));
        }

        if (! empty($coverage['missing_placeholders'])) {
            throw new RuntimeException(__('Cannot publish while missing mappings exist.'));
        }

        $published = $this->lifecycleService->publishVersion($versionId, $actorId);
        if (! $published) {
            throw new RuntimeException(__('Could not publish selected version.'));
        }

        return [
            'published' => $published,
            'coverage' => $coverage,
        ];
    }

    /**
     * @return array{coverage:array<string,mixed>, output_path:string, output_name:string}
     */
    public function runPreviewRender(int $orderTypeId, int $versionId, ?string $actorName = null): array
    {
        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with([
                'templateSet:id,order_type_id,name',
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find($versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== $orderTypeId) {
            throw new RuntimeException(__('Selected version is invalid.'));
        }

        $coverage = $this->coverageService->analyzeForVersion(
            $version,
            $version->mappings
                ->map(fn (OrderTemplateMapping $mapping) => [
                    'placeholder' => (string) $mapping->placeholder,
                    'field_key' => (string) $mapping->field_key,
                    'scope' => (string) $mapping->scope,
                ])
                ->values()
                ->all()
        );

        if (empty($coverage['inspectable'])) {
            throw new RuntimeException(__('Template coverage is unavailable. Upload DOCX and run coverage first.'));
        }

        if (! empty($coverage['missing_placeholders'])) {
            throw new RuntimeException(__('Cannot run preview. Missing mappings: :placeholders', [
                'placeholders' => implode(', ', $coverage['missing_placeholders']),
            ]));
        }

        $fieldMap = $version->fields
            ->keyBy(fn (OrderTemplateField $field) => ltrim((string) $field->field_key, '$'));

        $scalarValues = $this->buildPreviewScalarValues($version->mappings, $fieldMap->all(), $actorName);
        $rowValues = $this->buildPreviewRowValues($version->mappings, $fieldMap->all());
        $rows = empty($rowValues) ? [] : [$rowValues];

        try {
            $outputPath = $this->renderer->render(
                (string) $version->template_path,
                $scalarValues,
                $rows,
                sprintf('preview-v%s', (int) $version->version_no),
                [
                    'order_type_id' => $orderTypeId,
                    'template_version_id' => (int) $version->id,
                    'is_preview' => true,
                ]
            );
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }

        return [
            'coverage' => $coverage,
            'output_path' => $outputPath,
            'output_name' => basename($outputPath),
        ];
    }

    public function resolveVersionChecksum(int $versionId): ?string
    {
        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()->find($versionId);
        if (! $version) {
            return null;
        }

        return trim((string) ($version->checksum ?? '')) !== ''
            ? (string) $version->checksum
            : $this->resolveStoredChecksum((string) $version->template_path);
    }

    private function resolveTypeContext(int $orderTypeId): ?OrderType
    {
        return OrderType::query()
            ->with([
                'order:id,name,content,blade',
                'templateSet.versions' => fn ($query) => $query->orderByDesc('version_no')->orderByDesc('id'),
            ])
            ->find($orderTypeId);
    }

    private function resolveStoredChecksum(string $relativePath): ?string
    {
        $path = trim($relativePath);
        if ($path === '') {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        if (! is_file($absolutePath)) {
            return null;
        }

        $hash = @hash_file('sha256', $absolutePath);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int,OrderTemplateMapping>  $mappings
     * @param  array<string,OrderTemplateField>  $fieldMap
     * @return array<string,string>
     */
    private function buildPreviewScalarValues($mappings, array $fieldMap, ?string $actorName): array
    {
        $today = now();
        $defaultScalars = [
            'day' => $today->format('d'),
            'month' => $today->translatedFormat('F'),
            'year' => $today->format('Y'),
            'rank_director' => 'general-mayor',
            'name_director' => (string) ($actorName ?: 'Director'),
        ];

        foreach ($mappings as $mapping) {
            if ((string) $mapping->scope !== 'scalar') {
                continue;
            }

            $placeholder = $this->normalizePlaceholderForPreview((string) $mapping->placeholder);
            if ($placeholder === '') {
                continue;
            }

            $fieldKey = ltrim((string) $mapping->field_key, '$');
            $field = $fieldMap[$fieldKey] ?? null;
            $defaultScalars[$placeholder] = $this->sampleValueForField($fieldKey, $field);
        }

        return $defaultScalars;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int,OrderTemplateMapping>  $mappings
     * @param  array<string,OrderTemplateField>  $fieldMap
     * @return array<string,string>
     */
    private function buildPreviewRowValues($mappings, array $fieldMap): array
    {
        $rowValues = [];

        foreach ($mappings as $mapping) {
            if ((string) $mapping->scope === 'scalar') {
                continue;
            }

            $placeholder = $this->normalizePlaceholderForPreview((string) $mapping->placeholder);
            if ($placeholder === '') {
                continue;
            }

            $fieldKey = ltrim((string) $mapping->field_key, '$');
            $field = $fieldMap[$fieldKey] ?? null;
            $rowValues[$placeholder] = $this->sampleValueForField($fieldKey, $field);
        }

        return $rowValues;
    }

    private function normalizePlaceholderForPreview(string $placeholder): string
    {
        $normalized = trim($placeholder);
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/^\$\{(.+)\}$/', '$1', $normalized) ?: $normalized;
        $normalized = trim($normalized, '{} ');
        $normalized = ltrim($normalized, '$');
        $normalized = preg_replace('/#\d+$/', '', $normalized) ?: $normalized;

        return trim($normalized);
    }

    private function sampleValueForField(string $fieldKey, ?OrderTemplateField $field): string
    {
        $normalizedKey = ltrim(trim($fieldKey), '$');
        $fieldType = strtolower(trim((string) ($field?->field_type ?? '')));

        return match ($normalizedKey) {
            'fullname', 'name' => 'Test User',
            'rank', 'rank_director' => 'Leytenant',
            'day' => now()->format('d'),
            'month' => now()->translatedFormat('F'),
            'year' => now()->format('Y'),
            'structure_main' => '1-ci əsas strukturun',
            'structure' => '18-ci idarenin',
            'position' => 'Proqramçı',
            default => match ($fieldType) {
                'integer', 'int', 'number', 'numeric' => '1',
                'date', 'datetime' => now()->format('Y-m-d'),
                default => Str::headline(str_replace('_', ' ', $normalizedKey)),
            },
        };
    }
}
