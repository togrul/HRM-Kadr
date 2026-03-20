<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\Personnel;
use App\Models\PersonnelProjectRecord;
use App\Modules\Personnel\Exports\ProfessionalPortfolioProjectsExport;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistryFingerprintService;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistrySyncService;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioWorkflowPolicyService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\HandlesPortfolioAttachments;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use App\Models\Structure;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

class ProjectsManager extends Component
{
    use HandlesPortfolioAttachments;
    use WithFileUploads;

    public int $personnelId;
    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $selectedId = null;
    public $evidenceUpload = null;
    public array $form = [
        'project_name' => '',
        'project_code' => '',
        'project_type' => 'internal',
        'role_title' => '',
        'responsibility_summary' => '',
        'team_name' => '',
        'sponsor_unit_id' => null,
        'partner_organizations' => '',
        'start_date' => '',
        'end_date' => '',
        'is_ongoing' => false,
        'outcome_summary' => '',
        'impact_summary' => '',
        'reference_url' => '',
        'notes' => '',
    ];

    public function mount(int $personnelId): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::projectViewPermissions()), 403);
        $this->personnelId = $personnelId;
        $this->statusFilter = 'all';
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.professional-portfolio-tab');
    }

    public function updatedFormIsOngoing($value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $this->form['end_date'] = '';
        }
    }

    public function openCreate(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();
        $record = $this->baseQuery()->findOrFail($id);
        $this->editingId = $record->id;
        $this->showForm = true;
        $this->form = [
            'project_name' => $record->project_name,
            'project_code' => $record->project_code ?? '',
            'project_type' => $record->project_type,
            'role_title' => $record->role_title,
            'responsibility_summary' => $record->responsibility_summary,
            'team_name' => $record->team_name ?? '',
            'sponsor_unit_id' => $record->sponsor_unit_id,
            'partner_organizations' => $record->partner_organizations ?? '',
            'start_date' => optional($record->start_date)->format('Y-m-d') ?? '',
            'end_date' => optional($record->end_date)->format('Y-m-d') ?? '',
            'is_ongoing' => (bool) $record->is_ongoing,
            'outcome_summary' => $record->outcome_summary ?? '',
            'impact_summary' => $record->impact_summary ?? '',
            'reference_url' => $record->reference_url ?? '',
            'notes' => $record->notes ?? '',
        ];
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function selectRecord(int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $data = $this->validate($this->rules());

        $record = $this->editingId
            ? $this->baseQuery()->with('evidenceAttachment')->findOrFail($this->editingId)
            : new PersonnelProjectRecord(['personnel_id' => $this->personnelId]);

        $evidence = $this->storePortfolioAttachment($this->evidenceUpload, $this->personnelId, 'project-evidence', $record->evidenceAttachment);

        $record->fill([
            ...$data['form'],
            'registry_key' => app(ProfessionalPortfolioRegistryFingerprintService::class)->forProject($data['form']),
            'evidence_attachment_id' => $evidence?->id,
            'entered_by' => auth()->id(),
            'verification_status' => PersonnelProjectRecord::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $record->save();
        app(ProfessionalPortfolioRegistrySyncService::class)->syncProjectRecord($record);

        $this->statusFilter = 'all';
        $this->selectedId = $record->id;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.project_saved'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function verify(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertProjectTransition($record, PersonnelProjectRecord::STATUS_VERIFIED);
        $record->update([
            'verification_status' => PersonnelProjectRecord::STATUS_VERIFIED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_verified'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function reject(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertProjectTransition($record, PersonnelProjectRecord::STATUS_REJECTED);
        $record->update([
            'verification_status' => PersonnelProjectRecord::STATUS_REJECTED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_rejected'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::projectViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioProjectsExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-projects-{$this->personnelId}.xlsx"
        );
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::projectViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioProjectsExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-projects-{$this->personnelId}.csv",
            ExcelWriter::CSV
        );
    }

    public function getRecordsProperty()
    {
        return $this->filteredQuery()
            ->with(['sponsorUnit:id,name,parent_id', 'evidenceAttachment:id,display_name,original_name,file_path,disk', 'verifier:id,name'])
            ->limit(25)
            ->get();
    }

    public function getSelectedRecordProperty(): ?PersonnelProjectRecord
    {
        if (! $this->selectedId) {
            return null;
        }

        return $this->baseQuery()
            ->with(['sponsorUnit:id,name,parent_id', 'evidenceAttachment:id,display_name,original_name,file_path,disk', 'verifier:id,name'])
            ->find($this->selectedId);
    }

    public function getSponsorUnitOptionsProperty()
    {
        return Structure::query()->select(['id', 'name'])->orderBy('name')->limit(100)->get();
    }

    protected function baseQuery()
    {
        return PersonnelProjectRecord::query()->where('personnel_id', $this->personnelId);
    }

    protected function exportRows()
    {
        return $this->filteredQuery()
            ->with([
                'sponsorUnit:id,name,parent_id',
                'evidenceAttachment:id,display_name,original_name,file_path,disk',
                'verifier:id,name',
            ])
            ->get();
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()->select(['id', 'surname', 'name', 'patronymic'])->findOrFail($this->personnelId);
    }

    protected function filteredQuery()
    {
        return $this->baseQuery()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('verification_status', $this->statusFilter))
            ->when(filled($this->search), function ($query) {
                $needle = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($needle) {
                    $nested->where('project_name', 'like', $needle)
                        ->orWhere('project_code', 'like', $needle)
                        ->orWhere('role_title', 'like', $needle);
                });
            })
            ->when(filled($this->dateFrom), fn ($query) => $query->whereDate('start_date', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn ($query) => $query->whereDate('start_date', '<=', $this->dateTo))
            ->latest('start_date');
    }

    protected function rules(): array
    {
        $rules = [
            'form.project_name' => 'required|string|max:255',
            'form.project_code' => 'nullable|string|max:100',
            'form.project_type' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::projectTypes())],
            'form.role_title' => 'required|string|max:255',
            'form.responsibility_summary' => 'required|string|min:10',
            'form.team_name' => 'nullable|string|max:255',
            'form.sponsor_unit_id' => 'nullable|integer|exists:structures,id',
            'form.partner_organizations' => 'nullable|string',
            'form.start_date' => 'required|date',
            'form.end_date' => 'nullable|date|after_or_equal:form.start_date',
            'form.is_ongoing' => 'boolean',
            'form.outcome_summary' => 'nullable|string',
            'form.impact_summary' => 'nullable|string',
            'form.reference_url' => 'nullable|url',
            'form.notes' => 'nullable|string',
            'evidenceUpload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];

        if (filter_var($this->form['is_ongoing'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            unset($rules['form.end_date']);
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'form.project_name' => __('personnel::portfolio.fields.project_name'),
            'form.project_code' => __('personnel::portfolio.fields.project_code'),
            'form.project_type' => __('personnel::portfolio.fields.project_type'),
            'form.role_title' => __('personnel::portfolio.fields.role_title'),
            'form.responsibility_summary' => __('personnel::portfolio.fields.responsibility_summary'),
            'form.team_name' => __('personnel::portfolio.fields.team_name'),
            'form.sponsor_unit_id' => __('personnel::portfolio.fields.sponsor_unit'),
            'form.partner_organizations' => __('personnel::portfolio.fields.partner_organizations'),
            'form.start_date' => __('personnel::portfolio.fields.start_date'),
            'form.end_date' => __('personnel::portfolio.fields.end_date'),
            'form.is_ongoing' => __('personnel::portfolio.fields.is_ongoing'),
            'form.outcome_summary' => __('personnel::portfolio.fields.outcome_summary'),
            'form.impact_summary' => __('personnel::portfolio.fields.impact_summary'),
            'form.reference_url' => __('personnel::portfolio.fields.reference_url'),
            'form.notes' => __('personnel::portfolio.fields.notes'),
            'evidenceUpload' => __('personnel::portfolio.fields.evidence'),
        ];
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->evidenceUpload = null;
        $this->resetValidation();
        $this->form = [
            'project_name' => '',
            'project_code' => '',
            'project_type' => 'internal',
            'role_title' => '',
            'responsibility_summary' => '',
            'team_name' => '',
            'sponsor_unit_id' => null,
            'partner_organizations' => '',
            'start_date' => '',
            'end_date' => '',
            'is_ongoing' => false,
            'outcome_summary' => '',
            'impact_summary' => '',
            'reference_url' => '',
            'notes' => '',
        ];
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-personnel-project-records'), 403);
    }

    protected function authorizeVerify(): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::projectVerifyPermissions()), 403);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.projects-manager');
    }
}
