<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Modules\Orders\Application\UseCases\Templates\TemplateOnboardingWizardUseCase;
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
        return app(TemplateOnboardingWizardUseCase::class)->templateOptions();
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getOrderTypeOptionsProperty(): array
    {
        return app(TemplateOnboardingWizardUseCase::class)
            ->orderTypeOptionsForTemplate((int) ($this->templateId ?? 0));
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getVersionOptionsProperty(): array
    {
        return app(TemplateOnboardingWizardUseCase::class)
            ->versionOptionsForOrderType((int) ($this->orderTypeId ?? 0));
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

    public function createDraftVersion(): void
    {
        if (! $this->ensureWizardPermission('version')) {
            return;
        }

        if (! $this->orderTypeId) {
            $this->dispatch('addError', __('Please select an order type first.'));
            return;
        }

        try {
            $created = app(TemplateOnboardingWizardUseCase::class)->createDraftVersion(
                (int) $this->orderTypeId,
                $this->versionId ? (int) $this->versionId : null,
                auth()->id()
            );
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());

            return;
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

        try {
            $result = app(TemplateOnboardingWizardUseCase::class)->uploadDocxToVersion(
                (int) $this->orderTypeId,
                (int) $this->versionId,
                $this->docxFile,
                auth()->id()
            );
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $this->docxFile = null;
        $this->uploadedChecksum = (string) ($result['checksum'] ?? '');
        $this->uploadResult = __('DOCX uploaded and linked to selected version.');
        $this->resetPreviewState();
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('DOCX uploaded successfully.'));
    }

    public function generateMetadataAndMappings(): void {
        if (! $this->ensureWizardPermission('metadata')) {
            return;
        }

        if (! $this->orderTypeId || ! $this->versionId) {
            $this->dispatch('addError', __('Please select an order type/version first.'));
            return;
        }

        try {
            $result = app(TemplateOnboardingWizardUseCase::class)->generateMetadataAndMappings(
                (int) $this->orderTypeId,
                (int) $this->versionId,
                auth()->id()
            );
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $createdFields = (int) ($result['created_fields'] ?? 0);
        $createdMappings = (int) ($result['created_mappings'] ?? 0);
        $deletedFields = (int) ($result['deleted_fields'] ?? 0);
        $deletedMappings = (int) ($result['deleted_mappings'] ?? 0);
        $updatedFields = (int) ($result['updated_fields'] ?? 0);
        $updatedMappings = (int) ($result['updated_mappings'] ?? 0);

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

    public function runCoverageScan(): void
    {
        if (! $this->ensureWizardPermission('view')) {
            return;
        }

        if (! $this->versionId) {
            $this->dispatch('addError', __('Please select a version first.'));
            return;
        }

        try {
            $this->coverage = app(TemplateOnboardingWizardUseCase::class)
                ->runCoverageScan((int) $this->versionId);
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
        }
    }

    public function publishSelectedVersion(): void
    {
        if (! $this->ensureWizardPermission('version')) {
            return;
        }

        if (! $this->versionId || ! $this->orderTypeId) {
            $this->dispatch('addError', __('Please select a version first.'));
            return;
        }

        try {
            $result = app(TemplateOnboardingWizardUseCase::class)->publishSelectedVersion(
                (int) $this->orderTypeId,
                (int) $this->versionId,
                (bool) $this->previewSucceeded,
                $this->previewOutputPath,
                $this->coverage,
                auth()->id()
            );
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $published = $result['published'] ?? null;
        $this->coverage = is_array($result['coverage'] ?? null) ? $result['coverage'] : [];

        $this->versionId = (int) ($published?->id ?? $this->versionId);
        $this->publishResult = __('Version published successfully.');
        $this->refreshVersionState();
        $this->dispatch('typesUpdated', __('Version published successfully.'));
    }

    public function runPreviewRender(): void
    {
        if (! $this->ensureWizardPermission('metadata')) {
            return;
        }

        if (! $this->orderTypeId || ! $this->versionId) {
            $this->dispatch('addError', __('Please select an order type/version first.'));
            return;
        }

        try {
            $result = app(TemplateOnboardingWizardUseCase::class)->runPreviewRender(
                (int) $this->orderTypeId,
                (int) $this->versionId,
                (string) (auth()->user()?->name ?? 'Director')
            );
        } catch (Throwable $exception) {
            $this->previewSucceeded = false;
            $this->previewOutputPath = null;
            $this->previewOutputName = null;
            $this->previewResult = '';
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $this->coverage = is_array($result['coverage'] ?? null) ? $result['coverage'] : [];
        $this->previewSucceeded = true;
        $this->previewOutputPath = (string) ($result['output_path'] ?? '');
        $this->previewOutputName = (string) ($result['output_name'] ?? '');
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

        try {
            $result = app(TemplateOnboardingWizardUseCase::class)->ensureTemplateSets((int) $this->templateId);
        } catch (Throwable $exception) {
            $this->dispatch('addError', $exception->getMessage());
            return;
        }

        $this->setEnsureResult = __('Created: :created, Existing: :existing', [
            'created' => (int) ($result['created'] ?? 0),
            'existing' => (int) ($result['existing'] ?? 0),
        ]);

        $this->dispatch('typesUpdated', __('Template sets ensured successfully. :result', [
            'result' => $this->setEnsureResult,
        ]));

        if (! $this->orderTypeId) {
            $firstTypeId = (int) ($result['first_order_type_id'] ?? 0);
            if ($firstTypeId > 0) {
                $this->orderTypeId = $firstTypeId;
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

        $checksum = app(TemplateOnboardingWizardUseCase::class)
            ->resolveVersionChecksum((int) $this->versionId);

        if ($checksum === null) {
            $this->coverage = [];
            $this->currentChecksum = null;
            return;
        }

        $this->currentChecksum = $checksum;

        $this->runCoverageScan();
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

    public function render()
    {
        return view('orders::livewire.orders.templates.onboarding-wizard');
    }
}
