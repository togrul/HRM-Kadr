<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\EmployeeCompetencyProfile;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestQuestionOption;
use App\Models\PerformanceTestTrainingNeedLink;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgramCompetency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceSkillMeasurementService
{
    /**
     * @return array<int, array{label:string,is_correct:bool,score_value:float,sort_order:int}>
     */
    public function parseOptions(string $raw, float $questionMaxScore): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($raw)) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter();

        return $lines->values()->map(function (string $line, int $index) use ($questionMaxScore): array {
            $parts = array_map('trim', explode('|', $line));
            $label = (string) ($parts[0] ?? '');
            $isCorrect = in_array(strtolower((string) ($parts[1] ?? '0')), ['1', 'true', 'yes', 'correct'], true);
            $scoreValue = isset($parts[2]) && $parts[2] !== ''
                ? (float) $parts[2]
                : ($isCorrect ? $questionMaxScore : 0.0);

            return [
                'label' => $label,
                'is_correct' => $isCorrect,
                'score_value' => $scoreValue,
                'sort_order' => $index,
            ];
        })->filter(fn (array $item) => $item['label'] !== '')->values()->all();
    }

    public function syncQuestionOptions(PerformanceTestQuestion $question, string $rawOptions): void
    {
        $question->options()->delete();

        if (! $question->isAutoScored()) {
            return;
        }

        $options = $this->parseOptions($rawOptions, (float) $question->max_score);
        foreach ($options as $option) {
            $question->options()->create($option);
        }
    }

    public function submitAttempt(PerformanceTestAttempt $attempt): PerformanceTestAttempt
    {
        $attempt->loadMissing([
            'session.bank:id,pass_score',
            'session.personnel:id,position_id',
            'answers.question.options',
            'answers.selectedOption',
        ]);

        DB::transaction(function () use ($attempt): void {
            foreach ($attempt->answers as $answer) {
                $this->scoreAnswer($answer);
            }

            $attempt->refresh()->loadMissing([
                'session.bank:id,pass_score',
                'session.personnel:id,position_id',
                'answers.question:id,training_competency_id,max_score,question_type',
            ]);

            $pendingManual = $attempt->answers->contains(fn (PerformanceTestAttemptAnswer $answer) => $answer->review_status === 'pending');
            $totals = $this->computeAttemptTotals($attempt->answers);

            $attempt->update([
                'submitted_at' => $attempt->submitted_at ?? now(),
                'auto_scored_at' => now(),
                'score' => $totals['score'],
                'percentage' => $totals['percentage'],
                'passed' => $pendingManual ? null : ($totals['percentage'] >= $this->attemptPassScore($attempt)),
                'status' => $pendingManual ? 'review_pending' : 'completed',
                'reviewed_at' => $pendingManual ? null : now(),
            ]);

            if (! $pendingManual) {
                $this->syncAttemptOutcomes($attempt->fresh());
            }
        });

        return $attempt->fresh(['session.bank', 'answers.question', 'trainingNeedLinks']);
    }

    public function reviewAnswer(PerformanceTestAttemptAnswer $answer, float $score, ?string $feedback, ?int $reviewerId): PerformanceTestAttemptAnswer
    {
        $answer->loadMissing(['attempt.answers.question', 'attempt.session.bank', 'attempt.session.personnel']);

        DB::transaction(function () use ($answer, $score, $feedback, $reviewerId): void {
            $answer->update([
                'review_score' => $score,
                'final_score' => $score,
                'review_status' => 'reviewed',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'feedback' => $feedback,
            ]);

            $attempt = $answer->attempt->fresh(['session.bank', 'session.personnel', 'answers.question']);
            $pendingManual = $attempt->answers->contains(fn (PerformanceTestAttemptAnswer $item) => $item->review_status === 'pending');
            $totals = $this->computeAttemptTotals($attempt->answers);

            $attempt->update([
                'score' => $totals['score'],
                'percentage' => $totals['percentage'],
                'passed' => $pendingManual ? null : ($totals['percentage'] >= $this->attemptPassScore($attempt)),
                'status' => $pendingManual ? 'review_pending' : 'completed',
                'reviewed_at' => $pendingManual ? null : now(),
                'reviewed_by' => $pendingManual ? null : $reviewerId,
            ]);

            if (! $pendingManual) {
                $this->syncAttemptOutcomes($attempt->fresh());
            }
        });

        return $answer->fresh(['attempt']);
    }

    public function syncAttemptOutcomes(PerformanceTestAttempt $attempt): void
    {
        $attempt->loadMissing([
            'session.personnel:id,position_id',
            'answers.question:id,training_competency_id,max_score',
            'trainingNeedLinks.trainingNeed',
        ]);

        $competencyScores = $attempt->answers
            ->filter(fn (PerformanceTestAttemptAnswer $answer) => filled($answer->question?->training_competency_id) && $answer->final_score !== null)
            ->groupBy(fn (PerformanceTestAttemptAnswer $answer) => (int) $answer->question->training_competency_id)
            ->map(function (Collection $answers) {
                $score = (float) $answers->sum(fn (PerformanceTestAttemptAnswer $answer) => (float) $answer->final_score);
                $max = (float) $answers->sum(fn (PerformanceTestAttemptAnswer $answer) => (float) ($answer->question?->max_score ?? 0));
                $percentage = $max > 0 ? round(($score / $max) * 100, 2) : 0.0;

                return [
                    'score' => $score,
                    'max' => $max,
                    'percentage' => $percentage,
                ];
            });

        DB::transaction(function () use ($attempt, $competencyScores): void {
            $existingLinks = $attempt->trainingNeedLinks()->with('trainingNeed')->get()->keyBy('training_competency_id');

            foreach ($competencyScores as $competencyId => $result) {
                $levelId = $this->levelIdFromPercentage((float) $result['percentage']);

                EmployeeCompetencyProfile::query()->updateOrCreate(
                    [
                        'personnel_id' => $attempt->session->personnel_id,
                        'training_competency_id' => (int) $competencyId,
                    ],
                    [
                        'current_level_id' => $levelId,
                        'source' => 'skill_measurement',
                        'last_assessed_at' => now(),
                    ]
                );

                $threshold = 60.0;
                if ((float) $result['percentage'] >= $threshold) {
                    $this->deleteExistingNeedLink($existingLinks->get((int) $competencyId));
                    continue;
                }

                $requirement = RoleCompetencyRequirement::query()
                    ->where('position_id', $attempt->session->personnel?->position_id)
                    ->where('training_competency_id', (int) $competencyId)
                    ->first();

                $recommendedProgramId = TrainingProgramCompetency::query()
                    ->where('training_competency_id', (int) $competencyId)
                    ->orderByDesc('target_level_id')
                    ->value('training_program_id');

                $priority = (float) $result['percentage'] <= 40 ? 'high' : 'medium';
                $link = $existingLinks->get((int) $competencyId);
                $need = $link?->trainingNeed ?? new TrainingNeedItem();

                $need->fill([
                    'personnel_id' => $attempt->session->personnel_id,
                    'training_competency_id' => (int) $competencyId,
                    'position_id' => $attempt->session->personnel?->position_id,
                    'recommended_program_id' => $recommendedProgramId,
                    'target_level_id' => $requirement?->required_level_id,
                    'priority' => $priority,
                    'source' => 'skill_gap',
                    'status' => 'draft',
                    'reason' => __('performance_evaluation::dashboard.messages.skill_gap_reason', [
                        'attempt' => $attempt->id,
                        'competency' => $competencyId,
                        'percentage' => (string) $result['percentage'],
                    ]),
                    'plan_note' => __('performance_evaluation::dashboard.messages.auto_created_skill_weak_area_note'),
                ]);
                $need->save();

                PerformanceTestTrainingNeedLink::query()->updateOrCreate(
                    [
                        'performance_test_attempt_id' => $attempt->id,
                        'training_competency_id' => (int) $competencyId,
                    ],
                    [
                        'training_need_item_id' => $need->id,
                        'source' => 'test_gap',
                    ]
                );
            }

            $existingLinks
                ->filter(fn ($link, $competencyId) => ! $competencyScores->has((int) $competencyId))
                ->each(fn (PerformanceTestTrainingNeedLink $link) => $this->deleteExistingNeedLink($link));

            $attempt->update(['weak_area_synced_at' => now()]);
        });
    }

    private function scoreAnswer(PerformanceTestAttemptAnswer $answer): void
    {
        $question = $answer->question;
        if ($question === null) {
            return;
        }

        if (! $question->isAutoScored()) {
            $answer->update([
                'auto_score' => null,
                'final_score' => null,
                'is_correct' => null,
                'review_status' => 'pending',
            ]);

            return;
        }

        $selectedOption = $answer->selectedOption;
        $autoScore = (float) ($selectedOption?->score_value ?? 0);
        $isCorrect = (bool) ($selectedOption?->is_correct ?? false);

        $answer->update([
            'auto_score' => $autoScore,
            'final_score' => $autoScore,
            'is_correct' => $isCorrect,
            'review_status' => 'auto_scored',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PerformanceTestAttemptAnswer>  $answers
     * @return array{score:float,percentage:float}
     */
    private function computeAttemptTotals(Collection $answers): array
    {
        $score = (float) $answers->sum(fn (PerformanceTestAttemptAnswer $answer) => (float) ($answer->final_score ?? 0));
        $max = (float) $answers->sum(fn (PerformanceTestAttemptAnswer $answer) => (float) ($answer->question?->max_score ?? 0));
        $percentage = $max > 0 ? round(($score / $max) * 100, 2) : 0.0;

        return [
            'score' => round($score, 2),
            'percentage' => $percentage,
        ];
    }

    private function attemptPassScore(PerformanceTestAttempt $attempt): float
    {
        return (float) ($attempt->session?->pass_score ?? $attempt->session?->bank?->pass_score ?? 60);
    }

    private function levelIdFromPercentage(float $percentage): ?int
    {
        $levels = TrainingLevel::query()->orderBy('score')->get(['id', 'score']);
        if ($levels->isEmpty()) {
            return null;
        }

        $maxScore = (int) $levels->max('score');
        $mappedScore = max(1, min($maxScore, (int) ceil(($percentage / 100) * $maxScore)));

        return (int) ($levels->firstWhere('score', $mappedScore)?->id ?? $levels->last()->id);
    }

    private function deleteExistingNeedLink(?PerformanceTestTrainingNeedLink $link): void
    {
        if ($link === null) {
            return;
        }

        $trainingNeed = $link->trainingNeed;
        $link->delete();

        if ($trainingNeed !== null && $trainingNeed->source === 'skill_gap') {
            $trainingNeed->delete();
        }
    }
}
