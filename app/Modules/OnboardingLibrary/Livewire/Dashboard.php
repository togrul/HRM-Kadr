<?php

namespace App\Modules\OnboardingLibrary\Livewire;

use App\Models\OnboardingDocumentTemplate;
use App\Modules\OnboardingLibrary\Application\Services\OnboardingLibraryReadService;
use App\Modules\Personnel\Contracts\OnboardingAssignmentManager;
use App\Support\Library\LibraryExportAction;
use App\Support\Livewire\AbstractLibraryDashboard;
use Livewire\Attributes\Computed;

class Dashboard extends AbstractLibraryDashboard
{
    public string $searchTemplate = '';

    public ?int $versionSourceTemplateId = null;

    public $templateUpload = null;

    public array $templateForm = [
        'title' => '',
        'document_type' => 'policy',
        'version' => '1.0',
        'effective_from' => null,
        'effective_to' => null,
        'is_required' => true,
        'requires_acknowledgement' => true,
        'is_active' => true,
        'auto_assign_new_hires' => false,
    ];

    public array $assignmentForm = [
        'template_id' => null,
        'due_at' => null,
        'include_recent_hires' => false,
        'recent_hire_days' => 30,
    ];

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
            'templateForm.is_active' => 'boolean',
            'templateForm.auto_assign_new_hires' => 'boolean',
            'templateUpload' => 'required|file|max:10240',
        ], attributes: [
            'templateForm.title' => __('onboarding-library::dashboard.fields.template_title'),
            'templateForm.document_type' => __('onboarding-library::dashboard.fields.document_type'),
            'templateForm.version' => __('onboarding-library::dashboard.fields.version'),
            'templateForm.effective_from' => __('onboarding-library::dashboard.fields.effective_from'),
            'templateForm.effective_to' => __('onboarding-library::dashboard.fields.effective_to'),
            'templateForm.is_required' => __('onboarding-library::dashboard.fields.is_required'),
            'templateForm.requires_acknowledgement' => __('onboarding-library::dashboard.fields.requires_acknowledgement'),
            'templateForm.is_active' => __('onboarding-library::dashboard.fields.is_active'),
            'templateForm.auto_assign_new_hires' => __('onboarding-library::dashboard.fields.auto_assign_new_hires'),
            'templateUpload' => __('onboarding-library::dashboard.fields.file'),
        ]);

        $sourceTemplate = $this->versionSourceTemplateId
            ? OnboardingDocumentTemplate::query()->find($this->versionSourceTemplateId)
            : null;

        $template = app(OnboardingAssignmentManager::class)->createTemplate(
            data_get($validated, 'templateForm', []),
            $this->templateUpload,
            auth()->user(),
            $sourceTemplate
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
            'is_active' => true,
            'auto_assign_new_hires' => false,
        ];
        $this->versionSourceTemplateId = null;

        $this->dispatch('notify', type: 'success', message: __('onboarding-library::dashboard.messages.template_saved'));
    }

    public function assignSelected(): void
    {
        abort_unless($this->canAssignDocuments(), 403);

        $validated = $this->validate([
            'assignmentForm.template_id' => 'required|exists:onboarding_document_templates,id',
            'assignmentForm.due_at' => 'nullable|date',
            'selectedPersonnelIds' => 'array',
            'selectedPersonnelIds.*' => 'integer|exists:personnels,id',
            'selectedStructureIds' => 'array',
            'selectedStructureIds.*' => 'integer|exists:structures,id',
            'selectedPositionIds' => 'array',
            'selectedPositionIds.*' => 'integer|exists:positions,id',
            'assignmentForm.include_recent_hires' => 'boolean',
            'assignmentForm.recent_hire_days' => 'nullable|integer|min:1|max:365',
        ], attributes: [
            'assignmentForm.template_id' => __('onboarding-library::dashboard.fields.template'),
            'assignmentForm.due_at' => __('onboarding-library::dashboard.fields.due_at'),
            'assignmentForm.include_recent_hires' => __('onboarding-library::dashboard.fields.include_recent_hires'),
            'assignmentForm.recent_hire_days' => __('onboarding-library::dashboard.fields.recent_hire_days'),
            'selectedPersonnelIds' => __('onboarding-library::dashboard.fields.target_people'),
            'selectedPersonnelIds.*' => __('onboarding-library::dashboard.fields.target_person'),
            'selectedStructureIds' => __('onboarding-library::dashboard.fields.target_structures'),
            'selectedStructureIds.*' => __('onboarding-library::dashboard.fields.target_structure'),
            'selectedPositionIds' => __('onboarding-library::dashboard.fields.target_positions'),
            'selectedPositionIds.*' => __('onboarding-library::dashboard.fields.target_position'),
        ]);

        if (! $this->ensureTargetsSelected(
            (bool) data_get($validated, 'assignmentForm.include_recent_hires', false),
            'selectedPersonnelIds',
            __('onboarding-library::dashboard.messages.target_required')
        )) {
            return;
        }

        $count = app(OnboardingAssignmentManager::class)->assignByTargets(
            data_get($validated, 'selectedPersonnelIds', []),
            data_get($validated, 'selectedStructureIds', []),
            data_get($validated, 'selectedPositionIds', []),
            (bool) data_get($validated, 'assignmentForm.include_recent_hires', false),
            data_get($validated, 'assignmentForm.recent_hire_days'),
            (int) data_get($validated, 'assignmentForm.template_id'),
            data_get($validated, 'assignmentForm.due_at'),
            auth()->user()
        );

        $this->dispatch('notify', type: 'success', message: __('onboarding-library::dashboard.messages.assignment_saved', ['count' => $count]));
    }

    public function toggleTemplateActive(int $templateId): void
    {
        abort_unless($this->canManageTemplates(), 403);

        $template = OnboardingDocumentTemplate::query()->findOrFail($templateId);
        app(OnboardingAssignmentManager::class)->toggleTemplateActive($template);

        unset($this->payload);
        $this->dispatch('notify', type: 'success', message: __('onboarding-library::dashboard.messages.template_state_updated'));
    }

    public function toggleTemplateArchived(int $templateId): void
    {
        abort_unless($this->canManageTemplates(), 403);

        $template = OnboardingDocumentTemplate::query()->findOrFail($templateId);
        app(OnboardingAssignmentManager::class)->setTemplateArchived($template, $template->archived_at === null, auth()->user());

        unset($this->payload);
        $this->dispatch('notify', type: 'success', message: __('onboarding-library::dashboard.messages.template_archive_updated'));
    }

    public function prepareNextTemplateVersion(int $templateId): void
    {
        abort_unless($this->canManageTemplates(), 403);

        $template = OnboardingDocumentTemplate::query()->findOrFail($templateId);
        $this->versionSourceTemplateId = $template->id;
        $this->templateForm = [
            'title' => $template->title,
            'document_type' => $template->document_type,
            'version' => $this->incrementVersion($template->version),
            'effective_from' => optional($template->effective_from)?->format('Y-m-d'),
            'effective_to' => optional($template->effective_to)?->format('Y-m-d'),
            'is_required' => (bool) $template->is_required,
            'requires_acknowledgement' => (bool) $template->requires_acknowledgement,
            'is_active' => true,
            'auto_assign_new_hires' => (bool) $template->auto_assign_new_hires,
        ];

        $this->dispatch('notify', type: 'info', message: __('onboarding-library::dashboard.messages.version_prefilled'));
    }

    public function exportTemplates()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(OnboardingLibraryReadService::class)->exportTemplates($this->searchTemplate),
            [
                ['key' => 'title', 'label' => __('onboarding-library::dashboard.fields.template_title')],
                ['key' => 'type', 'label' => __('onboarding-library::dashboard.fields.document_type')],
                ['key' => 'version', 'label' => __('onboarding-library::dashboard.fields.version')],
                ['key' => 'is_active', 'label' => __('onboarding-library::dashboard.fields.is_active')],
                ['key' => 'auto_assign_new_hires', 'label' => __('onboarding-library::dashboard.fields.auto_assign_new_hires')],
                ['key' => 'required', 'label' => __('onboarding-library::dashboard.fields.is_required')],
                ['key' => 'assignments_count', 'label' => __('onboarding-library::dashboard.summary.active_assignments')],
                ['key' => 'acknowledged_assignments_count', 'label' => __('onboarding-library::dashboard.summary.acknowledged_assignments')],
            ],
            'onboarding-templates.xlsx'
        );
    }

    public function exportAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(OnboardingLibraryReadService::class)->exportAssignments(),
            [
                ['key' => 'template', 'label' => __('onboarding-library::dashboard.fields.template')],
                ['key' => 'version', 'label' => __('onboarding-library::dashboard.fields.version')],
                ['key' => 'personnel', 'label' => __('onboarding-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'assigned_at', 'label' => __('personnel::my_hr.onboarding.labels.assigned_at')],
                ['key' => 'status', 'label' => __('personnel::my_hr.requests.fields.status')],
                ['key' => 'acknowledged_at', 'label' => __('personnel::my_hr.onboarding.labels.acknowledged_at')],
            ],
            'onboarding-assignments.xlsx'
        );
    }

    public function exportOverdueAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(OnboardingLibraryReadService::class)->exportOverdueAssignments(),
            [
                ['key' => 'template', 'label' => __('onboarding-library::dashboard.fields.template')],
                ['key' => 'personnel', 'label' => __('onboarding-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'due_at', 'label' => __('onboarding-library::dashboard.fields.due_at')],
                ['key' => 'status', 'label' => __('personnel::my_hr.requests.fields.status')],
            ],
            'onboarding-overdue-assignments.xlsx'
        );
    }

    public function exportAcknowledgedAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(OnboardingLibraryReadService::class)->exportAcknowledgedAssignments(),
            [
                ['key' => 'template', 'label' => __('onboarding-library::dashboard.fields.template')],
                ['key' => 'personnel', 'label' => __('onboarding-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'assigned_at', 'label' => __('personnel::my_hr.onboarding.labels.assigned_at')],
                ['key' => 'acknowledged_at', 'label' => __('personnel::my_hr.onboarding.labels.acknowledged_at')],
            ],
            'onboarding-acknowledged-assignments.xlsx'
        );
    }

    public function exportVersionHistory()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(OnboardingLibraryReadService::class)->exportVersionHistory($this->searchTemplate),
            [
                ['key' => 'title', 'label' => __('onboarding-library::dashboard.fields.template_title')],
                ['key' => 'version', 'label' => __('onboarding-library::dashboard.fields.version')],
                ['key' => 'family_key', 'label' => __('onboarding-library::dashboard.reports.version_family')],
                ['key' => 'previous_version_id', 'label' => __('onboarding-library::dashboard.reports.previous_version')],
                ['key' => 'archived', 'label' => __('onboarding-library::dashboard.fields.archived')],
                ['key' => 'effective_from', 'label' => __('onboarding-library::dashboard.fields.effective_from')],
                ['key' => 'effective_to', 'label' => __('onboarding-library::dashboard.fields.effective_to')],
            ],
            'onboarding-version-history.xlsx'
        );
    }

    #[Computed]
    public function exportActions(): array
    {
        return [
            LibraryExportAction::make('exportTemplates', 'export_templates')->toArray(),
            LibraryExportAction::make('exportAssignments', 'export_assignments')->toArray(),
            LibraryExportAction::make('exportOverdueAssignments', 'export_overdue_assignments')->toArray(),
            LibraryExportAction::make('exportAcknowledgedAssignments', 'export_acknowledged_assignments')->toArray(),
            LibraryExportAction::make('exportVersionHistory', 'export_version_history')->toArray(),
        ];
    }

    #[Computed]
    public function summaryPayload(): array
    {
        return app(OnboardingLibraryReadService::class)->buildSummary();
    }

    #[Computed]
    public function generalPayload(): array
    {
        return app(OnboardingLibraryReadService::class)->buildGeneral(
            $this->searchPersonnel,
            $this->searchStructure,
            $this->searchPosition,
            'onboardingRecentAssignmentsPage'
        );
    }

    #[Computed]
    public function libraryPayload(): array
    {
        return app(OnboardingLibraryReadService::class)->buildLibrary($this->searchTemplate);
    }

    #[Computed]
    public function reportsPayload(): array
    {
        return app(OnboardingLibraryReadService::class)->buildReports();
    }

    #[Computed]
    public function payload(): array
    {
        return app(OnboardingLibraryReadService::class)->build($this->searchTemplate, $this->searchPersonnel, $this->searchStructure, $this->searchPosition);
    }

    public function canView(): bool
    {
        return auth()->user()?->can('view-onboarding-library') ?? false;
    }

    public function canManageTemplates(): bool
    {
        return auth()->user()?->can('manage-onboarding-document-templates') ?? false;
    }

    public function canAssignDocuments(): bool
    {
        return auth()->user()?->can('assign-onboarding-documents') ?? false;
    }

    public function render()
    {
        return view('onboarding-library::livewire.onboarding-library.dashboard');
    }
}
