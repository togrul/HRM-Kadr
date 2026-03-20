<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\PersonnelMediaMention;
use App\Models\Personnel;
use App\Modules\Personnel\Exports\ProfessionalPortfolioMediaExport;
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

class MediaManager extends Component
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
    public $archiveUpload = null;
    public $screenshotUpload = null;
    public array $form = [
        'headline' => '',
        'publisher_name' => '',
        'publisher_type' => 'website',
        'mention_type' => 'news_mention',
        'published_at' => '',
        'url' => '',
        'summary' => '',
        'sentiment' => 'neutral',
        'language' => 'az',
        'visibility' => 'internal',
        'notes' => '',
    ];

    public function mount(int $personnelId): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::mediaViewPermissions()), 403);
        $this->personnelId = $personnelId;
        $this->statusFilter = 'all';
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.professional-portfolio-tab');
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
            'headline' => $record->headline,
            'publisher_name' => $record->publisher_name,
            'publisher_type' => $record->publisher_type,
            'mention_type' => $record->mention_type,
            'published_at' => optional($record->published_at)->format('Y-m-d\TH:i') ?? '',
            'url' => $record->url ?? '',
            'summary' => $record->summary,
            'sentiment' => $record->sentiment,
            'language' => $record->language ?? 'az',
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
        $data = $this->validate($this->rules());

        $record = $this->editingId
            ? $this->baseQuery()->with(['archiveAttachment', 'screenshotAttachment'])->findOrFail($this->editingId)
            : new PersonnelMediaMention(['personnel_id' => $this->personnelId]);

        $archive = $this->storePortfolioAttachment($this->archiveUpload, $this->personnelId, 'media-archive', $record->archiveAttachment);
        abort_if(! $archive, 422, __('personnel::portfolio.fields.archive'));
        $screenshot = $this->storePortfolioAttachment($this->screenshotUpload, $this->personnelId, 'media-screenshot', $record->screenshotAttachment);

        $record->fill([
            ...$data['form'],
            'publisher_registry_key' => app(ProfessionalPortfolioRegistryFingerprintService::class)->forMediaPublisher($data['form']),
            'archive_attachment_id' => $archive->id,
            'screenshot_attachment_id' => $screenshot?->id,
            'entered_by' => auth()->id(),
            'verification_status' => PersonnelMediaMention::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $record->save();
        app(ProfessionalPortfolioRegistrySyncService::class)->syncMediaRecord($record);

        $this->statusFilter = 'all';
        $this->selectedId = $record->id;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.media_saved'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function verify(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertMediaTransition($record, PersonnelMediaMention::STATUS_VERIFIED);
        $record->update([
            'verification_status' => PersonnelMediaMention::STATUS_VERIFIED,
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
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertMediaTransition($record, PersonnelMediaMention::STATUS_REJECTED);
        $record->update([
            'verification_status' => PersonnelMediaMention::STATUS_REJECTED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);
        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_rejected'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function markBrokenLink(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertMediaTransition($record, PersonnelMediaMention::STATUS_BROKEN_LINK);
        $record->update([
            'verification_status' => PersonnelMediaMention::STATUS_BROKEN_LINK,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_marked_broken'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function markArchivedOnly(int $id): void
    {
        $this->authorizeVerify();
        $record = $this->baseQuery()->findOrFail($id);
        app(ProfessionalPortfolioWorkflowPolicyService::class)->assertMediaTransition($record, PersonnelMediaMention::STATUS_ARCHIVED_ONLY);
        $record->update([
            'verification_status' => PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::portfolio.messages.record_marked_archived_only'));
        $this->dispatch('portfolioRecordSaved');
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::mediaViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioMediaExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-media-{$this->personnelId}.xlsx"
        );
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::mediaViewPermissions()), 403);

        return Excel::download(
            new ProfessionalPortfolioMediaExport($this->personnel(), $this->exportRows()),
            "professional-portfolio-media-{$this->personnelId}.csv",
            ExcelWriter::CSV
        );
    }

    public function getRecordsProperty()
    {
        return $this->filteredQuery()
            ->with(['archiveAttachment:id,display_name,original_name,file_path,disk', 'screenshotAttachment:id,display_name,original_name,file_path,disk', 'verifier:id,name'])
            ->limit(25)
            ->get();
    }

    public function getSelectedRecordProperty(): ?PersonnelMediaMention
    {
        if (! $this->selectedId) {
            return null;
        }

        return $this->baseQuery()
            ->with(['archiveAttachment:id,display_name,original_name,file_path,disk', 'screenshotAttachment:id,display_name,original_name,file_path,disk', 'verifier:id,name'])
            ->find($this->selectedId);
    }

    protected function baseQuery()
    {
        return PersonnelMediaMention::query()->where('personnel_id', $this->personnelId);
    }

    protected function exportRows()
    {
        return $this->filteredQuery()
            ->with([
                'archiveAttachment:id,display_name,original_name,file_path,disk',
                'screenshotAttachment:id,display_name,original_name,file_path,disk',
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
            ->when(! auth()->user()?->can('view-restricted-media-records'), fn ($query) => $query->where('visibility', '!=', 'restricted'))
            ->when(filled($this->search), function ($query) {
                $needle = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($needle) {
                    $nested->where('headline', 'like', $needle)
                        ->orWhere('publisher_name', 'like', $needle)
                        ->orWhere('summary', 'like', $needle);
                });
            })
            ->when(filled($this->dateFrom), fn ($query) => $query->whereDate('published_at', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn ($query) => $query->whereDate('published_at', '<=', $this->dateTo))
            ->latest('published_at');
    }

    protected function rules(): array
    {
        return [
            'form.headline' => 'required|string|max:255',
            'form.publisher_name' => 'required|string|max:255',
            'form.publisher_type' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaPublisherTypes())],
            'form.mention_type' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaMentionTypes())],
            'form.published_at' => 'required|date',
            'form.url' => 'nullable|url',
            'form.summary' => 'required|string|min:10',
            'form.sentiment' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaSentiments())],
            'form.language' => 'nullable|string|max:20',
            'form.visibility' => ['required', 'string', Rule::in(\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaVisibilities())],
            'form.notes' => 'nullable|string',
            'archiveUpload' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
            'screenshotUpload' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.headline' => __('personnel::portfolio.fields.headline'),
            'form.publisher_name' => __('personnel::portfolio.fields.publisher_name'),
            'form.publisher_type' => __('personnel::portfolio.fields.publisher_type'),
            'form.mention_type' => __('personnel::portfolio.fields.mention_type'),
            'form.published_at' => __('personnel::portfolio.fields.published_at'),
            'form.url' => __('personnel::portfolio.fields.url'),
            'form.summary' => __('personnel::portfolio.fields.summary'),
            'form.sentiment' => __('personnel::portfolio.fields.sentiment'),
            'form.language' => __('personnel::portfolio.fields.language'),
            'form.visibility' => __('personnel::portfolio.fields.visibility'),
            'form.notes' => __('personnel::portfolio.fields.notes'),
            'archiveUpload' => __('personnel::portfolio.fields.archive'),
            'screenshotUpload' => __('personnel::portfolio.fields.screenshot'),
        ];
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->archiveUpload = null;
        $this->screenshotUpload = null;
        $this->resetValidation();
        $this->form = [
            'headline' => '',
            'publisher_name' => '',
            'publisher_type' => 'website',
            'mention_type' => 'news_mention',
            'published_at' => '',
            'url' => '',
            'summary' => '',
            'sentiment' => 'neutral',
            'language' => 'az',
            'visibility' => 'internal',
            'notes' => '',
        ];
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-personnel-media-records'), 403);
    }

    protected function authorizeVerify(): void
    {
        abort_unless(auth()->user()?->canAny(ProfessionalPortfolioPermissionMatrix::mediaVerifyPermissions()), 403);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.media-manager');
    }
}
