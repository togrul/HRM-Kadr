<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceScorecard;
use App\Models\Structure;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiAnalyticsService;
use App\Services\UserPersonnelLinkResolver;
use App\Support\Livewire\DownloadsReportsTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * KPI analytics (spec §9): each person sees their own section — "My KPIs" for an
 * employee with a card, the team panel for a manager, and the cycle views for HR.
 *
 * @property-read Collection<int, PerformanceCycle> $cycles
 * @property-read bool $isHr
 * @property-read bool $isManager
 * @property-read array<string, mixed>|null $hr
 */
class AnalyticsWorkspace extends Component
{
    use AuthorizesRequests;
    use DownloadsReportsTable;

    /** HR tables that can be downloaded as Excel. */
    private const EXPORTS = ['overdue', 'strictness', 'flight', 'red_twice', 'bonus_units', 'bonus_positions'];

    public ?int $cycleId = null;

    public ?int $structureId = null;

    /** Who is looking, resolved once — a person without a staff link stays null. */
    #[Locked]
    public ?int $viewerPersonnelId = null;

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()
            ->orderByRaw("case status when 'active' then 0 when 'closed' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->value('id');
        $this->viewerPersonnelId = app(UserPersonnelLinkResolver::class)->resolve(auth()->user());
    }

    #[Computed]
    public function cycles(): Collection
    {
        return PerformanceCycle::query()->orderByDesc('period_start')->get(['id', 'name', 'period_start', 'period_end']);
    }

    #[Computed]
    public function isHr(): bool
    {
        return (bool) auth()->user()?->can('manage-performance-evaluation');
    }

    #[Computed]
    public function isManager(): bool
    {
        return $this->viewerPersonnelId !== null && PerformanceScorecard::query()->where('manager_personnel_id', $this->viewerPersonnelId)->exists();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function unitOptions(): array
    {
        return $this->isHr ? Structure::query()->orderBy('name')->pluck('name', 'id')->all() : [];
    }

    #[Computed]
    public function mine(): SupportCollection
    {
        return $this->viewerPersonnelId && $this->cycleId ? $this->analytics()->myCards($this->viewerPersonnelId, $this->cycleId) : collect();
    }

    #[Computed]
    public function myTrend(): SupportCollection
    {
        return $this->viewerPersonnelId ? $this->analytics()->personalTrend($this->viewerPersonnelId) : collect();
    }

    /**
     * @return array{cards: SupportCollection, kpis: SupportCollection}|null
     */
    #[Computed]
    public function team(): ?array
    {
        return $this->isManager && $this->cycleId ? $this->analytics()->teamMatrix($this->viewerPersonnelId, $this->cycleId) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function hr(): ?array
    {
        if (! $this->isHr || ! $this->cycleId) {
            return null;
        }

        $cycle = $this->cycles->firstWhere('id', $this->cycleId);

        return $cycle ? $this->analytics()->hrOverview($cycle, $this->structureId) : null;
    }

    public function updated(string $property): void
    {
        unset($this->mine, $this->team, $this->hr);
    }

    public function export(string $report): BinaryFileResponse
    {
        $this->authorize('manage-performance-evaluation');
        abort_unless(in_array($report, self::EXPORTS, true), 404);

        $hr = $this->hr;
        [$rows, $keys] = match ($report) {
            'overdue' => [$hr['overdue'], ['manager', 'overdue', 'cards']],
            'strictness' => [$hr['strictness'], ['manager', 'people', 'average', 'delta', 'spread']],
            'flight' => [$hr['risks']['flight'], ['personnel', 'position', 'score', 'salary_gap']],
            'red_twice' => [$hr['risks']['red_twice'], ['personnel', 'position', 'score']],
            'bonus_units' => [$hr['bonus']['units'], ['name', 'people', 'total', 'average']],
            default => [$hr['bonus']['positions'], ['name', 'people', 'total', 'average']],
        };

        $columns = array_map(fn (string $key): array => ['key' => $key, 'label' => __('performance_evaluation::kpi.analytics.columns.'.$key)], $keys);

        /** @var SupportCollection<int, mixed> $rows */
        return $this->downloadReportTable(collect($rows)->all(), $columns, 'kpi-'.$report.'-'.$this->cycleId.'.xlsx');
    }

    public function render(): View
    {
        return view('performance-evaluation::livewire.performance-evaluation.kpi.analytics-workspace');
    }

    private function analytics(): KpiAnalyticsService
    {
        return app(KpiAnalyticsService::class);
    }
}
