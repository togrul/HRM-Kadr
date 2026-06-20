<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\Country;
use App\Models\Personnel;
use App\Models\PersonnelEventRecord;
use App\Modules\Personnel\Exports\ProfessionalPortfolioEventsExport;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistryFingerprintService;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistrySyncService;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioWorkflowPolicyService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\HandlesPortfolioAttachments;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

class EventsManager extends Component
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

    public $certificateUpload = null;

    public $agendaUpload = null;

    public array $form = [
        'event_type' => 'conference',
        'participation_role' => 'speaker',
        'title' => '',
        'topic' => '',
        'organizer_name' => '',
        'start_date' => '',
        'end_date' => '',
        'location' => '',
        'country_id' => null,
        'attendance_format' => 'offline',
        'strategic_level' => 'development',
        'hr_value_reason' => '',
        'result_summary' => '',
        'impact_summary' => '',
        'source_url' => '',
        'visibility' => 'internal',
        'notes' => '',
    ];

    public function mount(int $personnelId): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::eventViewPermissions()), 403);

        $this->personnelId = $personnelId;
        $this->statusFilter = 'all';
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.professional-portfolio-tab');
    }

    public function updatedFormParticipationRole(string $value): void
    {
        if ($value !== 'participant') {
            $this->form['hr_value_reason'] = '';
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
            'event_type' => $record->event_type,
            'participation_role' => $record->participation_role,
            'title' => $record->title,
            'topic' => $record->topic ?? '',
            'organizer_name' => $record->organizer_name ?? '',
            'start_date' => optional($record->start_date)->format('Y-m-d') ?? '',
            'end_date' => optional($record->end_date)->format('Y-m-d') ?? '',
            'location' => $record->location ?? '',
            'country_id' => $record->country_id,
            'attendance_format' => $record->attendance_format,
            'strategic_level' => $record->strategic_level,
            'hr_value_reason' => $record->hr_value_reason ?? '',
            'result_summary' => $record->result_summary ?? '',
            'impact_summary' => $record->impact_summary ?? '',
            'source_url' => $record->source_url ?? '',
            'visibility' => $record->visibility,
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
        $data = $this->validate($this->rules(), [], $this->validationAttributes());

        $record = $this->editingId
            ? $this->baseQuery()->with(['certificateAttachment', 'agendaAttachment'])->findOrFail($this->editingId)
            : new PersonnelEventRecord(['personnel_id' => $this->personnelId]);

        $certificate = $this->storePortfolioAttachment($this->certificateUpload, $this->personnelId, 'event-certificate', $record->certificateAttachment);
        $agenda = $this->storePortfolioAttachment($this->agendaUpload, $this->personnelId, 'event-agenda', $record->agendaAttachment);

        $record->fill([
            ...$data['form'],
            'registry_key' => app(ProfessionalPortfolioRegistryFingerprintService::class)->forEvent($data['form']),
            'certificate_attachment_id' => $certificate?->id,
            'agenda_attachment_id' => $agenda?->id,
            'entered_by' => auth()->id(),
            'verification_status' => PersonnelEventRecord::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $record->save();
        app(ProfessionalPortfolioRegistrySyncService::class)->syncEventRecord($record);

        $this->statusFilter = 'all';
        $this->selectedId = $record->id;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.event_saved'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function verify(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertEventTransition($record, PersonnelEventRecord::STATUS_VERIFIED);
        $record->update([
            'verification_status' => PersonnelEventRecord::STATUS_VERIFIED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_verified'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::eventViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioEventsExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-events-{$this->personnelId}.xlsx"
        );
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::eventViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioEventsExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-events-{$this->personnelId}.csv",
            ExcelWriter::CSV
        );
    }

    public function reject(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertEventTransition($record, PersonnelEventRecord::STATUS_REJECTED);
        $record->update([
            'verification_status' => PersonnelEventRecord::STATUS_REJECTED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_rejected'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function getRecordsProperty()
    {
        return $this->filteredQuery()
            ->with([
                'certificateAttachment:id,display_name,original_name,file_path,disk',
                'agendaAttachment:id,display_name,original_name,file_path,disk',
                'verifier:id,name',
                'country.currentCountryTranslations:country_id,title',
            ])
            ->limit(25)
            ->get();
    }

    public function getSelectedRecordProperty(): ?PersonnelEventRecord
    {
        if (! $this->selectedId) {
            return null;
        }

        return $this->baseQuery()
            ->with([
                'certificateAttachment:id,display_name,original_name,file_path,disk',
                'agendaAttachment:id,display_name,original_name,file_path,disk',
                'verifier:id,name',
                'country.currentCountryTranslations:country_id,title',
            ])
            ->find($this->selectedId);
    }

    public function getCountryOptionsProperty()
    {
        return Country::query()
            ->select(['countries.id', 'translations.title'])
            ->join('country_translations as translations', function ($join) {
                $join->on('translations.country_id', '=', 'countries.id')
                    ->where('translations.locale', '=', config('app.locale'));
            })
            ->orderBy('translations.title')
            ->limit(250)
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'title' => (string) $country->getAttribute('title'),
            ]);
    }

    protected function filteredQuery()
    {
        return $this->baseQuery()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('verification_status', $this->statusFilter))
            ->when(filled($this->search), function ($query) {
                $needle = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($needle) {
                    $nested->where('title', 'like', $needle)
                        ->orWhere('topic', 'like', $needle)
                        ->orWhere('organizer_name', 'like', $needle);
                });
            })
            ->when(filled($this->dateFrom) && filled($this->dateTo), function ($query) {
                $query->whereDate('start_date', '<=', $this->dateTo)
                    ->whereRaw('DATE(COALESCE(end_date, start_date)) >= ?', [$this->dateFrom]);
            })
            ->latest('start_date');
    }

    protected function baseQuery()
    {
        return PersonnelEventRecord::query()->where('personnel_id', $this->personnelId);
    }

    protected function exportRows()
    {
        return $this->filteredQuery()
            ->with([
                'country.currentCountryTranslations:country_id,title',
            ])
            ->get();
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()->select(['id', 'surname', 'name', 'patronymic'])->findOrFail($this->personnelId);
    }

    protected function rules(): array
    {
        $rules = [
            'form.event_type' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::eventTypes())],
            'form.participation_role' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::participationRoles())],
            'form.title' => 'required|string|max:255',
            'form.topic' => 'nullable|string|max:255',
            'form.organizer_name' => 'nullable|string|max:255',
            'form.start_date' => 'required|date',
            'form.end_date' => 'nullable|date|after_or_equal:form.start_date',
            'form.location' => 'nullable|string|max:255',
            'form.country_id' => 'nullable|integer|exists:countries,id',
            'form.attendance_format' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::attendanceFormats())],
            'form.strategic_level' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::strategicLevels())],
            'form.result_summary' => 'nullable|string',
            'form.impact_summary' => 'nullable|string',
            'form.source_url' => 'nullable|url',
            'form.visibility' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::eventVisibilities())],
            'form.notes' => 'nullable|string',
            'certificateUpload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx',
            'agendaUpload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];

        if (($this->form['participation_role'] ?? null) === 'participant') {
            $rules['form.hr_value_reason'] = 'required|string|min:10';
        } else {
            $rules['form.hr_value_reason'] = 'nullable|string';
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'form.event_type' => __('personnel::portfolio.fields.event_type'),
            'form.participation_role' => __('personnel::portfolio.fields.participation_role'),
            'form.title' => __('personnel::portfolio.fields.title'),
            'form.topic' => __('personnel::portfolio.fields.topic'),
            'form.organizer_name' => __('personnel::portfolio.fields.organizer_name'),
            'form.start_date' => __('personnel::portfolio.fields.start_date'),
            'form.end_date' => __('personnel::portfolio.fields.end_date'),
            'form.location' => __('personnel::portfolio.fields.location'),
            'form.country_id' => __('personnel::portfolio.fields.country'),
            'form.attendance_format' => __('personnel::portfolio.fields.attendance_format'),
            'form.strategic_level' => __('personnel::portfolio.fields.strategic_level'),
            'form.hr_value_reason' => __('personnel::portfolio.fields.hr_value_reason'),
            'form.result_summary' => __('personnel::portfolio.fields.result_summary'),
            'form.impact_summary' => __('personnel::portfolio.fields.impact_summary'),
            'form.source_url' => __('personnel::portfolio.fields.source_url'),
            'form.visibility' => __('personnel::portfolio.fields.visibility'),
            'form.notes' => __('personnel::portfolio.fields.notes'),
            'certificateUpload' => __('personnel::portfolio.fields.certificate'),
            'agendaUpload' => __('personnel::portfolio.fields.agenda'),
        ];
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->certificateUpload = null;
        $this->agendaUpload = null;
        $this->resetValidation();
        $this->form = [
            'event_type' => 'conference',
            'participation_role' => 'speaker',
            'title' => '',
            'topic' => '',
            'organizer_name' => '',
            'start_date' => '',
            'end_date' => '',
            'location' => '',
            'country_id' => null,
            'attendance_format' => 'offline',
            'strategic_level' => 'development',
            'hr_value_reason' => '',
            'result_summary' => '',
            'impact_summary' => '',
            'source_url' => '',
            'visibility' => 'internal',
            'notes' => '',
        ];
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-personnel-event-records'), 403);
    }

    protected function authorizeVerify(): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::eventVerifyPermissions()), 403);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.events-manager');
    }
}
