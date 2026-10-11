<?php

namespace App\Modules\EmployeeRelations\Livewire;

use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\AppealService;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Application\Services\EvidenceService;
use App\Modules\EmployeeRelations\Application\Services\MyCasesReadService;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Şəxsi kabinetin «Şikayət və müraciətlər» bölməsi: işçi müraciət göndərir (istəsə
 * anonim), öz müraciətlərinin mərhələsini və barəsində bildirilmiş qərarları görür,
 * müddət ərzində apellyasiya verir.
 *
 * İşçi həmişə serverdə, daxil olmuş istifadəçinin açıq bağından müəyyən olunur;
 * kənardan ötürülən `personnelId` yalnız yoxlanılır.
 *
 * @property-read bool $canSubmit
 * @property-read array<int, string> $typeOptions
 * @property-read list<array<string, mixed>> $reported
 * @property-read list<array<string, mixed>> $aboutMe
 */
class MyCases extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $personnelId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $attachment = null;

    public ?int $appealCaseId = null;

    public string $appealReason = '';

    public function mount(UserPersonnelLinkResolver $links, ?int $personnelId = null): void
    {
        $own = $links->resolve($this->user());

        abort_if($personnelId !== null && $personnelId !== $own, 403);
        abort_if($own === null, 404);

        $this->personnelId = $own;
        $this->form = $this->blankForm();
    }

    public function hydrate(): void
    {
        abort_unless(app(UserPersonnelLinkResolver::class)->resolve($this->user()) === $this->personnelId, 403);
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return $this->user()->can(CaseAccess::SUBMIT_OWN);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return RelationCaseType::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function reported(): array
    {
        return app(MyCasesReadService::class)->reported($this->user(), $this->personnelId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function aboutMe(): array
    {
        return app(MyCasesReadService::class)->aboutMe($this->user(), $this->personnelId);
    }

    public function openForm(): void
    {
        abort_unless($this->canSubmit, 403);
        $this->form = $this->blankForm();
        $this->attachment = null;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function submit(CaseService $cases, EvidenceService $evidence): void
    {
        abort_unless($this->canSubmit, 403);

        $validated = $this->validate([
            'form.case_type_id' => ['required', 'integer'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['required', 'string', 'min:20', 'max:10000'],
            'form.subject_description' => ['nullable', 'string', 'max:255'],
            'form.occurred_at' => ['nullable', 'date', 'before_or_equal:today'],
            'form.is_anonymous' => ['boolean'],
            'attachment' => EvidenceService::fileRules(false),
        ], attributes: [
            'form.case_type_id' => __('employee_relations::cases.fields.type'),
            'form.title' => __('employee_relations::cases.fields.title'),
            'form.description' => __('employee_relations::cases.fields.description'),
            'form.subject_description' => __('employee_relations::cases.fields.subject_description'),
            'form.occurred_at' => __('employee_relations::cases.fields.occurred_at'),
            'attachment' => __('employee_relations::cases.fields.evidence_file'),
        ]);

        $user = $this->user();
        $case = $cases->openBySelfService($user, (int) $this->personnelId, $validated['form']);

        if ($this->attachment instanceof TemporaryUploadedFile) {
            $evidence->addFromReporter($case, $user, $this->attachment, __('employee_relations::cases.my.attachment_title'));
        }

        $this->showForm = false;
        $this->attachment = null;
        $this->form = $this->blankForm();
        unset($this->reported);
        $this->dispatch('notify', type: 'success', message: __('employee_relations::cases.messages.self_service_submitted', ['number' => $case->label()]));
    }

    public function startAppeal(int $caseId): void
    {
        $case = $this->ownCase($caseId);
        abort_unless(app(AppealService::class)->canAppeal($case, $this->user(), $this->personnelId), 403);

        $this->appealCaseId = $case->id;
        $this->appealReason = '';
        $this->resetValidation();
    }

    public function cancelAppeal(): void
    {
        $this->appealCaseId = null;
        $this->appealReason = '';
    }

    public function submitAppeal(AppealService $appeals): void
    {
        $this->validate(['appealReason' => ['required', 'string', 'min:10', 'max:5000']], attributes: [
            'appealReason' => __('employee_relations::cases.fields.appeal_reason'),
        ]);

        $case = $this->ownCase((int) $this->appealCaseId);
        $appeals->submit($case, $this->user(), $this->personnelId, $this->appealReason);

        $this->cancelAppeal();
        unset($this->reported, $this->aboutMe);
        $this->dispatch('notify', type: 'success', message: __('employee_relations::cases.messages.appeal_submitted'));
    }

    public function render(): View
    {
        return view('employee-relations::livewire.my-cases');
    }

    /** Yalnız işçinin öz müraciəti və ya ona bildirilmiş qərar — başqa iş 404. */
    private function ownCase(int $caseId): RelationCase
    {
        $user = $this->user();

        return RelationCase::query()
            ->whereKey($caseId)
            ->where(fn ($query) => $query
                ->where('reporter_user_id', $user->getKey())
                ->orWhere(fn ($subject) => $subject
                    ->where('subject_personnel_id', $this->personnelId)
                    ->whereNotNull('decision_notified_at')))
            ->first() ?? abort(404);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankForm(): array
    {
        return [
            'case_type_id' => (string) (array_key_first($this->typeOptions) ?? ''),
            'title' => '',
            'description' => '',
            'subject_description' => '',
            'occurred_at' => '',
            'is_anonymous' => false,
        ];
    }
}
