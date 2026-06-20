<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

trait InteractsWithTestWorkspaceRunner
{
    public function getActiveAttemptProperty(): ?PerformanceTestAttempt
    {
        $session = $this->selectedSession;
        if (! $session) {
            return null;
        }

        $latestAttempt = $session->attempts
            ->sortByDesc('attempt_no')
            ->first();

        if ($latestAttempt && ! in_array($latestAttempt->status, ['completed', 'review_pending'], true)) {
            return $latestAttempt;
        }

        return null;
    }

    public function getAttemptHistoryProperty(): Collection
    {
        $session = $this->selectedSession;
        if (! $session) {
            return collect();
        }

        return $session->attempts
            ->sortByDesc('attempt_no')
            ->values();
    }

    public function getCurrentQuestionProperty(): ?PerformanceTestQuestion
    {
        $session = $this->selectedSession;
        if (! $session || ! $this->currentQuestionId) {
            return null;
        }

        return $session->bank->questions->firstWhere('id', $this->currentQuestionId);
    }

    public function getAttemptProgressProperty(): array
    {
        $session = $this->selectedSession;
        if (! $session) {
            return ['answered' => 0, 'total' => 0];
        }

        $total = $session->bank->questions->count();
        $answered = $session->bank->questions
            ->filter(fn (PerformanceTestQuestion $question): bool => $this->questionIsAnswered($question))
            ->count();

        return ['answered' => $answered, 'total' => $total];
    }

    public function getQuestionNavigationProperty(): Collection
    {
        $session = $this->selectedSession;
        if (! $session) {
            return collect();
        }

        return $session->bank->questions
            ->values()
            ->map(function (PerformanceTestQuestion $question, int $index): array {
                return [
                    'id' => $question->id,
                    'index' => $index + 1,
                    'answered' => $this->questionIsAnswered($question),
                    'flagged' => (bool) ($this->questionFlags[$question->id] ?? false),
                    'active' => $this->currentQuestionId === $question->id,
                ];
            });
    }

    public function getSessionTimerProperty(): array
    {
        $session = $this->selectedSession;
        if (! $session) {
            return $this->emptyTimerPayload();
        }

        $durationMinutes = (int) ($session->duration_minutes ?: $session->bank?->duration_minutes ?: 0);
        $attempt = $this->activeAttempt ?: $this->attemptHistory->first();

        if ($durationMinutes <= 0) {
            return [
                'attempt_id' => $attempt?->id,
                'duration_minutes' => null,
                'remaining_seconds' => null,
                'started_at' => $attempt?->started_at?->toIso8601String(),
                'ends_at' => null,
                'started' => filled($attempt?->started_at),
                'expired' => false,
                'finished' => in_array($attempt?->status, ['completed', 'review_pending'], true),
            ];
        }

        if ($attempt === null || ! $attempt->started_at) {
            return [
                'attempt_id' => null,
                'duration_minutes' => $durationMinutes,
                'remaining_seconds' => $durationMinutes * 60,
                'started_at' => null,
                'ends_at' => null,
                'started' => false,
                'expired' => false,
                'finished' => false,
            ];
        }

        $finished = in_array($attempt->status, ['completed', 'review_pending'], true);
        if ($finished) {
            return [
                'attempt_id' => $attempt->id,
                'duration_minutes' => $durationMinutes,
                'remaining_seconds' => 0,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'ends_at' => $attempt->submitted_at?->toIso8601String(),
                'started' => true,
                'expired' => false,
                'finished' => true,
            ];
        }

        $endsAt = CarbonImmutable::instance($attempt->started_at)->addMinutes($durationMinutes);
        $remaining = max(0, now()->diffInSeconds($endsAt, false));

        return [
            'attempt_id' => $attempt->id,
            'duration_minutes' => $durationMinutes,
            'remaining_seconds' => $remaining,
            'started_at' => $attempt->started_at->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            'started' => true,
            'expired' => $remaining === 0,
            'finished' => false,
        ];
    }

    public function getHasNoRemainingAttemptsProperty(): bool
    {
        $session = $this->selectedSession;
        if (! $session) {
            return true;
        }

        return ! $this->sessionHasRemainingAttempts($session);
    }

    public function getCanBeginAttemptProperty(): bool
    {
        $session = $this->selectedSession;
        if (! $session) {
            return false;
        }

        return $this->activeAttempt === null
            && $this->sessionHasRemainingAttempts($session)
            && ! in_array($session->status, ['closed', 'completed'], true);
    }

    public function getCanWriteSelectedSessionProperty(): bool
    {
        $session = $this->selectedSession;
        if (! $session) {
            return false;
        }

        if (in_array($session->status, ['completed', 'closed'], true)) {
            return false;
        }

        return $this->activeAttempt !== null || $this->canBeginAttempt;
    }

    public function getSelectedSessionIsReadOnlyProperty(): bool
    {
        return $this->selectedSessionReadOnly;
    }

    public function getHasNextActionableSessionProperty(): bool
    {
        return $this->preferredSessionId($this->selectedSessionId) !== null;
    }

    public function openNextActionableSession(): void
    {
        $this->advanceToPreferredSession($this->selectedSessionId);
    }

    protected function syncWorkspaceState(PerformanceTestSession $session): void
    {
        $this->hydrateAnswersFromAttempt($session);
        $this->hydrateFlagsFromAttempt($session);
        $this->autoSavePending = false;
        $latestAttempt = $this->latestRelevantAttempt($session);
        $this->selectedSessionReadOnly =
            in_array($session->status, ['completed', 'closed'], true)
            || (
                $latestAttempt !== null
                && in_array($latestAttempt->status, ['completed', 'review_pending'], true)
                && ! $this->sessionHasRemainingAttempts($session)
            );

        $questionIds = $session->bank->questions->pluck('id')->values();
        if ($questionIds->isEmpty()) {
            $this->currentQuestionId = null;

            return;
        }

        if ($this->currentQuestionId && $questionIds->contains($this->currentQuestionId)) {
            return;
        }

        $firstFlagged = $questionIds->first(fn ($id) => (bool) ($this->questionFlags[$id] ?? false));
        if ($firstFlagged) {
            $this->currentQuestionId = (int) $firstFlagged;

            return;
        }

        $firstUnanswered = $questionIds->first(function ($id) use ($session): bool {
            $question = $session->bank->questions->firstWhere('id', $id);

            return $question instanceof PerformanceTestQuestion
                ? ! $this->questionIsAnswered($question)
                : false;
        });

        $this->currentQuestionId = (int) ($firstUnanswered ?: $questionIds->first());
    }

    protected function hydrateAnswersFromAttempt(PerformanceTestSession $session): void
    {
        $attempt = $this->latestRelevantAttempt($session);
        $payload = [];

        foreach ($session->bank->questions as $question) {
            $answer = $attempt?->answers?->firstWhere('performance_test_question_id', $question->id);
            $payload[$question->id] = [
                'selected_option_id' => $answer?->selected_option_id,
                'answer_text' => (string) ($answer?->answer_text ?? ''),
            ];
        }

        $this->answers = $payload;
    }

    protected function hydrateFlagsFromAttempt(PerformanceTestSession $session): void
    {
        $attempt = $this->latestRelevantAttempt($session);
        $flaggedQuestionIds = collect(data_get($attempt?->meta, 'flagged_question_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id);

        $this->questionFlags = $session->bank->questions
            ->mapWithKeys(fn (PerformanceTestQuestion $question) => [
                $question->id => $flaggedQuestionIds->contains($question->id),
            ])
            ->all();
    }

    protected function questionIds(): Collection
    {
        return $this->selectedSession?->bank?->questions?->pluck('id')->values() ?? collect();
    }

    protected function questionPayload(int $questionId): array
    {
        return data_get($this->answers, $questionId, []);
    }

    protected function questionIsAnswered(PerformanceTestQuestion $question): bool
    {
        $payload = $this->questionPayload($question->id);

        return $question->isAutoScored()
            ? filled(data_get($payload, 'selected_option_id'))
            : trim((string) data_get($payload, 'answer_text', '')) !== '';
    }

    protected function emptyTimerPayload(): array
    {
        return [
            'attempt_id' => null,
            'duration_minutes' => null,
            'remaining_seconds' => null,
            'started_at' => null,
            'ends_at' => null,
            'started' => false,
            'expired' => false,
            'finished' => false,
        ];
    }

    protected function latestRelevantAttempt(PerformanceTestSession $session): ?PerformanceTestAttempt
    {
        $latestAttempt = $session->attempts->sortByDesc('attempt_no')->first();

        if ($latestAttempt && ! in_array($latestAttempt->status, ['completed', 'review_pending'], true)) {
            return $latestAttempt;
        }

        return $session->attempts
            ->sortByDesc('attempt_no')
            ->first();
    }
}
