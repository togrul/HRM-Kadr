<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingPlanItem;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TrainingSessionProposalService
{
    public function proposals(?int $limit = null): Collection
    {
        $items = TrainingPlanItem::query()
            ->with([
                'plan:id,title,plan_year,plan_quarter,status',
                'program:id,title,duration_hours',
                'competency:id,name',
                'position:id,name',
            ])
            ->where('review_status', 'approved')
            ->orderByDesc('suggested_score')
            ->orderByDesc('participant_count')
            ->get();

        $existingPlanItemIds = TrainingSession::query()
            ->whereNotNull('training_plan_item_id')
            ->whereIn('status', ['draft', 'scheduled', 'in_progress', 'completed'])
            ->pluck('training_plan_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $sessionsByPlan = TrainingSession::query()
            ->whereNotNull('training_annual_plan_id')
            ->whereNotNull('scheduled_start_at')
            ->orderBy('scheduled_start_at')
            ->get(['training_annual_plan_id', 'scheduled_start_at'])
            ->groupBy('training_annual_plan_id');

        $items = $items
            ->filter(function (TrainingPlanItem $item) use ($existingPlanItemIds): bool {
                return ! in_array((int) $item->id, $existingPlanItemIds, true);
            })
            ->map(fn (TrainingPlanItem $item): array => $this->proposalFromPlanItem($item, $sessionsByPlan->get($item->training_annual_plan_id)))
            ->values();

        if ($limit !== null) {
            return $items->take($limit)->values();
        }

        return $items;
    }

    public function proposalFromPlanItem(TrainingPlanItem $item, ?Collection $existingPlanSessions = null): array
    {
        $start = $this->suggestedStartAt($item, $existingPlanSessions);
        $hours = (float) ($item->program?->duration_hours ?? 8);
        $end = $start->addMinutes((int) round($hours * 60));
        $budget = $this->resolveBudget($item, $hours);

        return [
            'plan_item_id' => $item->id,
            'training_annual_plan_id' => $item->training_annual_plan_id,
            'training_program_id' => $item->training_program_id,
            'title' => $item->program?->title
                ?: $item->competency?->name
                ?: $item->position?->name
                ?: __('training_needs::dashboard.labels.default_session_title'),
            'scheduled_start_at' => $start->format('Y-m-d\TH:i'),
            'scheduled_end_at' => $end->format('Y-m-d\TH:i'),
            'location' => null,
            'trainer_name' => null,
            'capacity' => $item->participant_count,
            'planned_budget' => $budget,
            'auto_fill_participants' => true,
            'status' => 'scheduled',
            'notes' => trim(implode(' | ', array_filter([
                $item->competency?->name,
                $item->position?->name,
                $item->review_note,
            ]))),
            'participant_count' => $item->participant_count,
            'need_count' => $item->need_count,
            'estimated_budget' => $budget,
            'program_title' => $item->program?->title,
            'competency_name' => $item->competency?->name,
            'position_name' => $item->position?->name,
            'score' => $item->suggested_score,
            'plan_title' => $item->plan?->title,
        ];
    }

    private function suggestedStartAt(TrainingPlanItem $item, ?Collection $existingPlanSessions = null): CarbonImmutable
    {
        $year = (int) ($item->plan?->plan_year ?: now()->year);
        $quarter = $item->plan?->plan_quarter;
        $defaultStartHour = (int) config('training_needs.proposal.default_start_hour', 10);

        if ($quarter) {
            $month = (($quarter - 1) * 3) + 1;
        } else {
            $month = max(now()->month, 1);
            if ($year > now()->year) {
                $month = 1;
            }
        }

        $base = CarbonImmutable::create($year, $month, 1, $defaultStartHour, 0, 0);

        if ($base->isPast()) {
            $base = CarbonImmutable::instance(now())->startOfDay()->addWeek()->setHour($defaultStartHour)->setMinute(0);
        }

        if ((int) $base->dayOfWeekIso > 5) {
            $base = $base->next(CarbonImmutable::MONDAY);
        } elseif ((int) $base->dayOfWeekIso !== 1) {
            $base = $base->next(CarbonImmutable::MONDAY);
        }

        $existingStartDates = ($existingPlanSessions ?? collect())
            ->pluck('scheduled_start_at')
            ->filter()
            ->map(fn ($date) => CarbonImmutable::parse((string) $date)->startOfDay()->format('Y-m-d'))
            ->all();

        while (in_array($base->startOfDay()->format('Y-m-d'), $existingStartDates, true)) {
            $base = $base->addWeek();
            if ((int) $base->dayOfWeekIso > 5) {
                $base = $base->next(CarbonImmutable::MONDAY);
            }
        }

        return $base;
    }

    private function resolveBudget(TrainingPlanItem $item, float $hours): float
    {
        $estimatedBudget = (float) ($item->estimated_budget ?? 0);
        if ($estimatedBudget > 0) {
            return round($estimatedBudget, 2);
        }

        $deliveryType = (string) ($item->program?->delivery_type ?? 'internal');
        $hourlyRate = (float) config("training_needs.proposal.hourly_rate.{$deliveryType}", config('training_needs.proposal.default_hourly_rate', 25));

        return round(max((int) $item->participant_count, 1) * max($hours, 1) * $hourlyRate, 2);
    }
}
