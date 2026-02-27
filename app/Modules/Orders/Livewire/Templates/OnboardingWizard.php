<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Models\Component as OrderComponent;
use App\Models\Order;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateField;
use App\Models\OrderType;
use App\Services\GenerateDynamicFieldsService;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\OrderTemplateVersionLifecycleService;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class OnboardingWizard extends Component
{
    use WithFileUploads;

    public ?int $templateId = null;
    public ?int $orderTypeId = null;
    public ?int $versionId = null;

    public mixed $docxFile = null;

    public string $setEnsureResult = '';
    public string $versionResult = '';
    public string $uploadResult = '';
    public string $metadataResult = '';
    public string $publishResult = '';

    public ?string $currentChecksum = null;
    public ?string $uploadedChecksum = null;

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
        $this->refreshVersionState();
    }

    public function openUiConfigForSelectedTemplate(): void
    {
        if (! $this->templateId) {
            $this->dispatch('addError', __('Please select a template first.'));

            return;
        }

        $this->dispatch('openSetTypeFromTemplateEdit', templateId: (int) $this->templateId);
    }

    public function createDraftVersion(OrderTemplateVersionLifecycleService $lifecycleService): void
    {
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
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Draft version created successfully.'));
    }

    public function uploadDocxForSelectedVersion(): void
    {
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
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('DOCX uploaded successfully.'));
    }

    public function generateMetadataAndMappings(
        GenerateDynamicFieldsService $dynamicFieldsService,
        TemplateRegistry $templateRegistry,
        OrderTemplateFormSchemaService $schemaService,
        OrderTemplateAuditLogger $auditLogger
    ): void {
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

        $tokens = OrderComponent::query()
            ->where('order_type_id', (int) $this->orderTypeId)
            ->pluck('dynamic_fields')
            ->flatMap(fn ($fields) => $this->extractDynamicTokens($fields))
            ->filter(fn ($field) => is_string($field) && trim((string) $field) !== '')
            ->map(fn ($field) => '$'.ltrim(trim((string) $field), '$'))
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            $tokens = collect($this->fallbackTokensForBlade((string) ($version->templateSet->orderType?->order?->blade ?? '')));
        }

        $legacyCatalog = $dynamicFieldsService->handle();
        $createdFields = 0;
        $createdMappings = 0;

        DB::transaction(function () use ($version, $tokens, $legacyCatalog, &$createdFields, &$createdMappings): void {
            foreach ($tokens->values() as $index => $token) {
                $fieldKey = ltrim((string) $token, '$');
                if ($fieldKey === '') {
                    continue;
                }

                $legacyDefinition = is_array($legacyCatalog[$token] ?? null) ? $legacyCatalog[$token] : [];
                $resolvedInput = $this->resolveLegacyInput($legacyDefinition);
                if ($fieldKey === 'structure' || (string) ($legacyDefinition['field'] ?? '') === 'structure_id') {
                    $resolvedInput = 'radio-list';
                }

                $uiConfig = array_filter([
                    'field' => (string) ($legacyDefinition['field'] ?? $fieldKey),
                    'input' => $resolvedInput,
                    'model' => $legacyDefinition['model'] ?? null,
                    'selectedName' => $legacyDefinition['selectedName'] ?? null,
                    'searchField' => $legacyDefinition['searchField'] ?? null,
                    'group' => 'main',
                    'group_order' => 0,
                    'field_order' => (($index + 1) * 10),
                    'grid_cols' => ['default' => 1, 'sm' => 2, 'md' => 3],
                    'col_span' => ['default' => 1],
                ], static fn ($value) => $value !== null && $value !== '');

                $field = OrderTemplateField::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->where('field_key', $fieldKey)
                    ->first();

                if (! $field) {
                    OrderTemplateField::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'field_key' => $fieldKey,
                        'label' => (string) ($legacyDefinition['title'] ?? Str::headline(str_replace('_', ' ', $fieldKey))),
                        'field_type' => $this->mapInputToFieldType($resolvedInput),
                        'is_required' => false,
                        'sort_order' => (($index + 1) * 10),
                        'default_value' => null,
                        'data_source' => null,
                        'ui_config' => $uiConfig,
                        'transform_config' => null,
                        'validation_config' => null,
                    ]);
                    $createdFields++;
                }

                $mapping = OrderTemplateMapping::query()
                    ->where('order_template_version_id', (int) $version->id)
                    ->where('placeholder', (string) $token)
                    ->where('scope', 'row')
                    ->first();

                if (! $mapping) {
                    OrderTemplateMapping::query()->create([
                        'order_template_version_id' => (int) $version->id,
                        'placeholder' => (string) $token,
                        'field_key' => $fieldKey,
                        'scope' => 'row',
                        'sort_order' => (($index + 1) * 10),
                        'mapping_config' => null,
                    ]);
                    $createdMappings++;
                }
            }
        });

        $templateRegistry->invalidate((int) $this->orderTypeId);
        $schemaService->invalidateCachedSchema((int) $this->orderTypeId, (int) $this->versionId);
        $auditLogger->log((int) $this->versionId, 'wizard_metadata_generated', [
            'created_fields' => $createdFields,
            'created_mappings' => $createdMappings,
        ], auth()->id());

        $this->metadataResult = __('Metadata generated. Fields: :fields, Mappings: :mappings', [
            'fields' => $createdFields,
            'mappings' => $createdMappings,
        ]);
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Metadata + mappings generated successfully.'));
    }

    public function runCoverageScan(TemplatePlaceholderCoverageService $coverageService): void
    {
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
        if (! $this->versionId || ! $this->orderTypeId) {
            $this->dispatch('addError', __('Please select a version first.'));
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

        $published = $lifecycleService->publishVersion((int) $this->versionId, auth()->id());
        if (! $published) {
            $this->dispatch('addError', __('Could not publish selected version.'));
            return;
        }

        $this->versionId = (int) $published->id;
        $this->publishResult = __('Version published successfully.');
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Version published successfully.'));
    }

    public function ensureTemplateSetsForSelectedTemplate(): void
    {
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

    private function resetStepMessages(): void
    {
        $this->setEnsureResult = '';
        $this->versionResult = '';
        $this->uploadResult = '';
        $this->metadataResult = '';
        $this->publishResult = '';
        $this->currentChecksum = null;
        $this->uploadedChecksum = null;
        $this->docxFile = null;
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

    private function fallbackTokensForBlade(string $blade): array
    {
        return match ($blade) {
            Order::BLADE_VACATION => ['$start_date', '$end_date', '$days', '$fullname', '$location'],
            Order::BLADE_BUSINESS_TRIP => ['$start_date', '$end_date', '$location', '$fullname', '$transportation'],
            default => ['$fullname', '$rank', '$day', '$month', '$year', '$structure_main', '$structure', '$position'],
        };
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

    public function render()
    {
        return view('orders::livewire.orders.templates.onboarding-wizard');
    }
}
