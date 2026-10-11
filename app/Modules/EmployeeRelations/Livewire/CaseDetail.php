<?php

namespace App\Modules\EmployeeRelations\Livewire;

use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\AppealService;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Application\Services\DisciplinaryDeadline;
use App\Modules\EmployeeRelations\Application\Services\EvidenceService;
use App\Modules\EmployeeRelations\Application\Services\PersonnelLookup;
use App\Modules\EmployeeRelations\Application\Services\ReporterIdentityService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseAppeal;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvidence;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * İşin kartı: faktlar, iştirakçılar, sübutlar, araşdırıcılar, mərhələ keçidləri,
 * qərar (hüquqi müddət xəbərdarlığı ilə), apellyasiyaya baxış və tarixçə.
 * Hər əməliyyat serverdə CaseAccess / xidmətlərin öz yoxlamasından keçir.
 *
 * @property-read RelationCase $record
 * @property-read array<string, bool> $abilities
 * @property-read Collection<int, \App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvent> $timeline
 * @property-read Collection<int, RelationCaseEvidence> $evidence
 * @property-read Collection<int, \App\Modules\EmployeeRelations\Domain\Models\RelationCaseWitness> $witnesses
 * @property-read Collection<int, User> $investigators
 * @property-read Collection<int, RelationCaseAppeal> $appeals
 * @property-read list<array{id:int, name:string}> $eligibleInvestigators
 * @property-read array<string, mixed>|null $deadline
 */
class CaseDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $caseId;

    public string $transitionTarget = '';

    public string $transitionComment = '';

    /** @var array<string, mixed> */
    public array $facts = [];

    public string $witnessSearch = '';

    public string $witnessExternalName = '';

    public string $witnessNote = '';

    public string $evidenceKind = RelationCaseEvidence::KIND_FILE;

    public string $evidenceTitle = '';

    public string $evidenceBody = '';

    public ?string $evidenceProvidedBy = '';

    /** @var TemporaryUploadedFile|null */
    public $evidenceFile = null;

    public ?string $investigatorId = '';

    public string $decisionOutcome = '';

    public string $decisionSummary = '';

    public string $appealOutcome = '';

    public string $appealComment = '';

    public string $appealNewDecision = '';

    public string $revealReason = '';

    /** Açılmış kimlik yalnız həmin cavabda göstərilir — snapshot-a yazılmır. */
    protected ?string $revealedName = null;

    public function mount(int $case, CaseAccess $access): void
    {
        $record = RelationCase::query()->findOrFail($case);
        abort_unless($access->canView($this->user(), $record), 404);

        $this->caseId = $record->id;
        $this->fillFacts($record);
    }

    #[Computed]
    public function record(): RelationCase
    {
        $record = RelationCase::query()
            ->with(['type', 'subject:id,surname,name,patronymic,tabel_no,structure_id', 'structure:id,name', 'creator:id,name'])
            ->findOrFail($this->caseId);

        abort_unless(app(CaseAccess::class)->canView($this->user(), $record), 404);

        return $record;
    }

    /**
     * @return array<string, bool>
     */
    #[Computed]
    public function abilities(): array
    {
        $access = app(CaseAccess::class);
        $user = $this->user();
        $record = $this->record;

        return [
            'manage' => $access->canManage($user, $record),
            'work' => $access->canWork($user, $record),
            'reveal' => app(ReporterIdentityService::class)->canReveal($user, $record),
        ];
    }

    /**
     * @return list<CaseStatus>
     */
    public function transitionTargets(): array
    {
        $record = $this->record;
        $abilities = $this->abilities;

        return array_values(array_filter(
            $record->status->manualTargets(),
            fn (CaseStatus $target): bool => $target === CaseStatus::Investigating ? $abilities['work'] : $abilities['manage'],
        ));
    }

    /**
     * @return Collection<int, \App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvent>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->record->events()->with('actor:id,name')->latest('id')->limit(200)->get();
    }

    /**
     * @return Collection<int, RelationCaseEvidence>
     */
    #[Computed]
    public function evidence(): Collection
    {
        return $this->record->evidence()->with(['providedBy:id,surname,name,patronymic', 'uploader:id,name'])->latest('id')->get();
    }

    /**
     * @return Collection<int, \App\Modules\EmployeeRelations\Domain\Models\RelationCaseWitness>
     */
    #[Computed]
    public function witnesses(): Collection
    {
        return $this->record->witnesses()->with('personnel:id,surname,name,patronymic')->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function investigators(): Collection
    {
        return $this->record->investigators()->get(['users.id', 'users.name']);
    }

    /**
     * @return Collection<int, RelationCaseAppeal>
     */
    #[Computed]
    public function appeals(): Collection
    {
        return $this->record->appeals()->with('reviewer:id,name')->latest('id')->get();
    }

    /**
     * @return list<array{id:int, name:string}>
     */
    #[Computed]
    public function eligibleInvestigators(): array
    {
        if (! $this->abilities['manage']) {
            return [];
        }

        $assigned = $this->investigators->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return array_values(array_filter(
            app(CaseService::class)->eligibleInvestigators($this->record),
            fn (array $user): bool => ! in_array($user['id'], $assigned, true),
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function deadline(): ?array
    {
        if (! $this->abilities['manage'] || ! in_array($this->record->status, [CaseStatus::Investigating, CaseStatus::AppealReview], true)) {
            return null;
        }

        return app(DisciplinaryDeadline::class)->evaluate($this->record);
    }

    /**
     * Seçilə bilən qərarlar: müddət keçibsə və ya şablon yoxdursa tənbehlər bloklanır.
     *
     * @return array<string, array{label: string, disabled: bool}>
     */
    public function decisionOptions(): array
    {
        $blocked = (bool) ($this->deadline['blocked'] ?? true);
        $noTemplate = app(DecisionService::class)->disciplinaryTemplates() === [];
        $options = [];

        foreach (DecisionOutcome::cases() as $outcome) {
            $options[$outcome->value] = [
                'label' => $outcome->label(),
                'disabled' => $outcome->isSanction() && ($blocked || $noTemplate),
            ];
        }

        return $options;
    }

    public function changeStatus(CaseWorkflow $workflow): void
    {
        $target = CaseStatus::tryFrom($this->transitionTarget);

        if ($target === null) {
            $this->addError('transitionTarget', __('employee_relations::cases.messages.transition_not_allowed'));

            return;
        }

        $workflow->transition($this->record, $target, $this->user(), $this->transitionComment);

        $this->reset('transitionTarget', 'transitionComment');
        $this->refreshCase(__('employee_relations::cases.messages.status_changed', ['status' => $target->label()]));
    }

    public function saveFacts(CaseService $cases): void
    {
        $validated = $this->validate([
            'facts.case_type_id' => ['required', 'integer'],
            'facts.occurred_at' => ['nullable', 'date', 'before_or_equal:today'],
            'facts.discovered_at' => ['nullable', 'date', 'before_or_equal:today'],
            'facts.discovered_by_audit' => ['boolean'],
            'facts.subject_personnel_id' => ['nullable', 'integer'],
        ], attributes: [
            'facts.occurred_at' => __('employee_relations::cases.fields.occurred_at'),
            'facts.discovered_at' => __('employee_relations::cases.fields.discovered_at'),
        ])['facts'];

        $cases->updateFacts($this->record, $this->user(), $validated);
        $this->refreshCase(__('employee_relations::cases.messages.facts_saved'));
    }

    /**
     * @return list<array{id:int, name:string, meta:string}>
     */
    public function personnelResults(string $term): array
    {
        return app(PersonnelLookup::class)->search($this->user(), $term);
    }

    public function pickSubject(int $personnelId): void
    {
        $this->facts['subject_label'] = app(PersonnelLookup::class)->visibleName($this->user(), $personnelId);
        $this->facts['subject_personnel_id'] = $personnelId;
        $this->facts['subject_search'] = '';
    }

    public function addWitness(?int $personnelId = null): void
    {
        $cases = app(CaseService::class);
        $this->validate([
            'witnessExternalName' => ['nullable', 'string', 'max:150'],
            'witnessNote' => ['nullable', 'string', 'max:500'],
        ]);

        $cases->addWitness($this->record, $this->user(), $personnelId, $personnelId === null ? $this->witnessExternalName : null, $this->witnessNote);

        $this->reset('witnessSearch', 'witnessExternalName', 'witnessNote');
        $this->refreshCase(__('employee_relations::cases.messages.witness_added'));
    }

    public function removeWitness(int $witnessId): void
    {
        $cases = app(CaseService::class);
        $cases->removeWitness($this->record, $this->user(), $witnessId);
        $this->refreshCase(__('employee_relations::cases.messages.witness_removed'));
    }

    public function addEvidence(EvidenceService $evidence): void
    {
        $this->validate([
            'evidenceKind' => ['required', 'in:'.implode(',', RelationCaseEvidence::KINDS)],
            'evidenceTitle' => ['required', 'string', 'max:250'],
            'evidenceBody' => [$this->evidenceKind === RelationCaseEvidence::KIND_FILE ? 'nullable' : 'required', 'string', 'max:20000'],
            'evidenceFile' => EvidenceService::fileRules($this->evidenceKind === RelationCaseEvidence::KIND_FILE),
            'evidenceProvidedBy' => ['nullable', 'integer'],
        ], attributes: [
            'evidenceTitle' => __('employee_relations::cases.fields.evidence_title'),
            'evidenceBody' => __('employee_relations::cases.fields.evidence_body'),
            'evidenceFile' => __('employee_relations::cases.fields.evidence_file'),
        ]);

        $evidence->add(
            $this->record,
            $this->user(),
            $this->evidenceKind,
            $this->evidenceTitle,
            $this->evidenceBody,
            $this->evidenceFile instanceof TemporaryUploadedFile ? $this->evidenceFile : null,
            filled($this->evidenceProvidedBy) ? (int) $this->evidenceProvidedBy : null,
        );

        $this->reset('evidenceTitle', 'evidenceBody', 'evidenceFile', 'evidenceProvidedBy');
        $this->evidenceKind = RelationCaseEvidence::KIND_FILE;
        $this->refreshCase(__('employee_relations::cases.messages.evidence_added'));
    }

    public function assignInvestigator(CaseService $cases): void
    {
        $this->validate(['investigatorId' => ['required', 'integer']], attributes: [
            'investigatorId' => __('employee_relations::cases.fields.investigator'),
        ]);

        $cases->assignInvestigator($this->record, $this->user(), (int) $this->investigatorId);
        $this->investigatorId = '';
        $this->refreshCase(__('employee_relations::cases.messages.investigator_assigned'));
    }

    public function removeInvestigator(int $userId): void
    {
        $cases = app(CaseService::class);
        $cases->removeInvestigator($this->record, $this->user(), $userId);
        $this->refreshCase(__('employee_relations::cases.messages.investigator_removed'));
    }

    public function decide(DecisionService $decisions): void
    {
        $this->validate([
            'decisionOutcome' => ['required', 'in:'.implode(',', DecisionOutcome::values())],
            'decisionSummary' => ['required', 'string', 'max:5000'],
        ], attributes: [
            'decisionOutcome' => __('employee_relations::cases.fields.decision_outcome'),
            'decisionSummary' => __('employee_relations::cases.fields.decision_summary'),
        ]);

        $decisions->decide($this->record, $this->user(), DecisionOutcome::from($this->decisionOutcome), $this->decisionSummary);

        $this->reset('decisionOutcome', 'decisionSummary');
        $this->refreshCase(__('employee_relations::cases.messages.decided'));
    }

    public function startAppealReview(AppealService $appeals): void
    {
        $appeals->startReview($this->record, $this->user(), $this->appealComment ?: null);
        $this->appealComment = '';
        $this->refreshCase(__('employee_relations::cases.messages.appeal_review_started'));
    }

    public function resolveAppeal(AppealService $appeals): void
    {
        $this->validate([
            'appealOutcome' => ['required', 'in:'.implode(',', RelationCaseAppeal::OUTCOMES)],
            'appealComment' => ['required', 'string', 'max:5000'],
            'appealNewDecision' => ['nullable', 'in:'.implode(',', DecisionOutcome::values())],
        ], attributes: [
            'appealOutcome' => __('employee_relations::cases.fields.appeal_outcome'),
            'appealComment' => __('employee_relations::cases.fields.comment'),
        ]);

        $result = $appeals->resolve(
            $this->record,
            $this->user(),
            $this->appealOutcome,
            $this->appealComment,
            DecisionOutcome::tryFrom($this->appealNewDecision),
        );

        $this->reset('appealOutcome', 'appealComment', 'appealNewDecision');
        $this->refreshCase(__('employee_relations::cases.messages.appeal_resolved'));

        if ($result['order_follow_up']) {
            $this->dispatch('notify', type: 'warning', message: __('employee_relations::cases.messages.order_follow_up'));
        }
    }

    public function revealReporter(ReporterIdentityService $identity): void
    {
        $this->validate(['revealReason' => ['required', 'string', 'min:10', 'max:500']], attributes: [
            'revealReason' => __('employee_relations::cases.fields.reveal_reason'),
        ]);

        $this->revealedName = $identity->reveal($this->record, $this->user(), $this->revealReason);
        $this->revealReason = '';
        unset($this->timeline);
    }

    public function render(): View
    {
        $record = $this->record;
        $identity = app(ReporterIdentityService::class);

        return view('employee-relations::livewire.case-detail', [
            'case' => $record,
            'complainantName' => $identity->displayName($record),
            'revealedName' => $this->revealedName,
            'typeOptions' => RelationCaseType::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $record->case_type_id))
                ->orderBy('sort_order')
                ->pluck('name', 'id')
                ->all(),
            'subjectOptions' => array_filter([
                (int) $record->subject_personnel_id => $record->subject_personnel_id !== null ? $record->subject->fullname : null,
            ] + $this->witnesses
                ->filter(fn ($witness): bool => $witness->personnel_id !== null)
                ->mapWithKeys(fn ($witness): array => [(int) $witness->personnel_id => $witness->displayName()])
                ->all()),
        ]);
    }

    private function refreshCase(string $message): void
    {
        unset($this->record, $this->abilities, $this->timeline, $this->evidence, $this->witnesses, $this->investigators, $this->appeals, $this->eligibleInvestigators, $this->deadline);
        $this->fillFacts($this->record);
        $this->resetValidation();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    private function fillFacts(RelationCase $record): void
    {
        $this->facts = [
            'case_type_id' => (string) $record->case_type_id,
            'occurred_at' => $record->occurred_at?->toDateString() ?? '',
            'discovered_at' => $record->discovered_at?->toDateString() ?? '',
            'discovered_by_audit' => $record->discovered_by_audit,
            'subject_personnel_id' => $record->subject_personnel_id,
            'subject_label' => (string) $record->subject?->fullname,
            'subject_search' => '',
        ];
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
