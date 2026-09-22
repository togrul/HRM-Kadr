<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceNotificationSetting;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardChangeRequest;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use App\Livewire\Traits\SideModalAction;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\InternalKpiMetrics;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiActualsImportService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\TargetChangeService;
use App\Support\Livewire\DownloadsReportsTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * KPI scorecards of a cycle: HR opens them in bulk; the owner, their manager and HR
 * work a card through actuals, approvals and the status workflow. The service decides
 * who may do what.
 */
class ScorecardsWorkspace extends Component
{
    use AuthorizesRequests;
    use DownloadsReportsTable;
    use SideModalAction;
    use WithFileUploads;

    public ?int $cycleId = null;

    public string $statusFilter = '';

    public ?int $openCardId = null;

    public ?int $actualItemId = null;

    public $actualValue = null;

    public string $actualNote = '';

    public $evidence = null;

    /** @var array<int, mixed> item id → draft target */
    public array $targets = [];

    public string $transitionReason = '';

    public $calibrationDelta = null;

    public string $calibrationReason = '';

    public ?string $checkinDate = null;

    public string $checkinProgress = '';

    public string $checkinRisks = '';

    public ?int $changeItemId = null;

    public $changeTarget = null;

    public string $changeReason = '';

    public string $decisionNote = '';

    public bool $showNotificationSettings = false;

    public bool $notifyByEmail = true;

    public bool $notifyDigest = false;

    public string $extraSearch = '';

    public ?int $extraPersonnelId = null;

    public ?int $extraPositionId = null;

    public $extraFte = 0.5;

    public bool $showImport = false;

    public $importFile = null;

    /** @var array<int, string> sheet row → problem */
    public array $importErrors = [];

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->value('id');

        $settings = PerformanceNotificationSetting::query()->where('user_id', auth()->id())->first();
        $this->notifyByEmail = $settings?->email ?? true;
        $this->notifyDigest = $settings?->digest ?? false;
    }

    public function updatedNotifyByEmail(): void
    {
        $this->saveNotificationSettings();
    }

    public function updatedNotifyDigest(): void
    {
        $this->saveNotificationSettings();
    }

    private function saveNotificationSettings(): void
    {
        PerformanceNotificationSetting::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['email' => $this->notifyByEmail, 'digest' => $this->notifyDigest],
        );
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.notification_settings.saved'));
    }

    public function updatedCycleId(): void
    {
        $this->closeCard();
    }

    /**
     * @return Collection<int, PerformanceCycle>
     */
    #[Computed]
    public function cycles(): Collection
    {
        return PerformanceCycle::query()->orderByDesc('period_start')->get(['id', 'name', 'status']);
    }

    /**
     * @return Collection<int, PerformanceScorecard>
     */
    #[Computed]
    public function cards(): Collection
    {
        if (! $this->cycleId) {
            return new Collection;
        }

        return app(ScorecardService::class)->visibleQuery(auth()->user())
            ->where('performance_cycle_id', $this->cycleId)
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->with(['personnel:id,surname,name,patronymic', 'position:id,name', 'manager:id,surname,name'])
            ->orderBy('status')
            ->orderBy('id')
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function card(): ?PerformanceScorecard
    {
        if (! $this->openCardId) {
            return null;
        }

        $card = app(ScorecardService::class)->visibleQuery(auth()->user())
            ->with([
                'personnel:id,surname,name,patronymic',
                'position:id,name',
                'manager:id,surname,name',
                'items.kpi:id,code,name,type,direction,unit,evidence_required,source_metric,integration_error,data_source',
                'items.actuals',
                'items.goal:id,title',
                'items.changeRequests',
                'checkins',
                'calibrations',
                'events',
                'bonus',
            ])
            ->find($this->openCardId);

        return $card ? $this->attachUsers($card) : null;
    }

    /**
     * The card names users in five places (actuals, requests, check-ins, calibrations,
     * history); they are read in one query and handed out, not once per relation.
     */
    private function attachUsers(PerformanceScorecard $card): PerformanceScorecard
    {
        $places = [
            [$card->items->flatMap->actuals, 'entered_by', 'enteredBy'],
            [$card->items->flatMap->changeRequests, 'requested_by', 'requester'],
            [$card->checkins, 'created_by', 'author'],
            [$card->calibrations, 'adjusted_by', 'adjustedBy'],
            [$card->events, 'user_id', 'user'],
        ];

        $ids = collect($places)->flatMap(fn (array $place) => $place[0]->pluck($place[1]))->filter()->unique();
        $users = $ids->isEmpty() ? collect() : User::query()->whereIn('id', $ids)->get(['id', 'name'])->keyBy('id');

        foreach ($places as [$models, $column, $relation]) {
            $models->each(fn ($model) => $model->setRelation($relation, $users->get($model->{$column})));
        }

        return $card;
    }

    #[Computed]
    public function role(): ?string
    {
        return $this->card ? app(ScorecardService::class)->roleFor(auth()->user(), $this->card) : null;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function actions(): array
    {
        return $this->card ? app(ScorecardService::class)->availableActions($this->card, auth()->user()) : [];
    }

    #[Computed]
    public function competencies(): SupportCollection
    {
        return $this->card ? app(ScorecardReviewService::class)->competencies($this->card) : collect();
    }

    /**
     * @return array<int, string> goal id → title, for tying draft KPI items to goals
     */
    #[Computed]
    public function goalOptions(): array
    {
        return $this->card?->status === 'draft'
            ? PerformanceGoal::query()->where('performance_cycle_id', $this->card->performance_cycle_id)->orderBy('title')->pluck('title', 'id')->all()
            : [];
    }

    /**
     * @return array<string, array{count: int, share: float, target: int}>
     */
    #[Computed]
    public function distribution(): array
    {
        return $this->cycleId && auth()->user()->can('manage-performance-evaluation')
            ? app(ScorecardReviewService::class)->distribution($this->cycleId)
            : [];
    }

    #[Computed]
    public function unlinkedItems(): int
    {
        return $this->cycleId && auth()->user()->can('manage-performance-evaluation')
            ? app(ScorecardReviewService::class)->unlinkedItemCount($this->cycleId)
            : 0;
    }

    public function generate(): void
    {
        $this->authorize('manage-performance-evaluation');
        $cycle = PerformanceCycle::query()->findOrFail($this->cycleId);

        $created = app(ScorecardService::class)->generateForCycle($cycle);

        unset($this->cards);
        $this->dispatch('notify', type: $created > 0 ? 'success' : 'info', message: __('performance_evaluation::kpi.messages.cards_generated', ['count' => $created]));
    }

    /**
     * Active people matching the search, for a second-post card.
     *
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function extraPersonnelOptions(): array
    {
        $term = trim($this->extraSearch);

        return Personnel::query()
            ->active()
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('surname', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")->orWhere('tabel_no', 'like', "%{$term}%")))
            ->when($this->extraPersonnelId, fn ($query) => $query->orWhere('id', $this->extraPersonnelId))
            ->orderBy('surname')
            ->limit(30)
            ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
            ->map(fn (Personnel $person): array => ['id' => $person->id, 'label' => trim($person->surname.' '.$person->name.' '.$person->patronymic).' · '.$person->tabel_no])
            ->all();
    }

    /**
     * Positions that have an active KPI template.
     *
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function extraPositionOptions(): array
    {
        return Position::query()
            ->whereIn('id', app(ScorecardService::class)->templateIdsByPosition()->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Position $position): array => ['id' => $position->id, 'label' => $position->name])
            ->all();
    }

    public function openExtra(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->reset(['extraSearch', 'extraPersonnelId', 'extraPositionId']);
        $this->extraFte = 0.5;
        $this->resetValidation();
        $this->openSideMenu('extra-card');
    }

    public function openExtraCard(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->validate([
            'extraPersonnelId' => ['required', 'integer'],
            'extraPositionId' => ['required', 'integer'],
            'extraFte' => ['required', 'numeric', 'min:0.05', 'max:1'],
        ]);

        $personnel = Personnel::query()->findOrFail($this->extraPersonnelId);
        $cycle = PerformanceCycle::query()->findOrFail($this->cycleId);

        if ((int) $this->extraPositionId === (int) $personnel->position_id) {
            $this->addError('extraPositionId', __('performance_evaluation::kpi.extra.errors.main_position'));

            return;
        }

        $taken = PerformanceScorecard::query()
            ->where('performance_cycle_id', $cycle->id)
            ->where('personnel_id', $personnel->id)
            ->where('position_id', $this->extraPositionId)
            ->where('status', '!=', 'closed')
            ->exists();
        $card = $taken ? null : app(ScorecardService::class)->openCardFor($cycle, $personnel, null, (int) $this->extraPositionId, (float) $this->extraFte);

        if ($card === null) {
            $this->addError('extraPositionId', __('performance_evaluation::kpi.extra.errors.'.($taken ? 'exists' : 'no_template')));

            return;
        }

        $this->closeSideMenu();
        unset($this->cards);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.extra.created'));
    }

    public function toggleImport(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->showImport = ! $this->showImport;
        $this->importFile = null;
        $this->importErrors = [];
        $this->resetValidation();
    }

    public function downloadActualsTemplate(): BinaryFileResponse
    {
        $this->authorize('manage-performance-evaluation');
        $service = app(KpiActualsImportService::class);
        $cycle = PerformanceCycle::query()->findOrFail($this->cycleId);

        return $this->downloadReportTable($service->templateRows($cycle), $service->columns(), 'kpi-actuals-'.$cycle->id.'.xlsx');
    }

    public function importActuals(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->validate(['importFile' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls,csv,txt']]);

        $rows = Excel::toArray(new \stdClass, $this->importFile->getRealPath(), null, $this->readerType())[0] ?? [];
        $result = app(KpiActualsImportService::class)->import(PerformanceCycle::query()->findOrFail($this->cycleId), $rows, auth()->user());

        $this->importErrors = $result['errors'];
        if ($result['errors'] !== []) {
            return;
        }

        $this->importFile = null;
        $this->showImport = $result['imported'] === 0;
        unset($this->cards);
        $this->dispatch('notify', type: $result['imported'] > 0 ? 'success' : 'info', message: __('performance_evaluation::kpi.import.done', ['count' => $result['imported']]));
    }

    public function syncMetrics(): void
    {
        $this->authorize('manage-performance-evaluation');
        $count = app(InternalKpiMetrics::class)->sync($this->cycleId);

        unset($this->cards);
        $this->dispatch('notify', type: $count > 0 ? 'success' : 'info', message: __('performance_evaluation::kpi.metrics.synced', ['count' => $count]));
    }

    public function startChange(int $itemId): void
    {
        $this->cancelChange();
        $item = $this->cardItem($itemId);
        $this->changeItemId = $item->id;
        $this->changeTarget = $item->target === null ? null : (float) $item->target;
    }

    public function cancelChange(): void
    {
        $this->changeItemId = null;
        $this->changeTarget = null;
        $this->changeReason = '';
        $this->resetValidation(['changeTarget', 'change', 'reason']);
    }

    public function submitChange(): void
    {
        $this->validate(['changeTarget' => ['required', 'numeric']]);
        app(TargetChangeService::class)->request($this->cardItem($this->changeItemId), (float) $this->changeTarget, $this->changeReason, auth()->user());

        $this->cancelChange();
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.change_requests.sent'));
    }

    public function approveChange(int $requestId): void
    {
        $this->decideChange($requestId, true);
    }

    public function rejectChange(int $requestId): void
    {
        $this->decideChange($requestId, false);
    }

    private function decideChange(int $requestId, bool $approve): void
    {
        $request = PerformanceScorecardChangeRequest::query()
            ->whereIn('performance_scorecard_item_id', $this->requireCard()->items->pluck('id'))
            ->findOrFail($requestId);

        app(TargetChangeService::class)->decide($request, $approve, $this->decisionNote, auth()->user());

        $this->decisionNote = '';
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.change_requests.'.($approve ? 'approved_message' : 'rejected_message')));
    }

    public function openCard(int $id): void
    {
        $this->openCardId = $id;
        $this->cancelActual();
        $this->transitionReason = '';
        $this->checkinDate = today()->toDateString();
        $this->targets = $this->card?->items->mapWithKeys(fn (PerformanceScorecardItem $item): array => [$item->id => $item->target === null ? null : (float) $item->target])->all() ?? [];
        abort_if($this->card === null, 404);
    }

    public function closeCard(): void
    {
        $this->openCardId = null;
        $this->targets = [];
        $this->cancelActual();
        $this->transitionReason = '';
        $this->refreshCard();
    }

    public function moveCard(string $action): void
    {
        app(ScorecardService::class)->transition($this->requireCard(), $action, auth()->user(), $this->transitionReason);
        $this->transitionReason = '';
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.status_changed'));
    }

    public function saveTarget(int $itemId): void
    {
        $this->validate(["targets.$itemId" => ['required', 'numeric']]);

        app(ScorecardService::class)->updateTarget($this->cardItem($itemId), (float) $this->targets[$itemId], auth()->user());
        $this->refreshCard();
    }

    public function startActual(int $itemId): void
    {
        $this->cancelActual();
        $this->actualItemId = $this->cardItem($itemId)->id;
    }

    public function cancelActual(): void
    {
        $this->actualItemId = null;
        $this->actualValue = null;
        $this->actualNote = '';
        $this->evidence = null;
        $this->resetValidation();
    }

    public function saveActual(): void
    {
        $this->validate([
            'actualItemId' => ['required', 'integer'],
            'actualValue' => ['required', 'numeric'],
            'actualNote' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,xls,xlsx,csv,doc,docx'],
        ]);

        app(ScorecardService::class)->recordActual(
            $this->cardItem($this->actualItemId),
            (float) $this->actualValue,
            auth()->user(),
            $this->evidence,
            $this->actualNote ?: null,
        );

        $this->cancelActual();
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.actual_saved'));
    }

    public function approveActual(int $actualId): void
    {
        $actual = PerformanceKpiActual::query()
            ->whereKey($actualId)
            ->whereHas('item', fn ($query) => $query->where('performance_scorecard_id', $this->requireCard()->id))
            ->firstOrFail();

        app(ScorecardService::class)->approveActual($actual, auth()->user());
        $this->refreshCard();
    }

    public function rate(int $itemId, string $evaluator, int $rating): void
    {
        app(ScorecardReviewService::class)->rateCompetency($this->requireCard(), $itemId, $evaluator === 'self' ? 'self' : 'manager', $rating, null, auth()->user());
        $this->refreshCard();
    }

    public function saveCalibration(): void
    {
        $this->validate([
            'calibrationDelta' => ['required', 'numeric'],
            'calibrationReason' => ['required', 'string', 'max:1000'],
        ]);

        app(ScorecardReviewService::class)->calibrate($this->requireCard(), (float) $this->calibrationDelta, $this->calibrationReason, auth()->user());
        $this->calibrationDelta = null;
        $this->calibrationReason = '';
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.calibrated'));
    }

    public function saveCheckin(): void
    {
        $this->validate([
            'checkinDate' => ['required', 'date'],
            'checkinProgress' => ['required', 'string', 'max:2000'],
            'checkinRisks' => ['nullable', 'string', 'max:2000'],
        ]);

        app(ScorecardReviewService::class)->addCheckin($this->requireCard(), Carbon::parse($this->checkinDate), $this->checkinProgress, $this->checkinRisks, auth()->user());
        $this->reset('checkinProgress', 'checkinRisks');
        $this->checkinDate = today()->toDateString();
        $this->refreshCard();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.checkin_saved'));
    }

    public function linkGoal(int $itemId, mixed $goalId): void
    {
        app(ScorecardReviewService::class)->linkGoal($this->cardItem($itemId), filled($goalId) ? (int) $goalId : null, auth()->user());
        $this->refreshCard();
    }

    public function render(): View
    {
        return view('performance-evaluation::livewire.performance-evaluation.kpi.scorecards-workspace');
    }

    /**
     * Livewire keeps uploads under a .tmp name, so the reader is picked from the client
     * extension.
     */
    private function readerType(): string
    {
        return match (strtolower($this->importFile->getClientOriginalExtension())) {
            'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }

    private function requireCard(): PerformanceScorecard
    {
        return $this->card ?? abort(404);
    }

    private function cardItem(?int $itemId): PerformanceScorecardItem
    {
        return $this->requireCard()->items->firstWhere('id', $itemId) ?? abort(404);
    }

    private function refreshCard(): void
    {
        unset($this->card, $this->cards, $this->role, $this->actions, $this->competencies, $this->goalOptions, $this->distribution, $this->unlinkedItems);
    }
}
