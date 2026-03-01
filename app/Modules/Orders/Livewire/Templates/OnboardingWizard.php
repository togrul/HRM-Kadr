<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Models\Order;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateField;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\OrderTemplateMetadataSyncService;
use App\Services\Orders\OrderTemplateRenderer;
use App\Services\Orders\OrderTemplateVersionLifecycleService;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Livewire\Component;
use Livewire\WithFileUploads;

class OnboardingWizard extends Component
{
    use WithFileUploads;

    private const TEMPLATE_WIZARD_PERMISSION_MATRIX = [
        'view' => ['show-orders', 'edit-orders'],
        'metadata' => ['manage-order-template-metadata', 'edit-orders'],
        'version' => ['manage-order-template-versions', 'edit-orders'],
        'set' => ['manage-order-template-sets', 'edit-orders'],
    ];

    public ?int $templateId = null;
    public ?int $orderTypeId = null;
    public ?int $versionId = null;

    public mixed $docxFile = null;

    public string $setEnsureResult = '';
    public string $versionResult = '';
    public string $uploadResult = '';
    public string $metadataResult = '';
    public string $publishResult = '';
    public string $previewResult = '';

    public ?string $currentChecksum = null;
    public ?string $uploadedChecksum = null;
    public ?string $previewOutputPath = null;
    public ?string $previewOutputName = null;
    public bool $previewSucceeded = false;

    public array $coverage = [];

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getTemplateOptionsProperty(): array
    {
        return Order::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Order $order): array => [
                'id' => (int) $order->id,
                'label' => (string) $order->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getOrderTypeOptionsProperty(): array
    {
        if (! $this->templateId) {
            return [];
        }

        return OrderType::query()
            ->where('order_id', (int) $this->templateId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (OrderType $type): array => [
                'id' => (int) $type->id,
                'label' => (string) $type->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getVersionOptionsProperty(): array
    {
        if (! $this->orderTypeId) {
            return [];
        }

        $setId = OrderTemplateSet::query()
            ->where('order_type_id', (int) $this->orderTypeId)
            ->value('id');

        if (! $setId) {
            return [];
        }

        return OrderTemplateVersion::query()
            ->where('order_template_set_id', (int) $setId)
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->get(['id', 'version_no', 'status', 'is_active'])
            ->map(fn (OrderTemplateVersion $version): array => [
                'id' => (int) $version->id,
                'label' => sprintf(
                    'v%s (%s%s)',
                    (int) $version->version_no,
                    (string) $version->status,
                    $version->is_active ? ', active' : ''
                ),
            ])
            ->values()
            ->all();
    }

    public function updatedTemplateId($value): void
    {
        if (! $value) {
            $this->orderTypeId = null;
            $this->versionId = null;
            $this->coverage = [];
            $this->resetStepMessages();
            return;
        }

        $this->resetStepMessages();
        $this->orderTypeId = null;
        $this->versionId = null;
        $this->coverage = [];
        $this->resetPreviewState();

        $firstType = collect($this->orderTypeOptions)->first();
        if ($firstType && isset($firstType['id'])) {
            $this->orderTypeId = (int) $firstType['id'];
        }
    }

    public function updatedOrderTypeId($value): void
    {
        if (! $value) {
            $this->versionId = null;
            $this->coverage = [];
            $this->currentChecksum = null;
            $this->uploadedChecksum = null;
            return;
        }

        $this->versionResult = '';
        $this->uploadResult = '';
        $this->metadataResult = '';
        $this->publishResult = '';
        $this->coverage = [];
        $this->resetPreviewState();

        $firstVersion = collect($this->versionOptions)->first();
        $this->versionId = $firstVersion['id'] ?? null;
        $this->refreshVersionState();
    }

    public function updatedVersionId($value): void
    {
        if (! $value) {
            $this->currentChecksum = null;
            $this->coverage = [];
            return;
        }

        $this->uploadResult = '';
        $this->metadataResult = '';
        $this->publishResult = '';
        $this->previewResult = '';
        $this->refreshVersionState();
    }

    public function openUiConfigForSelectedTemplate(): void
    {
        if (! $this->ensureWizardPermission('view')) {
            return;
        }

        if (! $this->templateId) {
            $this->dispatch('addError', __('Please select a template first.'));

            return;
        }

        $this->dispatch('openSetTypeFromTemplateEdit', templateId: (int) $this->templateId);
    }

    public function createDraftVersion(OrderTemplateVersionLifecycleService $lifecycleService): void
    {
        if (! $this->ensureWizardPermission('version')) {
            return;
        }

        if (! $this->orderTypeId) {
            $this->dispatch('addError', __('Please select an order type first.'));
            return;
        }

        $context = $this->resolveTypeContext((int) $this->orderTypeId);
        if (! $context) {
            $this->dispatch('addError', __('Order type not found.'));
            return;
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
        $baseVersionId = $this->versionId ?: (int) ($set->versions->firstWhere('is_active', true)?->id ?? 0);

        $created = null;
        if ($set->versions->isNotEmpty()) {
            $created = $lifecycleService->createDraftFromVersion(
                (int) $context->id,
                $baseVersionId > 0 ? $baseVersionId : null,
                auth()->id()
            );
        }

        if (! $created) {
            $nextVersionNo = ((int) ($set->versions()->max('version_no') ?? 0)) + 1;
            $templatePath = trim((string) ($context->order?->content ?? ''));

            $created = $set->versions()->create([
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
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $this->versionId = (int) $created->id;
        $this->versionResult = __('Draft version created: v:version', ['version' => (int) $created->version_no]);
        $this->resetPreviewState();
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Draft version created successfully.'));
    }

    public function uploadDocxForSelectedVersion(): void
    {
        if (! $this->ensureWizardPermission('version')) {
            return;
        }

        if (! $this->orderTypeId || ! $this->versionId) {
            $this->dispatch('addError', __('Please create/select a version first.'));
            return;
        }

        $this->validate([
            'docxFile' => 'required|file|mimes:docx|max:10240',
        ]);

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with('templateSet.orderType.order')
            ->find((int) $this->versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== (int) $this->orderTypeId) {
            $this->dispatch('addError', __('Selected version is invalid for this order type.'));
            return;
        }

        $templateName = (string) ($version->template_name ?: $version->templateSet->name ?: 'template');
        $fileName = sprintf(
            '%s-v%s-%s.docx',
            Str::slug($templateName),
            (int) $version->version_no,
            now()->format('Ymd_His')
        );

        $storedPath = $this->docxFile->storeAs(
            'templates/order-types/'.(int) $this->orderTypeId,
            $fileName,
            'public'
        );

        $checksum = $this->resolveStoredChecksum((string) $storedPath);

        $version->update([
            'template_path' => (string) $storedPath,
            'checksum' => $checksum,
            'status' => 'draft',
            'updated_by' => auth()->id(),
        ]);

        $this->docxFile = null;
        $this->uploadedChecksum = $checksum;
        $this->uploadResult = __('DOCX uploaded and linked to selected version.');
        $this->resetPreviewState();
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('DOCX uploaded successfully.'));
    }

    public function generateMetadataAndMappings(
        OrderTemplateMetadataSyncService $metadataSyncService,
        TemplateRegistry $templateRegistry,
        OrderTemplateFormSchemaService $schemaService,
        OrderTemplateAuditLogger $auditLogger
    ): void {
        if (! $this->ensureWizardPermission('metadata')) {
            return;
        }

        if (! $this->orderTypeId || ! $this->versionId) {
            $this->dispatch('addError', __('Please select an order type/version first.'));
            return;
        }

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with('templateSet.orderType.order')
            ->find((int) $this->versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== (int) $this->orderTypeId) {
            $this->dispatch('addError', __('Selected version is invalid.'));
            return;
        }

        $result = $metadataSyncService->sync(
            $version,
            (int) $this->orderTypeId,
            (string) ($version->templateSet->orderType?->order?->blade ?? ''),
            true,
            auth()->id()
        );

        $createdFields = (int) ($result['created_fields'] ?? 0);
        $createdMappings = (int) ($result['created_mappings'] ?? 0);
        $deletedFields = (int) ($result['deleted_fields'] ?? 0);
        $deletedMappings = (int) ($result['deleted_mappings'] ?? 0);
        $updatedFields = (int) ($result['updated_fields'] ?? 0);
        $updatedMappings = (int) ($result['updated_mappings'] ?? 0);

        $templateRegistry->invalidate((int) $this->orderTypeId);
        $schemaService->invalidateCachedSchema((int) $this->orderTypeId, (int) $this->versionId);
        $auditLogger->log((int) $this->versionId, 'wizard_metadata_generated', [
            'created_fields' => $createdFields,
            'created_mappings' => $createdMappings,
            'updated_fields' => $updatedFields,
            'updated_mappings' => $updatedMappings,
            'deleted_fields' => $deletedFields,
            'deleted_mappings' => $deletedMappings,
        ], auth()->id());

        $this->metadataResult = __('Metadata synced. Fields +: :fields, ~: :updated_fields, -: :deleted_fields | Mappings +: :mappings, ~: :updated_mappings, -: :deleted_mappings', [
            'fields' => $createdFields,
            'updated_fields' => $updatedFields,
            'deleted_fields' => $deletedFields,
            'mappings' => $createdMappings,
            'updated_mappings' => $updatedMappings,
            'deleted_mappings' => $deletedMappings,
        ]);
        $this->resetPreviewState();
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Metadata + mappings generated successfully.'));
    }

    public function runCoverageScan(TemplatePlaceholderCoverageService $coverageService): void
    {
        if (! $this->ensureWizardPermission('view')) {
            return;
        }

        if (! $this->versionId) {
            $this->dispatch('addError', __('Please select a version first.'));
            return;
        }

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with(['mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->find((int) $this->versionId);

        if (! $version) {
            $this->dispatch('addError', __('Version not found.'));
            return;
        }

        $mappings = $version->mappings
            ->map(fn (OrderTemplateMapping $mapping) => [
                'placeholder' => (string) $mapping->placeholder,
                'field_key' => (string) $mapping->field_key,
                'scope' => (string) $mapping->scope,
            ])
            ->values()
            ->all();

        $this->coverage = $coverageService->analyzeForVersion($version, $mappings);
    }

    public function publishSelectedVersion(OrderTemplateVersionLifecycleService $lifecycleService): void
    {
        if (! $this->ensureWizardPermission('version')) {
            return;
        }

        if (! $this->versionId || ! $this->orderTypeId) {
            $this->dispatch('addError', __('Please select a version first.'));
            return;
        }

        if (! $this->previewSucceeded || ! $this->previewOutputPath || ! is_file($this->previewOutputPath)) {
            $this->dispatch('addError', __('Run preview render before publishing.'));
            return;
        }

        if (empty($this->coverage)) {
            $this->runCoverageScan(app(TemplatePlaceholderCoverageService::class));
        }

        if (empty($this->coverage['inspectable'])) {
            $this->dispatch('addError', __('Template coverage is not inspectable. Upload DOCX first.'));
            return;
        }

        if (! empty($this->coverage['missing_placeholders'])) {
            $this->dispatch('addError', __('Cannot publish while missing mappings exist.'));
            return;
        }

        try {
            $published = $lifecycleService->publishVersion((int) $this->versionId, auth()->id());
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        if (! $published) {
            $this->dispatch('addError', __('Could not publish selected version.'));
            return;
        }

        $this->versionId = (int) $published->id;
        $this->publishResult = __('Version published successfully.');
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Version published successfully.'));
    }

    public function runPreviewRender(
        OrderTemplateRenderer $renderer,
        TemplatePlaceholderCoverageService $coverageService
    ): void
    {
        if (! $this->ensureWizardPermission('metadata')) {
            return;
        }

        if (! $this->orderTypeId || ! $this->versionId) {
            $this->dispatch('addError', __('Please select an order type/version first.'));
            return;
        }

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()
            ->with([
                'templateSet:id,order_type_id,name',
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find((int) $this->versionId);

        if (! $version || ! $version->templateSet || (int) $version->templateSet->order_type_id !== (int) $this->orderTypeId) {
            $this->dispatch('addError', __('Selected version is invalid.'));
            return;
        }

        $coverage = $coverageService->analyzeForVersion(
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
        $this->coverage = $coverage;

        if (empty($coverage['inspectable'])) {
            $this->dispatch('addError', __('Template coverage is unavailable. Upload DOCX and run coverage first.'));
            return;
        }

        if (! empty($coverage['missing_placeholders'])) {
            $this->dispatch('addError', __('Cannot run preview. Missing mappings: :placeholders', [
                'placeholders' => implode(', ', $coverage['missing_placeholders']),
            ]));
            return;
        }

        try {
            $fieldMap = $version->fields
                ->keyBy(fn (OrderTemplateField $field) => ltrim((string) $field->field_key, '$'));

            $scalarValues = $this->buildPreviewScalarValues($version->mappings, $fieldMap->all());
            $rowValues = $this->buildPreviewRowValues($version->mappings, $fieldMap->all());
            $rows = empty($rowValues) ? [] : [$rowValues];

            $outputPath = $renderer->render(
                (string) $version->template_path,
                $scalarValues,
                $rows,
                sprintf('preview-v%s', (int) $version->version_no),
                [
                    'order_type_id' => (int) $this->orderTypeId,
                    'template_version_id' => (int) $version->id,
                    'is_preview' => true,
                ]
            );
        } catch (Throwable $exception) {
            $this->previewSucceeded = false;
            $this->previewOutputPath = null;
            $this->previewOutputName = null;
            $this->previewResult = '';
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $this->previewSucceeded = true;
        $this->previewOutputPath = $outputPath;
        $this->previewOutputName = basename($outputPath);
        $this->previewResult = __('Preview rendered successfully.');
        $this->dispatch('typesUpdated', __('Preview render completed.'));
    }

    public function downloadPreview()
    {
        if (! $this->previewOutputPath || ! is_file($this->previewOutputPath)) {
            $this->dispatch('addError', __('Preview file is not available. Run preview render first.'));
            return null;
        }

        return response()->download(
            $this->previewOutputPath,
            $this->previewOutputName ?: basename($this->previewOutputPath)
        );
    }

    public function ensureTemplateSetsForSelectedTemplate(): void
    {
        if (! $this->ensureWizardPermission('set')) {
            return;
        }

        if (! $this->templateId) {
            $this->dispatch('addError', __('Please select a template first.'));

            return;
        }

        $types = OrderType::query()
            ->where('order_id', (int) $this->templateId)
            ->get(['id', 'name']);

        if ($types->isEmpty()) {
            $this->dispatch('addError', __('No order types found for selected template.'));

            return;
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

        $this->setEnsureResult = __('Created: :created, Existing: :existing', [
            'created' => $created,
            'existing' => $existing,
        ]);

        $this->dispatch('typesUpdated', __('Template sets ensured successfully. :result', [
            'result' => $this->setEnsureResult,
        ]));

        if (! $this->orderTypeId) {
            $firstType = collect($this->orderTypeOptions)->first();
            if ($firstType && isset($firstType['id'])) {
                $this->orderTypeId = (int) $firstType['id'];
            }
        }
    }

    private function refreshVersionState(): void
    {
        if (! $this->versionId) {
            $this->coverage = [];
            $this->currentChecksum = null;
            return;
        }

        /** @var OrderTemplateVersion|null $version */
        $version = OrderTemplateVersion::query()->find((int) $this->versionId);
        if (! $version) {
            $this->coverage = [];
            $this->currentChecksum = null;
            return;
        }

        $this->currentChecksum = trim((string) ($version->checksum ?? '')) !== ''
            ? (string) $version->checksum
            : $this->resolveStoredChecksum((string) $version->template_path);

        $this->runCoverageScan(app(TemplatePlaceholderCoverageService::class));
    }

    private function ensureWizardPermission(string $scope): bool
    {
        $user = auth()->user();
        if (! $user) {
            $this->dispatch('addError', __('You do not have permission to perform this action.'));
            return false;
        }

        $permissions = self::TEMPLATE_WIZARD_PERMISSION_MATRIX[$scope] ?? ['edit-orders'];
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        $this->dispatch('addError', __('You do not have permission to perform this action.'));

        return false;
    }

    private function resetStepMessages(): void
    {
        $this->setEnsureResult = '';
        $this->versionResult = '';
        $this->uploadResult = '';
        $this->metadataResult = '';
        $this->publishResult = '';
        $this->previewResult = '';
        $this->currentChecksum = null;
        $this->uploadedChecksum = null;
        $this->docxFile = null;
        $this->resetPreviewState();
    }

    private function resetPreviewState(): void
    {
        $this->previewSucceeded = false;
        $this->previewOutputPath = null;
        $this->previewOutputName = null;
        $this->previewResult = '';
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
    private function buildPreviewScalarValues($mappings, array $fieldMap): array
    {
        $today = now();
        $defaultScalars = [
            'day' => $today->format('d'),
            'month' => $today->translatedFormat('F'),
            'year' => $today->format('Y'),
            'rank_director' => 'general-mayor',
            'name_director' => (string) (auth()->user()?->name ?? 'Director'),
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

    public function render()
    {
        return view('orders::livewire.orders.templates.onboarding-wizard');
    }
}
