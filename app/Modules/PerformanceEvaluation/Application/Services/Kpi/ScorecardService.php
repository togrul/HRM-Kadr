<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceForm;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceKpiTemplate;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Application\Services\SuccessionService;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Individual KPI scorecards (spec §5): built per cycle from each person's position
 * template, moved through the workflow in PerformanceScorecard::TRANSITIONS, fed with
 * manual actuals and re-scored on every approved actual, competency rating or
 * calibration. Closing freezes the card into a snapshot.
 *
 * Roles on a card: `hr` (manage permission), `manager` (the person the card names as
 * manager, resolved from the org hierarchy) and `employee` (the card's owner). A null
 * user means the system (scheduler) acts.
 */
class ScorecardService
{
    public function __construct(
        private readonly KpiScoringEngine $engine,
        private readonly ApprovalRouteResolver $routes,
        private readonly UserPersonnelLinkResolver $links,
        private readonly SuccessionService $succession,
        private readonly WorkingDays $workingDays,
    ) {}

    /**
     * Opens a card for every active person whose position has a template and who has
     * no card in the cycle yet, then tells each manager how many cards wait for them.
     * Returns the number of cards created.
     */
    public function generateForCycle(PerformanceCycle $cycle): int
    {
        $templateIdsByPosition = $this->templateIdsByPosition();
        if ($templateIdsByPosition->isEmpty()) {
            return 0;
        }

        // ponytail: synchronous, one manager lookup per person; move to a queued job when cycles reach thousands of people.
        $cards = Personnel::query()
            ->active()
            ->whereIn('position_id', $templateIdsByPosition->keys())
            ->whereNotIn('id', PerformanceScorecard::query()->where('performance_cycle_id', $cycle->id)->select('personnel_id'))
            ->get()
            ->map(fn (Personnel $personnel) => $this->openCardFor($cycle, $personnel))
            ->filter();

        app(ScorecardNotifier::class)->cycleOpened($cycle, $cards);

        return $cards->count();
    }

    /**
     * Opens one card for the person's current position — or, for a second job / part-time
     * post, for `$positionId` with its FTE share (spec §5.1) — starting on `$validFrom`
     * (or the later of cycle start and hire date). Null when the position has no active
     * template.
     */
    public function openCardFor(PerformanceCycle $cycle, Personnel $personnel, ?Carbon $validFrom = null, ?int $positionId = null, float $fte = 1.0): ?PerformanceScorecard
    {
        $positionId ??= $personnel->position_id;
        $additional = (int) $positionId !== (int) $personnel->position_id;
        $templateId = $this->templateIdsByPosition()->get($positionId);
        $template = $templateId ? PerformanceKpiTemplate::query()->with('items')->find($templateId) : null;
        if ($template === null) {
            return null;
        }

        $cycleStart = Carbon::parse($cycle->period_start)->startOfDay();
        $cycleEnd = Carbon::parse($cycle->period_end)->startOfDay();
        $joined = $personnel->getRawOriginal('join_work_date');
        $validFrom ??= $joined !== null ? max($cycleStart, Carbon::parse($joined)->startOfDay()) : $cycleStart;

        $versionIdsByKpi = DB::table('performance_kpi_versions')
            ->join('performance_kpis', fn ($join) => $join
                ->on('performance_kpis.id', '=', 'performance_kpi_versions.performance_kpi_id')
                ->on('performance_kpis.current_version', '=', 'performance_kpi_versions.version'))
            ->whereIn('performance_kpis.id', $template->items->pluck('performance_kpi_id'))
            ->pluck('performance_kpi_versions.id', 'performance_kpi_versions.performance_kpi_id');

        $managerPersonnelId = $this->routes->manager($personnel)['id'] ?? null;

        return DB::transaction(function () use ($cycle, $personnel, $template, $validFrom, $cycleStart, $cycleEnd, $versionIdsByKpi, $managerPersonnelId, $positionId, $fte, $additional): PerformanceScorecard {
            $card = PerformanceScorecard::query()->create([
                'performance_cycle_id' => $cycle->id,
                'personnel_id' => $personnel->id,
                'position_id' => $positionId,
                'fte' => max(0.05, min(1.0, $fte)),
                'is_additional' => $additional,
                'performance_kpi_template_id' => $template->id,
                'performance_form_id' => $this->competencyFormFor($cycle, $personnel, $template, $managerPersonnelId)?->id,
                'manager_personnel_id' => $managerPersonnelId,
                'status' => 'draft',
                'valid_from' => $validFrom,
                'valid_to' => $cycleEnd,
                'prorata_factor' => $this->prorata($validFrom, $cycleEnd, $cycleStart, $cycleEnd),
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

            $card->events()->create(['action' => 'created', 'to_status' => 'draft', 'user_id' => auth()->id()]);

            return $card;
        });
    }

    public function roleFor(User $user, PerformanceScorecard $card): ?string
    {
        $personnelId = $this->links->resolve($user);

        // Nobody reviews, calibrates or approves their own card — not even HR.
        return match (true) {
            $personnelId !== null && $personnelId === (int) $card->personnel_id => 'employee',
            $user->can('manage-performance-evaluation') => 'hr',
            $personnelId === null => null,
            $personnelId === (int) $card->manager_personnel_id => 'manager',
            default => null,
        };
    }

    /**
     * Actions the user may take on the card right now.
     *
     * @return array<int, string>
     */
    public function availableActions(PerformanceScorecard $card, User $user): array
    {
        $role = $this->roleFor($user, $card);

        return collect(PerformanceScorecard::TRANSITIONS)
            ->filter(fn (array $rule): bool => in_array($card->status, $rule['from'], true) && in_array($role, $rule['roles'], true))
            ->keys()
            ->all();
    }

    /**
     * Cards the user may see: all for HR, otherwise their own and the ones they manage.
     *
     * @return Builder<PerformanceScorecard>
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
     * Moves the card along the workflow, records the step and tells whoever acts next.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function transition(PerformanceScorecard $card, string $action, ?User $user, ?string $reason = null): void
    {
        $rule = PerformanceScorecard::TRANSITIONS[$action] ?? null;

        if ($user !== null) {
            $this->authorizeRole($user, $card, $rule['roles'] ?? []);
        }

        if ($rule === null || ! in_array($card->status, $rule['from'], true)) {
            throw ValidationException::withMessages(['scorecard' => __('performance_evaluation::kpi.errors.invalid_transition')]);
        }

        $reason = trim((string) $reason) ?: null;
        if (($rule['reason'] ?? false) && $reason === null) {
            throw ValidationException::withMessages(['reason' => __('performance_evaluation::kpi.errors.reason_required')]);
        }

        if ($action === 'submit_manager_review') {
            $this->ensureCompetenciesRated($card);
        }

        DB::transaction(function () use ($card, $rule, $action, $user, $reason): void {
            if ($action === 'close') {
                $this->freeze($card);
            }

            $from = $card->status;
            $card->fill([
                'status' => $rule['to'],
                'stage_due_at' => $this->stageDueDate($rule['to']),
                'reminded_at' => null,
                'escalated_at' => null,
            ])->save();

            $card->events()->create([
                'action' => $action,
                'from_status' => $from,
                'to_status' => $rule['to'],
                'reason' => $reason,
                'user_id' => $user?->id,
            ]);
        });

        if ($rule['to'] === 'approved') {
            $this->syncNineBox($card);
        }

        app(ScorecardNotifier::class)->transitioned($card, $action, $reason);
    }

    /**
     * Ends a card early (position change, termination): scores it, freezes it and trims
     * its validity and pro-rata to the last day it covered.
     */
    public function closeEarly(PerformanceScorecard $card, string $reason, Carbon $lastDay): void
    {
        $cycle = $card->cycle;
        // Never before the card started, never past where it (or its cycle) would have ended.
        $end = Carbon::parse($card->valid_to ?? $cycle->period_end)->startOfDay();
        $validTo = max(Carbon::parse($card->valid_from)->startOfDay(), min($lastDay->copy()->startOfDay(), $end));

        DB::transaction(function () use ($card, $reason, $cycle, $validTo): void {
            $card->valid_to = $validTo;
            $card->prorata_factor = $this->prorata(
                Carbon::parse($card->valid_from),
                $validTo,
                Carbon::parse($cycle->period_start),
                Carbon::parse($cycle->period_end),
            );
            $this->freeze($card);

            $from = $card->status;
            $card->fill(['status' => 'closed', 'closure_reason' => $reason, 'stage_due_at' => null])->save();
            $card->events()->create(['action' => 'closed_early', 'from_status' => $from, 'to_status' => 'closed', 'reason' => $reason]);
        });

        $this->syncNineBox($card);
    }

    /**
     * An approved result places the person on the 9-box performance axis for the cycle.
     */
    private function syncNineBox(PerformanceScorecard $card): void
    {
        $score = $this->personCycleScore($card->personnel_id, $card->performance_cycle_id);

        if ($score !== null) {
            $this->succession->syncPerformanceFromScore($card->personnel_id, $card->performance_cycle_id, $score);
        }
    }

    /**
     * Manager change (spec §5.1): the card stays, the right to evaluate moves to whoever
     * the org chart now names as the person's manager, together with the competency
     * form. Returns whether the manager changed.
     */
    public function reassignManager(PerformanceScorecard $card): bool
    {
        $personnel = $card->personnel;
        $managerId = $personnel ? ($this->routes->manager($personnel)['id'] ?? null) : null;

        if ($managerId === null || (int) $managerId === (int) $card->manager_personnel_id) {
            return false;
        }

        $previous = $card->manager;
        DB::transaction(function () use ($card, $managerId, $previous): void {
            $card->update(['manager_personnel_id' => $managerId]);
            $card->form?->update(['manager_id' => $this->userIdsForPersonnel((int) $managerId)[0] ?? null]);
            $card->events()->create([
                'action' => 'manager_changed',
                'from_status' => $card->status,
                'to_status' => $card->status,
                'reason' => __('performance_evaluation::kpi.manager_changed_note', [
                    'from' => $previous ? trim($previous->surname.' '.$previous->name) : '—',
                    'to' => trim(($card->refresh()->manager->surname ?? '').' '.($card->manager->name ?? '')),
                ]),
            ]);
        });

        app(ScorecardNotifier::class)->managerChanged($card, $previous?->id);

        return true;
    }

    /**
     * Long leave (spec §5.1): once the leave inside a card's period passes the threshold,
     * the card is judged on the days actually worked — additive targets (KPIs summed
     * over the period) and the pro-rata shrink by the worked share. Dropping back under
     * the threshold (leave cancelled) restores both. Returns whether anything changed.
     */
    public function applyLeave(PerformanceScorecard $card, int $leaveDays): bool
    {
        $threshold = (int) config('performance_evaluation.kpi.long_leave_days', 30);
        $leaveDays = $leaveDays > $threshold ? $leaveDays : 0;

        if ($leaveDays === (int) $card->leave_days) {
            return false;
        }

        $cycle = $card->cycle;
        $from = Carbon::parse($card->valid_from);
        $to = Carbon::parse($card->valid_to);
        $covered = $from->diffInDays($to) + 1;
        $worked = max(0, $covered - $leaveDays) / max(1, $covered);

        DB::transaction(function () use ($card, $leaveDays, $worked, $from, $to, $cycle): void {
            $card->load('items.kpi', 'items.kpiVersion');
            foreach ($card->items as $item) {
                $definition = $item->kpiVersion->snapshot ?? $item->kpi->only(['type', 'direction', 'aggregation']);
                $additive = ($definition['aggregation'] ?? null) === 'sum' && ($definition['type'] ?? null) === 'quantitative' && ($definition['direction'] ?? null) !== 'range';

                if (! $additive || ($item->target === null && $item->original_target === null)) {
                    continue;
                }

                $original = (float) ($item->original_target ?? $item->target);
                $item->update($leaveDays > 0
                    ? ['original_target' => $original, 'target' => round($original * $worked, 4)]
                    : ['original_target' => null, 'target' => $original]);
            }

            $card->prorata_factor = round($this->prorata($from, $to, Carbon::parse($cycle->period_start), Carbon::parse($cycle->period_end)) * $worked, 4);
            $card->leave_days = $leaveDays;
            $card->save();
            $card->events()->create([
                'action' => 'leave_adjusted',
                'from_status' => $card->status,
                'to_status' => $card->status,
                'reason' => __('performance_evaluation::kpi.leave.event', ['days' => $leaveDays]),
            ]);
        });

        $this->recalculate($card);

        return true;
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
    public function recordActual(PerformanceScorecardItem $item, float $value, User $user, ?UploadedFile $evidence = null, ?string $note = null, string $source = 'manual', bool $recalculate = true): PerformanceKpiActual
    {
        $card = $item->scorecard;
        $role = $this->authorizeRole($user, $card, ['hr', 'manager', 'employee']);

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['actual' => __('performance_evaluation::kpi.errors.scorecard_not_active')]);
        }

        if ($item->kpi->data_source === 'calculated') {
            throw ValidationException::withMessages(['actual' => __('performance_evaluation::kpi.formula.errors.no_manual')]);
        }

        if ($item->kpi->evidence_required && $evidence === null) {
            throw ValidationException::withMessages(['evidence' => __('performance_evaluation::kpi.errors.evidence_required')]);
        }

        $approved = $role !== 'employee';

        $actual = $item->actuals()->create([
            'value' => $value,
            'source' => $source,
            'evidence_path' => $evidence?->store('performance/kpi-evidence/'.$card->id, 'local'),
            'evidence_name' => $evidence?->getClientOriginalName(),
            'note' => $note,
            'entered_by' => $user->id,
            'approved_by' => $approved ? $user->id : null,
            'approved_at' => $approved ? now() : null,
        ]);

        if ($approved && $recalculate) {
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
     * item was built from), then the card's KPI, competency, final and calibrated score.
     */
    public function recalculate(PerformanceScorecard $card): void
    {
        DB::transaction(fn () => $this->rescore($card));
    }

    private function rescore(PerformanceScorecard $card): void
    {
        // ponytail: inline on each change; queue it if a card ever takes long enough to notice.
        $card->load(['items.kpi', 'items.kpiVersion', 'items.actuals', 'form:id,final_score', 'calibrations']);

        $definitions = $card->items->mapWithKeys(fn (PerformanceScorecardItem $item): array => [
            $item->id => $item->kpiVersion->snapshot ?? $item->kpi->only(['type', 'direction', 'aggregation', 'qualitative_scale', 'formula']),
        ]);
        $actuals = $this->itemActuals($card, $definitions->all());

        $scored = $card->items->map(function (PerformanceScorecardItem $item) use ($card, $definitions, $actuals): array {
            $definition = $definitions->get($item->id);
            $actual = $actuals[$item->id] ?? null;

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

            $forecast = $this->forecast($card, $definition, $actual);
            $forecastAchievement = $forecast === null ? null : $this->engine->achievement([
                'type' => $definition['type'],
                'direction' => $definition['direction'],
                'target' => $item->target,
                'range_min' => $item->range_min,
                'range_max' => $item->range_max,
                'scale' => $definition['qualitative_scale'] ?? null,
            ], $forecast);

            $item->update([
                'actual' => $actual,
                'achievement' => $achievement,
                'score' => $score,
                'forecast' => $forecast,
                'forecast_achievement' => $forecastAchievement,
            ]);

            return ['weight' => $item->weight, 'score' => $score];
        });

        $kpiScore = $this->engine->kpiScore($scored);
        $competencyScore = $card->form?->final_score === null ? null : (float) $card->form->final_score;
        $finalScore = $this->engine->finalScore($kpiScore, $competencyScore, (float) $card->kpi_weight_share, (float) $card->competency_weight_share);
        $delta = $card->calibrations->first()?->delta;
        $calibrated = $finalScore !== null && $delta !== null ? round(max(0.0, $finalScore + (float) $delta), 4) : null;

        $card->update([
            'kpi_score' => $kpiScore,
            'competency_score' => $competencyScore,
            'final_score' => $finalScore,
            'calibrated_score' => $calibrated,
            'rating_category' => $this->engine->ratingCategory($calibrated ?? $finalScore),
        ]);
    }

    /**
     * End-of-period forecast (spec §9): an additive KPI is extrapolated linearly from the
     * pace so far; any other quantitative KPI is expected to stay where it is.
     *
     * @param  array<string, mixed>  $definition
     */
    private function forecast(PerformanceScorecard $card, array $definition, ?float $actual): ?float
    {
        if ($actual === null || ($definition['type'] ?? null) !== 'quantitative') {
            return null;
        }

        if (($definition['aggregation'] ?? null) !== 'sum') {
            return $actual;
        }

        $from = Carbon::parse($card->valid_from)->startOfDay();
        $to = Carbon::parse($card->valid_to)->startOfDay();
        $total = $from->diffInDays($to) + 1;
        $elapsed = min($total, max(1, $from->diffInDays(today(), false) + 1));

        return round($actual * $total / $elapsed, 4);
    }

    /**
     * Each item's actual: entered/imported/synced items aggregate their approved values;
     * calculated items evaluate their formula over the other items' actuals by KPI code,
     * pass after pass so a formula may build on another formula.
     *
     * @param  array<int, array<string, mixed>>  $definitions  item id → KPI definition
     * @return array<int, float|null> item id → actual
     */
    private function itemActuals(PerformanceScorecard $card, array $definitions): array
    {
        $formula = app(KpiFormula::class);
        $actuals = [];
        $calculated = [];

        foreach ($card->items as $item) {
            if (filled($definitions[$item->id]['formula'] ?? null)) {
                $calculated[$item->id] = (string) $definitions[$item->id]['formula'];
                $actuals[$item->id] = null;

                continue;
            }

            $actuals[$item->id] = $this->aggregate($item->actuals->whereNotNull('approved_at'), (string) ($definitions[$item->id]['aggregation'] ?? 'last'));
        }

        $codes = $card->items->mapWithKeys(fn (PerformanceScorecardItem $item): array => [$item->id => $item->kpi->code]);
        for ($pass = 0; $pass < count($calculated); $pass++) {
            $values = collect($actuals)->mapWithKeys(fn ($value, int $itemId): array => [$codes[$itemId] => $value])->all();
            foreach ($calculated as $itemId => $expression) {
                try {
                    $actuals[$itemId] = $formula->evaluate($expression, $values);
                } catch (InvalidArgumentException) {
                    $actuals[$itemId] = null;
                }
            }
        }

        return $actuals;
    }

    /**
     * The person's result for a cycle when position changes split it over several cards:
     * each card's score weighted by the days it covered (spec §5.1).
     */
    public function personCycleScore(int $personnelId, int $cycleId): ?float
    {
        $cards = PerformanceScorecard::query()
            ->where('personnel_id', $personnelId)
            ->where('performance_cycle_id', $cycleId)
            ->get()
            ->filter(fn (PerformanceScorecard $card): bool => $card->effectiveScore() !== null);

        // Days covered × FTE share, so a half-time second post weighs half.
        $days = fn (PerformanceScorecard $card): float => ((int) $card->valid_from->diffInDays($card->valid_to) + 1) * (float) $card->fte;
        $totalDays = $cards->sum($days);

        if ($totalDays <= 0) {
            return null;
        }

        return round($cards->sum(fn (PerformanceScorecard $card): float => $card->effectiveScore() * $days($card)) / $totalDays, 4);
    }

    /**
     * The users behind a person: the explicit user link, else a user with the same e-mail.
     *
     * @return array<int, int>
     */
    public function userIdsForPersonnel(?int $personnelId): array
    {
        if ($personnelId === null) {
            return [];
        }

        $linked = UserPersonnelLink::query()->where('personnel_id', $personnelId)->pluck('user_id');
        if ($linked->isNotEmpty()) {
            return $linked->map(fn ($id): int => (int) $id)->all();
        }

        $email = Personnel::query()->whereKey($personnelId)->value('email');

        return $email
            ? User::query()->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($email))])->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : [];
    }

    /**
     * @param  array<int, string>  $roles
     *
     * @throws AuthorizationException
     */
    public function authorizeRole(User $user, PerformanceScorecard $card, array $roles): string
    {
        $role = $this->roleFor($user, $card);

        if ($role === null || ! in_array($role, $roles, true)) {
            throw new AuthorizationException;
        }

        return $role;
    }

    /**
     * The competency block: the template's evaluation form, opened (or reused) for this
     * person and cycle with the card's manager as evaluator.
     */
    private function competencyFormFor(PerformanceCycle $cycle, Personnel $personnel, PerformanceKpiTemplate $template, ?int $managerPersonnelId): ?PerformanceForm
    {
        if (! $template->performance_form_template_id || (float) $template->competency_weight_share <= 0) {
            return null;
        }

        $form = PerformanceForm::query()->firstOrCreate(
            [
                'performance_cycle_id' => $cycle->id,
                'performance_form_template_id' => $template->performance_form_template_id,
                'personnel_id' => $personnel->id,
            ],
            ['manager_id' => $this->userIdsForPersonnel($managerPersonnelId)[0] ?? null],
        );

        return $form;
    }

    /**
     * @throws ValidationException when the manager has not rated every competency yet
     */
    private function ensureCompetenciesRated(PerformanceScorecard $card): void
    {
        if (! $card->performance_form_id || (float) $card->competency_weight_share <= 0) {
            return;
        }

        $form = $card->form()->with('template.sections.items:id,performance_form_template_section_id')->first();
        $itemIds = $form?->template?->sections->flatMap->items->pluck('id') ?? collect();
        $rated = $form?->scores()->where('evaluator_type', 'manager')->pluck('performance_form_template_item_id') ?? collect();

        if ($itemIds->diff($rated)->isNotEmpty()) {
            throw ValidationException::withMessages(['scorecard' => __('performance_evaluation::kpi.errors.competencies_incomplete')]);
        }
    }

    private function freeze(PerformanceScorecard $card): void
    {
        $this->recalculate($card);
        $card->refresh()->load('items');
        $card->snapshot = [
            'closed_at' => now()->toIso8601String(),
            'kpi_weight_share' => (float) $card->kpi_weight_share,
            'competency_weight_share' => (float) $card->competency_weight_share,
            'kpi_score' => $card->kpi_score,
            'competency_score' => $card->competency_score,
            'final_score' => $card->final_score,
            'calibrated_score' => $card->calibrated_score,
            'rating_category' => $card->rating_category,
            'items' => $card->items->map(fn (PerformanceScorecardItem $item): array => $item->only([
                'performance_kpi_id', 'performance_kpi_version_id', 'performance_goal_id', 'weight', 'target', 'range_min', 'range_max',
                'threshold', 'stretch', 'cap', 'actual', 'achievement', 'score',
            ]))->all(),
        ];
        $card->locked_at = now();
    }

    /**
     * Working days on the attendance calendar: holidays and weekends are skipped.
     */
    private function stageDueDate(string $status): ?Carbon
    {
        $days = PerformanceScorecard::STAGE_WORKING_DAYS[$status] ?? null;

        return $days === null ? null : $this->workingDays->add(today(), $days);
    }

    private function prorata(Carbon $from, Carbon $to, Carbon $cycleStart, Carbon $cycleEnd): float
    {
        $cycleDays = $cycleStart->diffInDays($cycleEnd) + 1;

        return round(min(1.0, ($from->diffInDays($to) + 1) / max(1, $cycleDays)), 4);
    }

    /**
     * @return Collection<int, int> position id → active template id
     */
    public function templateIdsByPosition(): Collection
    {
        return DB::table('performance_kpi_template_positions')
            ->join('performance_kpi_templates', 'performance_kpi_templates.id', '=', 'performance_kpi_template_positions.performance_kpi_template_id')
            ->where('performance_kpi_templates.status', 'active')
            ->whereNull('performance_kpi_templates.deleted_at')
            ->pluck('performance_kpi_template_positions.performance_kpi_template_id', 'performance_kpi_template_positions.position_id');
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
}
