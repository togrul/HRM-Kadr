<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceCalibrationAdjustment;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceGoal;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardCheckin;
use App\Models\PerformanceScorecardItem;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The review side of a scorecard (spec §5–6): competency ratings on the linked form,
 * check-ins while the card is active, HR calibration and the goal a KPI item serves.
 */
class ScorecardReviewService
{
    /** Competencies are rated 1–5; each rating maps to a percentage (spec §6.3). */
    public const RATING_SCALE = KpiScoringEngine::DEFAULT_QUALITATIVE_SCALE;

    /** Largest calibration move either way, in score points (spec §6.5 default). */
    public const MAX_CALIBRATION_DELTA = 15;

    /** Target share of each rating across a cycle, low to high (spec §6.5 default). */
    public const TARGET_DISTRIBUTION = [
        'not_meeting' => 10,
        'partially_meeting' => 20,
        'meeting' => 50,
        'exceeding' => 15,
        'far_exceeding' => 5,
    ];

    public function __construct(
        private readonly ScorecardService $scorecards,
    ) {}

    /**
     * The competency items of the card's form with the self and manager rating (1–5) each.
     *
     * @return Collection<int, array{id: int, name: string, section: string, self: int|null, manager: int|null, self_comment: string|null, manager_comment: string|null}>
     */
    public function competencies(PerformanceScorecard $card): Collection
    {
        $form = $card->form()->with(['template.sections.items', 'scores'])->first();
        if ($form === null) {
            return collect();
        }

        $scores = $form->scores->groupBy('performance_form_template_item_id');

        return $form->template->sections->sortBy('sort_order')->flatMap(fn ($section) => $section->items->sortBy('sort_order')->map(function (PerformanceFormTemplateItem $item) use ($section, $scores): array {
            $byType = ($scores->get($item->id) ?? collect())->keyBy('evaluator_type');

            return [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'section' => (string) $section->name,
                'self' => $this->ratingFromScore($byType->get('self')?->score),
                'manager' => $this->ratingFromScore($byType->get('manager')?->score),
                'self_comment' => $byType->get('self')?->comment,
                'manager_comment' => $byType->get('manager')?->comment,
            ];
        }))->values();
    }

    /**
     * The owner rates themselves during self review; the manager (or HR) rates during
     * manager review. Only the manager's rating reaches the score.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function rateCompetency(PerformanceScorecard $card, int $itemId, string $evaluator, int $rating, ?string $comment, User $user): void
    {
        [$status, $roles] = $evaluator === 'self' ? ['self_review', ['employee']] : ['manager_review', ['manager', 'hr']];
        $this->scorecards->authorizeRole($user, $card, $roles);

        if ($card->status !== $status || $card->form === null) {
            throw ValidationException::withMessages(['competency' => __('performance_evaluation::kpi.errors.competency_stage')]);
        }

        if (! array_key_exists($rating, self::RATING_SCALE) || ! $this->competencies($card)->contains('id', $itemId)) {
            throw ValidationException::withMessages(['competency' => __('performance_evaluation::kpi.errors.competency_invalid')]);
        }

        PerformanceFormScore::query()->updateOrCreate(
            ['performance_form_id' => $card->performance_form_id, 'performance_form_template_item_id' => $itemId, 'evaluator_type' => $evaluator],
            ['score' => self::RATING_SCALE[$rating], 'comment' => $comment],
        );

        $card->form->update([$evaluator === 'self' ? 'self_status' : 'manager_status' => 'submitted']);

        // Refreshes the form result (and with it the card) and links weak areas to training needs.
        app(PerformanceWeakAreaTrainingNeedService::class)->syncForForm($card->form->fresh());
    }

    /**
     * HR moves a score up or down by at most MAX_CALIBRATION_DELTA points, with a reason.
     * The latest adjustment is the one that counts; the original final score is kept.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function calibrate(PerformanceScorecard $card, float $delta, string $reason, User $user): PerformanceCalibrationAdjustment
    {
        $this->scorecards->authorizeRole($user, $card, ['hr']);

        if ($card->status !== 'calibration') {
            throw ValidationException::withMessages(['calibration' => __('performance_evaluation::kpi.errors.calibration_stage')]);
        }

        if (abs($delta) > self::MAX_CALIBRATION_DELTA) {
            throw ValidationException::withMessages(['calibration' => __('performance_evaluation::kpi.errors.calibration_too_large', ['max' => self::MAX_CALIBRATION_DELTA])]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['calibration' => __('performance_evaluation::kpi.errors.reason_required')]);
        }

        $adjustment = $card->calibrations()->create(['delta' => $delta, 'reason' => trim($reason), 'adjusted_by' => $user->id]);
        $this->scorecards->recalculate($card);

        return $adjustment;
    }

    /**
     * How the cycle's scored cards spread over the rating bands, next to the target spread.
     *
     * @return array<string, array{count: int, share: float, target: int}>
     */
    public function distribution(int $cycleId): array
    {
        $counts = PerformanceScorecard::query()
            ->where('performance_cycle_id', $cycleId)
            ->whereIn('status', ['calibration', 'approved', 'closed'])
            ->whereNotNull('rating_category')
            ->selectRaw('rating_category, COUNT(*) as aggregate')
            ->groupBy('rating_category')
            ->pluck('aggregate', 'rating_category');

        $total = max(1, (int) $counts->sum());

        return collect(self::TARGET_DISTRIBUTION)
            ->map(fn (int $target, string $category): array => [
                'count' => (int) $counts->get($category, 0),
                'share' => round((int) $counts->get($category, 0) / $total * 100, 1),
                'target' => $target,
            ])
            ->all();
    }

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function addCheckin(PerformanceScorecard $card, Carbon $date, string $progress, ?string $risks, User $user): PerformanceScorecardCheckin
    {
        $this->scorecards->authorizeRole($user, $card, ['hr', 'manager', 'employee']);

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['checkin' => __('performance_evaluation::kpi.errors.scorecard_not_active')]);
        }

        return $card->checkins()->create([
            'checkin_date' => $date,
            'progress' => trim($progress),
            'risks' => trim((string) $risks) ?: null,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Check-ins a card should have by the end of its cycle: one a month, at least two.
     */
    public function expectedCheckins(PerformanceScorecard $card): int
    {
        $months = (int) ceil(($card->valid_from->diffInDays($card->valid_to) + 1) / 30);

        return max(2, $months);
    }

    /**
     * Ties a KPI item to a goal of the same cycle (or unties it) while the card is a draft.
     *
     * @throws AuthorizationException|ValidationException
     */
    public function linkGoal(PerformanceScorecardItem $item, ?int $goalId, User $user): void
    {
        $card = $item->scorecard;
        $this->scorecards->authorizeRole($user, $card, ['hr', 'manager']);

        if ($card->status !== 'draft') {
            throw ValidationException::withMessages(['goal' => __('performance_evaluation::kpi.errors.scorecard_locked')]);
        }

        if ($goalId !== null && ! PerformanceGoal::query()->whereKey($goalId)->where('performance_cycle_id', $card->performance_cycle_id)->exists()) {
            throw ValidationException::withMessages(['goal' => __('performance_evaluation::kpi.errors.goal_invalid')]);
        }

        $item->update(['performance_goal_id' => $goalId]);
    }

    /**
     * KPI items of the cycle not tied to any goal — the cascade's gaps (spec §4.3).
     */
    public function unlinkedItemCount(int $cycleId): int
    {
        return PerformanceScorecardItem::query()
            ->whereNull('performance_goal_id')
            ->whereHas('scorecard', fn ($query) => $query->where('performance_cycle_id', $cycleId))
            ->count();
    }

    private function ratingFromScore(mixed $score): ?int
    {
        if ($score === null) {
            return null;
        }

        $rating = array_search((float) $score, array_map('floatval', self::RATING_SCALE), true);

        return $rating === false ? null : (int) $rating;
    }
}
