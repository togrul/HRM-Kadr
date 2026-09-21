<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
            ])
            ->find($this->openCardId);
    }

    #[Computed]
    public function role(): ?string
    {
        return $this->card ? app(ScorecardService::class)->roleFor(auth()->user(), $this->card) : null;
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
        $this->targets = $this->card?->items->mapWithKeys(fn (PerformanceScorecardItem $item): array => [$item->id => $item->target === null ? null : (float) $item->target])->all() ?? [];
        abort_if($this->card === null, 404);
    }

    public function closeCard(): void
    {
        $this->openCardId = null;
        $this->targets = [];
        $this->cancelActual();
        unset($this->card, $this->role);
    }

    public function moveCard(string $action): void
    {
        app(ScorecardService::class)->transition($this->requireCard(), $action, auth()->user());
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
        unset($this->card, $this->cards, $this->role);
    }
}
