<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrOnboardingReadService;
use App\Modules\Personnel\Application\Services\MyHr\OnboardingAdminReportReadService;
use App\Modules\Personnel\Application\Services\MyHr\OnboardingAssignmentManagerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class OnboardingAssignmentManager extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $personnelModel;

    public Personnel $personnel;

    public string $searchTemplate = '';

    public $templateUpload = null;

    public array $templateForm = [
        'title' => '',
        'document_type' => 'policy',
        'version' => '1.0',
        'effective_from' => null,
        'effective_to' => null,
        'is_required' => true,
        'requires_acknowledgement' => true,
    ];

    public array $assignmentForm = [
        'template_id' => null,
        'due_at' => null,
    ];

    public function mount(int $personnelModel): void
    {
        abort_unless($this->canManageTemplates() || $this->canAssignDocuments(), 403);

        $this->personnel = Personnel::query()->with(['position', 'structure'])->findOrFail($personnelModel);
    }

    public function saveTemplate(): void
    {
        abort_unless($this->canManageTemplates(), 403);

        $validated = $this->validate([
            'templateForm.title' => 'required|string|max:255',
            'templateForm.document_type' => 'required|in:policy,internal_regulation,job_instruction,security_rule,welcome_pack,other',
            'templateForm.version' => 'required|string|max:32',
            'templateForm.effective_from' => 'nullable|date',
            'templateForm.effective_to' => 'nullable|date|after_or_equal:templateForm.effective_from',
            'templateForm.is_required' => 'boolean',
            'templateForm.requires_acknowledgement' => 'boolean',
            'templateUpload' => 'required|file|max:10240',
        ], attributes: [
            'templateForm.title' => __('personnel::my_hr.onboarding_admin.fields.template_title'),
            'templateForm.document_type' => __('personnel::my_hr.onboarding_admin.fields.document_type'),
            'templateForm.version' => __('personnel::my_hr.onboarding_admin.fields.version'),
            'templateForm.effective_from' => __('personnel::my_hr.onboarding_admin.fields.effective_from'),
            'templateForm.effective_to' => __('personnel::my_hr.onboarding_admin.fields.effective_to'),
            'templateUpload' => __('personnel::my_hr.onboarding_admin.fields.file'),
        ]);

        $template = app(OnboardingAssignmentManagerService::class)->createTemplate(
            data_get($validated, 'templateForm', []),
            $this->templateUpload,
            auth()->user()
        );

        $this->assignmentForm['template_id'] = $template->id;
        $this->templateUpload = null;
        $this->templateForm = [
            'title' => '',
            'document_type' => 'policy',
            'version' => '1.0',
            'effective_from' => null,
            'effective_to' => null,
            'is_required' => true,
            'requires_acknowledgement' => true,
        ];

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.onboarding_admin.messages.template_saved'));
    }

    public function assignTemplate(): void
    {
        abort_unless($this->canAssignDocuments(), 403);

        $validated = $this->validate([
            'assignmentForm.template_id' => 'required|exists:onboarding_document_templates,id',
            'assignmentForm.due_at' => 'nullable|date',
        ], attributes: [
            'assignmentForm.template_id' => __('personnel::my_hr.onboarding_admin.fields.template'),
            'assignmentForm.due_at' => __('personnel::my_hr.onboarding_admin.fields.due_at'),
        ]);

        app(OnboardingAssignmentManagerService::class)->assign(
            $this->personnel,
            (int) data_get($validated, 'assignmentForm.template_id'),
            data_get($validated, 'assignmentForm.due_at'),
            auth()->user()
        );

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.onboarding_admin.messages.assignment_saved'));
    }

    public function waiveAssignment(int $assignmentId): void
    {
        abort_unless($this->canAssignDocuments(), 403);

        app(OnboardingAssignmentManagerService::class)->waive($this->assignment($assignmentId));

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.onboarding_admin.messages.assignment_waived'));
    }

    public function removeAssignment(int $assignmentId): void
    {
        abort_unless($this->canAssignDocuments(), 403);

        app(OnboardingAssignmentManagerService::class)->remove($this->assignment($assignmentId));

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.onboarding_admin.messages.assignment_removed'));
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrOnboardingReadService::class)->build($this->personnel);
    }

    #[Computed]
    public function reportPayload(): array
    {
        return app(OnboardingAdminReportReadService::class)->build($this->personnel);
    }

    public function templateOptions(): array
    {
        return OnboardingDocumentTemplate::query()
            ->when($this->searchTemplate !== '', function ($query): void {
                $query->where('title', 'like', '%'.$this->searchTemplate.'%');
            })
            ->latest('created_at')
            ->get(['id', 'title', 'version'])
            ->map(fn (OnboardingDocumentTemplate $template): array => [
                'id' => $template->id,
                'label' => trim($template->title.' · v'.$template->version),
            ])
            ->all();
    }

    public function templates(): array
    {
        return OnboardingDocumentTemplate::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (OnboardingDocumentTemplate $template): array => [
                'id' => $template->id,
                'title' => $template->title,
                'document_type_label' => __('personnel::my_hr.onboarding.document_types.'.$template->document_type),
                'version' => $template->version,
                'file_url' => $template->fileUrl(),
                'required' => (bool) $template->is_required,
                'requires_acknowledgement' => (bool) $template->requires_acknowledgement,
            ])
            ->all();
    }

    public function canManageTemplates(): bool
    {
        return auth()->user()?->can('manage-onboarding-document-templates') ?? false;
    }

    public function canAssignDocuments(): bool
    {
        return auth()->user()?->can('assign-onboarding-documents') ?? false;
    }

    protected function assignment(int $assignmentId): OnboardingDocumentAssignment
    {
        return OnboardingDocumentAssignment::query()
            ->where('personnel_id', $this->personnel->id)
            ->with(['template', 'receipt'])
            ->findOrFail($assignmentId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.onboarding-assignment-manager');
    }
}
