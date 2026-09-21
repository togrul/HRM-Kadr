<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceKpiTemplate;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Individual KPI scorecards (spec §5, phase-1 workflow): built per cycle from each
 * person's position template, moved through draft → active → manager_review → closed,
 * fed with manual actuals and re-scored on every approved actual. Closing freezes the
 * card into a snapshot.
 *
 * Roles on a card: `hr` (manage permission), `manager` (the person the card names as
 * manager, resolved from the org hierarchy) and `employee` (the card's owner).
 */
class ScorecardService
{
    public function __construct(
        private readonly KpiScoringEngine $engine,
        private readonly ApprovalRouteResolver $routes,
        private readonly UserPersonnelLinkResolver $links,
    ) {}

    /**
     * Opens a card for every active person whose position has a template and who has
     * no card in the cycle yet. Returns the number of cards created.
     */
    public function generateForCycle(PerformanceCycle $cycle): int
    {
        $templateIdsByPosition = DB::table('performance_kpi_template_positions')
            ->join('performance_kpi_templates', 'performance_kpi_templates.id', '=', 'performance_kpi_template_positions.performance_kpi_template_id')
            ->where('performance_kpi_templates.status', 'active')
            ->whereNull('performance_kpi_templates.deleted_at')
            ->pluck('performance_kpi_template_positions.performance_kpi_template_id', 'performance_kpi_template_positions.position_id');

        if ($templateIdsByPosition->isEmpty()) {
            return 0;
        }

        $templates = PerformanceKpiTemplate::query()
            ->whereIn('id', $templateIdsByPosition->unique()->values())
            ->with('items')
            ->get()
            ->keyBy('id');

        $versionIdsByKpi = DB::table('performance_kpi_versions')
            ->join('performance_kpis', fn ($join) => $join
                ->on('performance_kpis.id', '=', 'performance_kpi_versions.performance_kpi_id')
                ->on('performance_kpis.current_version', '=', 'performance_kpi_versions.version'))
            ->pluck('performance_kpi_versions.id', 'performance_kpi_versions.performance_kpi_id');

        // ponytail: synchronous, one manager lookup per person; move to a queued job when cycles reach thousands of people.
        return Personnel::query()
            ->active()
            ->whereIn('position_id', $templateIdsByPosition->keys())
            ->whereNotIn('id', PerformanceScorecard::query()->where('performance_cycle_id', $cycle->id)->select('personnel_id'))
            ->get()
            ->each(fn (Personnel $personnel) => $this->openCard($cycle, $personnel, $templates->get($templateIdsByPosition->get($personnel->position_id)), $versionIdsByKpi))
            ->count();
    }

    public function roleFor(User $user, PerformanceScorecard $card): ?string
    {
        if ($user->can('manage-performance-evaluation')) {
            return 'hr';
        }

        $personnelId = $this->links->resolve($user);

        return match (true) {
            $personnelId === null => null,
            $personnelId === (int) $card->manager_personnel_id => 'manager',
            $personnelId === (int) $card->personnel_id => 'employee',
            default => null,
        };
    }

    /**
     * Cards the user may see: all for HR, otherwise their own and the ones they manage.
     */
    public function visibleQuery(User $user): Builder
    {
        $query = PerformanceScorecard::query();

        if ($user->can('manage-performance-evaluation')) {
            return $query;
        }

        $personnelId = $this->links->resolve($user);
        if ($personnelId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (Builder $inner) => $inner
            ->where('personnel_id', $personnelId)
            ->orWhere('manager_personnel_id', $personnelId));
    }

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function transition(PerformanceScorecard $card, string $action, User $user): void
    {
        $rule = PerformanceScorecard::TRANSITIONS[$action] ?? null;
        $allowedRoles = in_array($action, ['return', 'close'], true) ? ['hr'] : ['hr', 'manager'];

        $this->authorizeRole($user, $card, $allowedRoles);

        if ($rule === null || $card->status !== $rule['from']) {
            throw ValidationException::withMessages(['scorecard' => __('performance_evaluation::kpi.errors.invalid_transition')]);
        }

        DB::transaction(function () use ($card, $rule, $action): void {
            if ($action === 'close') {
                $this->recalculate($card);
                $card->refresh()->load('items');
                $card->snapshot = [
                    'closed_at' => now()->toIso8601String(),
                    'kpi_weight_share' => (float) $card->kpi_weight_share,
                    'competency_weight_share' => (float) $card->competency_weight_share,
                    'items' => $card->items->map(fn (PerformanceScorecardItem $item): array => $item->only([
                        'performance_kpi_id', 'performance_kpi_version_id', 'weight', 'target', 'range_min', 'range_max',
                        'threshold', 'stretch', 'cap', 'actual', 'achievement', 'score',
                    ]))->all(),
                ];
                $card->locked_at = now();
            }

            $card->status = $rule['to'];
            $card->save();
        });
    }

    /**
     * Targets move only while the card is a draft, and only on items the template left
     * editable (HR may edit any item).
     *
     * @throws AuthorizationException|ValidationException
     */
    public function updateTarget(PerformanceScorecardItem $item, float $target, User $user): void
    {
        $card = $item->scorecard;
        $role = $this->authorizeRole($user, $card, ['hr', 'manager']);

        if ($card->status !== 'draft' || ($role !== 'hr' && ! $item->target_editable)) {
            throw ValidationException::withMessages(['target' => __('performance_evaluation::kpi.errors.scorecard_locked')]);
        }

        $item->update(['target' => $target]);
    }

    /**
     * A manual actual. HR and the manager's entries count at once; the employee's wait
     * for the manager's approval. KPIs that require evidence refuse a value without a file.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function recordActual(PerformanceScorecardItem $item, float $value, User $user, ?UploadedFile $evidence = null, ?string $note = null): PerformanceKpiActual
    {
        $card = $item->scorecard;
        $role = $this->authorizeRole($user, $card, ['hr', 'manager', 'employee']);

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['actual' => __('performance_evaluation::kpi.errors.scorecard_not_active')]);
        }

        if ($item->kpi->evidence_required && $evidence === null) {
            throw ValidationException::withMessages(['evidence' => __('performance_evaluation::kpi.errors.evidence_required')]);
        }

        $approved = $role !== 'employee';

        $actual = $item->actuals()->create([
            'value' => $value,
            'source' => 'manual',
            'evidence_path' => $evidence?->store('performance/kpi-evidence/'.$card->id, 'local'),
            'evidence_name' => $evidence?->getClientOriginalName(),
            'note' => $note,
            'entered_by' => $user->id,
            'approved_by' => $approved ? $user->id : null,
            'approved_at' => $approved ? now() : null,
        ]);

        if ($approved) {
            $this->recalculate($card);
        }

        return $actual;
    }

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function approveActual(PerformanceKpiActual $actual, User $user): void
    {
        $card = $actual->item->scorecard;
        $this->authorizeRole($user, $card, ['hr', 'manager']);

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['actual' => __('performance_evaluation::kpi.errors.scorecard_not_active')]);
        }

        $actual->update(['approved_by' => $user->id, 'approved_at' => now()]);
        $this->recalculate($card);
    }

    /**
     * Re-scores every item from its approved actuals (aggregated per the KPI version the
     * item was built from), then the card's KPI, final score and rating.
     */
    public function recalculate(PerformanceScorecard $card): void
    {
        // ponytail: inline on each actual; queue it if a card ever takes long enough to notice.
        $card->load(['items.kpi', 'items.kpiVersion', 'items.actuals']);

        $scored = $card->items->map(function (PerformanceScorecardItem $item): array {
            $definition = $item->kpiVersion?->snapshot ?? $item->kpi->only(['type', 'direction', 'aggregation', 'qualitative_scale']);
            $actual = $this->aggregate($item->actuals->whereNotNull('approved_at'), (string) ($definition['aggregation'] ?? 'last'));

            $achievement = $actual === null ? null : $this->engine->achievement([
                'type' => $definition['type'],
                'direction' => $definition['direction'],
                'target' => $item->target,
                'range_min' => $item->range_min,
                'range_max' => $item->range_max,
                'scale' => $definition['qualitative_scale'] ?? null,
            ], $actual);

            $score = $achievement === null ? null : $this->engine->itemScore(
                $achievement,
                $item->threshold === null ? null : (float) $item->threshold,
                $item->stretch === null ? null : (float) $item->stretch,
                $item->cap === null ? null : (float) $item->cap,
            );

            $item->update(['actual' => $actual, 'achievement' => $achievement, 'score' => $score]);

            return ['weight' => $item->weight, 'score' => $score];
        });

        $kpiScore = $this->engine->kpiScore($scored);
        $finalScore = $this->engine->finalScore(
            $kpiScore,
            $card->competency_score === null ? null : (float) $card->competency_score,
            (float) $card->kpi_weight_share,
            (float) $card->competency_weight_share,
        );

        $card->update([
            'kpi_score' => $kpiScore,
            'final_score' => $finalScore,
            'rating_category' => $this->engine->ratingCategory($finalScore),
        ]);
    }

    /**
     * @param  Collection<int, int>  $versionIdsByKpi
     */
    private function openCard(PerformanceCycle $cycle, Personnel $personnel, PerformanceKpiTemplate $template, Collection $versionIdsByKpi): PerformanceScorecard
    {
        $cycleStart = Carbon::parse($cycle->period_start)->startOfDay();
        $cycleEnd = Carbon::parse($cycle->period_end)->startOfDay();
        $joined = $personnel->getRawOriginal('join_work_date');
        $validFrom = $joined !== null ? max($cycleStart, Carbon::parse($joined)->startOfDay()) : $cycleStart;
        $cycleDays = $cycleStart->diffInDays($cycleEnd) + 1;

        return DB::transaction(function () use ($cycle, $personnel, $template, $versionIdsByKpi, $validFrom, $cycleEnd, $cycleDays): PerformanceScorecard {
            $card = PerformanceScorecard::query()->create([
                'performance_cycle_id' => $cycle->id,
                'personnel_id' => $personnel->id,
                'position_id' => $personnel->position_id,
                'performance_kpi_template_id' => $template->id,
                'manager_personnel_id' => $this->routes->manager($personnel)['id'] ?? null,
                'status' => 'draft',
                'valid_from' => $validFrom,
                'valid_to' => $cycleEnd,
                'prorata_factor' => round(($validFrom->diffInDays($cycleEnd) + 1) / $cycleDays, 4),
                'kpi_weight_share' => $template->kpi_weight_share,
                'competency_weight_share' => $template->competency_weight_share,
                'created_by' => auth()->id(),
            ]);

            foreach ($template->items as $item) {
                $card->items()->create([
                    ...$item->only(['performance_kpi_id', 'weight', 'target', 'range_min', 'range_max', 'threshold', 'stretch', 'cap', 'target_editable', 'sort_order']),
                    'performance_kpi_version_id' => $versionIdsByKpi->get($item->performance_kpi_id),
                ]);
            }

            return $card;
        });
    }

    /**
     * @param  Collection<int, PerformanceKpiActual>  $actuals  newest first
     */
    private function aggregate(Collection $actuals, string $aggregation): ?float
    {
        if ($actuals->isEmpty()) {
            return null;
        }

        $values = $actuals->map(fn (PerformanceKpiActual $actual): float => (float) $actual->value);

        return round((float) match ($aggregation) {
            'sum' => $values->sum(),
            'avg' => $values->avg(),
            'min' => $values->min(),
            'max' => $values->max(),
            default => $values->first(),
        }, 4);
    }

    /**
     * @param  array<int, string>  $roles
     *
     * @throws AuthorizationException
     */
    private function authorizeRole(User $user, PerformanceScorecard $card, array $roles): string
    {
        $role = $this->roleFor($user, $card);

        if ($role === null || ! in_array($role, $roles, true)) {
            throw new AuthorizationException;
        }

        return $role;
    }
}
