<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceTestAttempt;
use App\Models\User;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class PerformanceTestTranscriptService
{
    public function loadVisibleAttempt(User $user, int $attemptId): PerformanceTestAttempt
    {
        $attempt = PerformanceTestAttempt::query()
            ->with([
                'session:id,personnel_id,reviewer_id,performance_test_bank_id,pass_score,duration_minutes,max_attempts,status,available_until',
                'session.personnel:id,surname,name,patronymic,tabel_no,position_id',
                'session.bank:id,name,code,pass_score,duration_minutes,max_attempts',
                'answers:id,performance_test_attempt_id,performance_test_question_id,selected_option_id,answer_text,is_correct,auto_score,review_score,final_score,review_status,reviewed_by,reviewed_at,feedback',
                'answers.question:id,performance_test_bank_id,question_type,prompt,description,max_score,sort_order',
                'answers.question.options:id,performance_test_question_id,label,is_correct,score_value,sort_order',
                'answers.selectedOption:id,performance_test_question_id,label,is_correct,score_value,sort_order',
                'answers.reviewer:id,name',
            ])
            ->findOrFail($attemptId);

        $this->authorizeView($user, $attempt);

        return $attempt;
    }

    public function buildAttemptAnalytics(PerformanceTestAttempt $attempt): array
    {
        $answers = $attempt->answers
            ->sortBy(fn ($answer) => (int) ($answer->question?->sort_order ?? $answer->question?->id ?? 0))
            ->values();

        $questionRows = $answers->map(function ($answer, int $index): array {
            $question = $answer->question;
            $selectedOption = $answer->selectedOption;
            $correctOptions = $question?->options
                ?->filter(fn ($option) => (bool) $option->is_correct)
                ->pluck('label')
                ->values()
                ->all() ?? [];

            return [
                'index' => $index + 1,
                'question_type' => (string) ($question?->question_type ?? 'open_answer'),
                'prompt' => (string) ($question?->prompt ?? '—'),
                'description' => (string) ($question?->description ?? ''),
                'answer_text' => $question?->isAutoScored()
                    ? (string) ($selectedOption?->label ?? '—')
                    : (string) ($answer->answer_text ?? '—'),
                'correct_answer' => $question?->isAutoScored()
                    ? implode(', ', $correctOptions)
                    : null,
                'is_correct' => $answer->is_correct,
                'auto_score' => $answer->auto_score,
                'review_score' => $answer->review_score,
                'final_score' => $answer->final_score,
                'max_score' => $question?->max_score,
                'review_status' => (string) ($answer->review_status ?? 'pending'),
                'feedback' => (string) ($answer->feedback ?? ''),
                'reviewer_name' => (string) ($answer->reviewer?->name ?? ''),
                'reviewed_at' => $answer->reviewed_at,
            ];
        })->all();

        $timeline = collect([
            [
                'title' => __('performance_evaluation::dashboard.labels.timeline_attempt_started'),
                'meta' => $attempt->started_at,
                'tone' => 'sky',
                'description' => __('performance_evaluation::dashboard.labels.timeline_attempt_started_hint'),
            ],
            [
                'title' => __('performance_evaluation::dashboard.labels.timeline_attempt_submitted'),
                'meta' => $attempt->submitted_at,
                'tone' => 'emerald',
                'description' => __('performance_evaluation::dashboard.labels.timeline_attempt_submitted_hint'),
            ],
        ])
            ->merge($answers
                ->filter(fn ($answer) => $answer->reviewed_at !== null)
                ->map(function ($answer): array {
                    return [
                        'title' => __('performance_evaluation::dashboard.labels.timeline_answer_reviewed', [
                            'question' => (string) ($answer->question?->prompt ?? '#'.$answer->performance_test_question_id),
                        ]),
                        'meta' => $answer->reviewed_at,
                        'tone' => 'amber',
                        'description' => trim(implode(' • ', array_filter([
                            filled($answer->reviewer?->name) ? __('performance_evaluation::dashboard.fields.reviewer').': '.$answer->reviewer->name : null,
                            $answer->feedback ? __('performance_evaluation::dashboard.fields.feedback').': '.$answer->feedback : null,
                        ]))),
                    ];
                }))
            ->filter(fn (array $row) => $row['meta'] !== null)
            ->sortBy('meta')
            ->values()
            ->all();

        return [
            'attempt' => $attempt,
            'question_rows' => $questionRows,
            'timeline' => $timeline,
        ];
    }

    private function authorizeView(User $user, PerformanceTestAttempt $attempt): void
    {
        $isManager = $user->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]);

        if ($isManager || (int) $attempt->session?->reviewer_id === (int) $user->id) {
            return;
        }

        $personnelId = app(UserPersonnelLinkResolver::class)->resolve($user);
        if ($personnelId !== null && (int) $attempt->session?->personnel_id === $personnelId) {
            return;
        }

        throw new AuthorizationException();
    }
}
