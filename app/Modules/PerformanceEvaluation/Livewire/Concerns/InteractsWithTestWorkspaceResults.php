<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestQuestion;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceTestTranscriptService;

trait InteractsWithTestWorkspaceResults
{
    public function getSelectedSessionAttemptSummaryProperty(): array
    {
        $attempt = $this->attemptHistory->first();

        return [
            'status' => $attempt?->status,
            'score' => $attempt?->score,
            'percentage' => $attempt?->percentage,
            'submitted_at' => $attempt?->submitted_at,
            'passed' => $attempt?->passed,
        ];
    }

    public function getSelectedAttemptAnalyticsProperty(): array
    {
        $attempt = $this->attemptHistory->first();

        if (! $attempt) {
            return [
                'attempt' => null,
                'question_rows' => [],
                'timeline' => [],
            ];
        }

        return $this->rememberRuntime(
            'performanceEvaluation.testWorkspace.selectedAttemptAnalytics.'.$attempt->id,
            fn (): array => app(PerformanceTestTranscriptService::class)->buildAttemptAnalytics(
                $this->hydrateInlineAttemptAnalytics($attempt)
            )
        );
    }

    public function getBackUrlProperty(): string
    {
        $returnUrl = request()->query('return');

        if (is_string($returnUrl) && str_starts_with($returnUrl, url('/'))) {
            return $returnUrl;
        }

        return route('performance-evaluation');
    }

    protected function hydrateInlineAttemptAnalytics(PerformanceTestAttempt $attempt): PerformanceTestAttempt
    {
        $session = $this->selectedSession;
        if (! $session) {
            return app(PerformanceTestTranscriptService::class)->loadVisibleAttempt(auth()->user(), $attempt->id);
        }

        $attempt->setRelation('session', $session);

        $questions = $session->bank?->questions?->keyBy('id') ?? collect();
        $reviewerIds = [];

        foreach ($attempt->answers as $answer) {
            $question = $questions->get((int) $answer->performance_test_question_id);

            if ($question instanceof PerformanceTestQuestion) {
                $answer->setRelation('question', $question);

                if ($answer->selected_option_id) {
                    $answer->setRelation(
                        'selectedOption',
                        $question->options->firstWhere('id', (int) $answer->selected_option_id)
                    );
                }
            }

            if ($answer->reviewed_by) {
                $reviewerIds[] = (int) $answer->reviewed_by;
            }
        }

        if ($reviewerIds !== []) {
            $reviewers = User::query()
                ->whereIn('id', array_values(array_unique($reviewerIds)))
                ->get(['id', 'name'])
                ->keyBy('id');

            foreach ($attempt->answers as $answer) {
                if ($answer->reviewed_by) {
                    $answer->setRelation('reviewer', $reviewers->get((int) $answer->reviewed_by));
                }
            }
        }

        return $attempt;
    }
}
