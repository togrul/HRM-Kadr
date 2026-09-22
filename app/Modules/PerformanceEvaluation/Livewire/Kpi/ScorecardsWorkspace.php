<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * KPI scorecards of a cycle: HR opens them in bulk; the owner, their manager and HR
 * work a card through actuals, approvals and the status workflow. The service decides
 * who may do what.
 */
class ScorecardsWorkspace extends Component
{
    use AuthorizesRequests;
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

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->value('id');
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

        return app(ScorecardService::class)->visibleQuery(auth()->user())
            ->with([
                'personnel:id,surname,name,patronymic',
                'position:id,name',
                'manager:id,surname,name',
                'items.kpi:id,code,name,type,direction,unit,evidence_required',
                'items.actuals.enteredBy:id,name',
                'items.goal:id,title',
                'checkins.author:id,name',
                'calibrations.adjustedBy:id,name',
                'events.user:id,name',
            ])
            ->find($this->openCardId);
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
