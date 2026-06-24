<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceFeedbackRater;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceFeedbackScore;
use App\Models\PerformanceFormTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 360° multi-rater feedback + calibration.
 *
 * A request gathers competency scores from several raters (manager/peer/subordinate/self)
 * using a form template's items as criteria. Once enough raters have submitted, HR
 * calibrates the raw per-item averages into agreed scores and a weighted final score.
 */
class Feedback360Service
{
    /**
     * @return array{requests:int,collecting:int,calibrating:int,closed:int}
     */
    public function summary(): array
    {
        $byStatus = PerformanceFeedbackRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'requests' => (int) $byStatus->sum(),
            'collecting' => (int) ($byStatus['collecting'] ?? 0),
            'calibrating' => (int) ($byStatus['calibrating'] ?? 0),
            'closed' => (int) ($byStatus['closed'] ?? 0),
        ];
    }

    /**
     * Requests for the list view, newest first, with rater progress counters.
     */
    public function requests(?int $cycleId = null): Collection
    {
        return PerformanceFeedbackRequest::query()
            ->with(['subject:id,surname,name,patronymic', 'cycle:id,name', 'template:id,name'])
            ->withCount([
                'raters',
                'raters as submitted_raters_count' => fn ($q) => $q->where('status', 'submitted'),
            ])
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId))
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $requestId): ?PerformanceFeedbackRequest
    {
        return PerformanceFeedbackRequest::query()
            ->with([
                'subject:id,surname,name,patronymic',
                'cycle:id,name',
                'template:id,name',
                'raters' => fn ($q) => $q->orderBy('rater_type'),
                'raters.personnel:id,surname,name,patronymic',
                'raters.scores',
            ])
            ->find($requestId);
    }

    /**
     * Ordered competency items of a template (across its sections) used as rating criteria.
     *
     * @return array<int,array{id:int,name:string,section:string,weight:float}>
     */
    public function templateItems(int $templateId): array
    {
        $template = PerformanceFormTemplate::query()
            ->with([
                'sections' => fn ($q) => $q->orderBy('sort_order'),
                'sections.items' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->find($templateId);

        if (! $template) {
            return [];
        }

        $items = [];
        foreach ($template->sections as $section) {
            foreach ($section->items as $item) {
                $items[] = [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'section' => (string) $section->name,
                    'weight' => (float) $item->weight_percent,
                ];
            }
        }

        return $items;
    }

    public function createRequest(int $cycleId, int $templateId, int $subjectPersonnelId, bool $isAnonymous, ?string $dueDate): PerformanceFeedbackRequest
    {
        return PerformanceFeedbackRequest::query()->updateOrCreate(
            [
                'performance_cycle_id' => $cycleId,
                'performance_form_template_id' => $templateId,
                'subject_personnel_id' => $subjectPersonnelId,
            ],
            [
                'is_anonymous' => $isAnonymous,
                'due_date' => $dueDate ?: null,
            ],
        );
    }

    public function addRater(int $requestId, string $raterType, int $raterPersonnelId): PerformanceFeedbackRater
    {
        $type = in_array($raterType, PerformanceFeedbackRater::TYPES, true) ? $raterType : 'peer';

        return PerformanceFeedbackRater::query()->firstOrCreate(
            [
                'performance_feedback_request_id' => $requestId,
                'rater_personnel_id' => $raterPersonnelId,
                'rater_type' => $type,
            ],
            ['status' => 'pending'],
        );
    }

    public function removeRater(int $raterId): void
    {
        PerformanceFeedbackRater::query()->whereKey($raterId)->delete();
    }

    /**
     * Persist a rater's scores and mark them submitted.
     *
     * @param  array<int,int|float|string|null>  $scores   item id => score
     * @param  array<int,string|null>            $comments item id => comment
     */
    public function submitScores(int $raterId, array $scores, array $comments = []): void
    {
        DB::transaction(function () use ($raterId, $scores, $comments): void {
            $rater = PerformanceFeedbackRater::query()->findOrFail($raterId);

            foreach ($scores as $itemId => $score) {
                if ($score === null || $score === '') {
                    continue;
                }

                PerformanceFeedbackScore::query()->updateOrCreate(
                    [
                        'performance_feedback_rater_id' => $rater->id,
                        'performance_form_template_item_id' => (int) $itemId,
                    ],
                    [
                        'score' => max(0, min(100, (float) $score)),
                        'comment' => $comments[$itemId] ?? null,
                    ],
                );
            }

            $rater->update(['status' => 'submitted', 'submitted_at' => now()]);
        });
    }

    /**
     * Aggregate submitted scores into per-item averages and per-rater-type breakdowns.
     *
     * @return array{
     *     items: array<int,array{id:int,name:string,section:string,weight:float,average:?float,count:int,by_type:array<string,?float>}>,
     *     by_type: array<string,?float>,
     *     raw_final: ?float
     * }
     */
    public function aggregate(int $requestId): array
    {
        $request = PerformanceFeedbackRequest::query()->find($requestId);
        if (! $request) {
            return ['items' => [], 'by_type' => [], 'raw_final' => null];
        }

        $items = $this->templateItems((int) $request->performance_form_template_id);

        // All submitted scores for this request, joined to their rater's type.
        $rows = PerformanceFeedbackScore::query()
            ->join('performance_feedback_raters as r', 'r.id', '=', 'performance_feedback_scores.performance_feedback_rater_id')
            ->where('r.performance_feedback_request_id', $requestId)
            ->where('r.status', 'submitted')
            ->get([
                'performance_feedback_scores.performance_form_template_item_id as item_id',
                'performance_feedback_scores.score as score',
                'r.rater_type as rater_type',
            ]);

        $byItem = $rows->groupBy('item_id');
        $typeTotals = [];

        $resolvedItems = [];
        $weightSum = 0.0;
        $weightedSum = 0.0;
        foreach ($items as $item) {
            $itemRows = $byItem->get($item['id'], collect());
            $average = $itemRows->isEmpty() ? null : round((float) $itemRows->avg('score'), 2);

            $byType = [];
            foreach (PerformanceFeedbackRater::TYPES as $type) {
                $typeRows = $itemRows->where('rater_type', $type);
                $byType[$type] = $typeRows->isEmpty() ? null : round((float) $typeRows->avg('score'), 2);
            }

            foreach ($itemRows as $row) {
                $typeTotals[$row->rater_type][] = (float) $row->score;
            }

            if ($average !== null) {
                $weight = $item['weight'] > 0 ? $item['weight'] : 1.0;
                $weightSum += $weight;
                $weightedSum += $average * $weight;
            }

            $resolvedItems[] = $item + [
                'average' => $average,
                'count' => $itemRows->count(),
                'by_type' => $byType,
            ];
        }

        $overallByType = [];
        foreach (PerformanceFeedbackRater::TYPES as $type) {
            $vals = $typeTotals[$type] ?? [];
            $overallByType[$type] = $vals === [] ? null : round(array_sum($vals) / count($vals), 2);
        }

        return [
            'items' => $resolvedItems,
            'by_type' => $overallByType,
            'raw_final' => $weightSum > 0 ? round($weightedSum / $weightSum, 2) : null,
        ];
    }

    /**
     * Save HR's calibrated per-item scores, compute the weighted final, and (optionally)
     * approve the calibration. Moving to calibration also flips the request status.
     *
     * @param  array<int,int|float|string|null>  $calibratedScores item id => score
     */
    public function calibrate(int $requestId, array $calibratedScores, ?string $note, bool $approve, ?int $userId): void
    {
        $request = PerformanceFeedbackRequest::query()->findOrFail($requestId);
        $items = $this->templateItems((int) $request->performance_form_template_id);

        $clean = [];
        $weightSum = 0.0;
        $weightedSum = 0.0;
        foreach ($items as $item) {
            $value = $calibratedScores[$item['id']] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $score = max(0, min(100, (float) $value));
            $clean[$item['id']] = $score;
            $weight = $item['weight'] > 0 ? $item['weight'] : 1.0;
            $weightSum += $weight;
            $weightedSum += $score * $weight;
        }

        $request->update([
            'calibrated_scores' => $clean,
            'final_score' => $weightSum > 0 ? round($weightedSum / $weightSum, 2) : null,
            'calibration_status' => $approve ? 'approved' : 'pending',
            'calibrated_by' => $userId,
            'calibration_note' => $note,
            'status' => $approve ? 'closed' : 'calibrating',
        ]);
    }

    public function reopen(int $requestId): void
    {
        PerformanceFeedbackRequest::query()->whereKey($requestId)->update([
            'status' => 'collecting',
            'calibration_status' => 'pending',
        ]);
    }

    public function delete(int $requestId): void
    {
        PerformanceFeedbackRequest::query()->whereKey($requestId)->delete();
    }
}
