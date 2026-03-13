<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;

trait InteractsWithTestWorkspaceAttempts
{
    public function beginAttempt(): void
    {
        $session = $this->selectedSession;
        if ($session === null || ! $this->canWriteSelectedSession) {
            return;
        }

        $this->ensureWritableAttemptInitialized($session);
        $this->syncWorkspaceState($this->reloadSessionSnapshot($session->id));
        $this->bumpRunnerVersion();
    }

    public function saveDraft(): void
    {
        $session = $this->selectedSession;
        if ($session === null || ! $this->canWriteSelectedSession) {
            return;
        }

        $this->persistDraftAnswers($session);
        $this->lastAutoSavedAt = now()->toIso8601String();
        $this->autoSavePending = false;
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.test_draft_saved'));
    }

    public function heartbeat(): void
    {
        $this->flushAutoSave();
        $this->autoSubmitExpiredAttempt();
    }

    public function submitAttempt(): void
    {
        $this->finalizeAttempt(requireCompleteAnswers: true, autoSubmitted: false);
    }

    public function flushAutoSave(): void
    {
        $session = $this->selectedSession;
        if (! $this->autoSavePending || $session === null || ! $this->canWriteSelectedSession) {
            return;
        }

        $this->persistDraftAnswers($session);
        $this->lastAutoSavedAt = now()->toIso8601String();
        $this->autoSavePending = false;
    }

    public function autoSubmitExpiredAttempt(): void
    {
        if (! $this->sessionTimer['expired'] || ! $this->canWriteSelectedSession) {
            return;
        }

        $this->finalizeAttempt(requireCompleteAnswers: false, autoSubmitted: true);
    }

    public function toggleQuestionFlag(int $questionId): void
    {
        $session = $this->selectedSession;
        if ($session === null || ! $session->bank->questions->contains('id', $questionId)) {
            return;
        }

        $isFlagged = (bool) ($this->questionFlags[$questionId] ?? false);
        $this->questionFlags[$questionId] = ! $isFlagged;
        $this->autoSavePending = true;

        $attempt = null;
        if ($this->canBeginAttempt) {
            $attempt = $this->ensureWritableAttemptInitialized($session);
        }

        if ($attempt || $this->activeAttempt) {
            $this->persistAttemptMeta($attempt ?: $this->activeAttempt);
            $this->autoSavePending = false;
            $this->lastAutoSavedAt = now()->toIso8601String();
            $this->syncWorkspaceState($this->reloadSessionSnapshot($session->id));
        }
    }

    public function openQuestion(int $questionId): void
    {
        $session = $this->selectedSession;
        if ($session === null || ! $session->bank->questions->contains('id', $questionId)) {
            return;
        }

        $this->currentQuestionId = $questionId;
    }

    public function goToPreviousQuestion(): void
    {
        $questionIds = $this->questionIds();
        $currentIndex = $questionIds->search($this->currentQuestionId);

        if ($currentIndex === false || $currentIndex <= 0) {
            return;
        }

        $this->currentQuestionId = (int) $questionIds->get($currentIndex - 1);
    }

    public function goToNextQuestion(): void
    {
        $questionIds = $this->questionIds();
        $currentIndex = $questionIds->search($this->currentQuestionId);

        if ($currentIndex === false || $currentIndex >= ($questionIds->count() - 1)) {
            return;
        }

        $this->currentQuestionId = (int) $questionIds->get($currentIndex + 1);
    }

    public function updatedAnswers(): void
    {
        if (! $this->selectedSession) {
            return;
        }

        if ($this->canBeginAttempt) {
            $this->ensureWritableAttemptInitialized($this->selectedSession);
        }

        if ($this->canWriteSelectedSession) {
            $this->autoSavePending = true;
        }
    }

    protected function finalizeAttempt(bool $requireCompleteAnswers, bool $autoSubmitted): void
    {
        $session = $this->selectedSession;
        if ($session === null || ! $this->canWriteSelectedSession) {
            return;
        }

        if ($requireCompleteAnswers) {
            $this->validateRequiredAnswers($session);
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        $attempt = $this->persistDraftAnswers($session);
        $this->materializeMissingAnswers($session, $attempt);

        $attempt->update([
            'duration_seconds' => $this->resolveAttemptDurationSeconds($attempt),
        ]);

        app(PerformanceSkillMeasurementService::class)->submitAttempt($attempt);
        $session->fresh()->update(['status' => 'completed']);

        $this->autoSavePending = false;
        $this->lastAutoSavedAt = now()->toIso8601String();

        $freshSession = $this->reloadSessionSnapshot($session->id);
        $this->syncWorkspaceState($freshSession);
        if (! $this->advanceToPreferredSession(exceptSessionId: $freshSession->id)) {
            $this->openSession($freshSession->id);
        }
        $this->bumpRunnerVersion();

        $this->dispatch(
            'performanceEvaluationSaved',
            $autoSubmitted
                ? __('performance_evaluation::dashboard.messages.attempt_auto_submitted')
                : __('performance_evaluation::dashboard.messages.attempt_submitted')
        );
    }

    protected function persistDraftAnswers(PerformanceTestSession $session): PerformanceTestAttempt
    {
        $attempt = $this->resolveWritableAttempt($session);

        foreach ($session->bank->questions as $question) {
            $payload = data_get($this->answers, $question->id, []);
            $selectedOptionId = data_get($payload, 'selected_option_id');
            $answerText = trim((string) data_get($payload, 'answer_text', ''));

            if ($question->isAutoScored()) {
                if (blank($selectedOptionId)) {
                    continue;
                }
            } elseif ($answerText === '') {
                continue;
            }

            PerformanceTestAttemptAnswer::query()->updateOrCreate(
                [
                    'performance_test_attempt_id' => $attempt->id,
                    'performance_test_question_id' => $question->id,
                ],
                [
                    'selected_option_id' => $question->isAutoScored() ? $selectedOptionId : null,
                    'answer_text' => $question->isAutoScored() ? null : $answerText,
                    'review_status' => $question->isAutoScored() ? 'auto_ready' : 'pending',
                ]
            );
        }

        if ($session->status === 'assigned') {
            $session->update(['status' => 'in_progress']);
        }

        $this->persistAttemptMeta($attempt);
        $this->syncWorkspaceState($this->reloadSessionSnapshot($session->id));

        return $attempt->fresh(['answers']);
    }

    protected function materializeMissingAnswers(PerformanceTestSession $session, PerformanceTestAttempt $attempt): void
    {
        foreach ($session->bank->questions as $question) {
            PerformanceTestAttemptAnswer::query()->firstOrCreate(
                [
                    'performance_test_attempt_id' => $attempt->id,
                    'performance_test_question_id' => $question->id,
                ],
                [
                    'selected_option_id' => null,
                    'answer_text' => null,
                    'review_status' => $question->isAutoScored() ? 'auto_ready' : 'pending',
                ]
            );
        }
    }

    protected function validateRequiredAnswers(PerformanceTestSession $session): void
    {
        foreach ($session->bank->questions as $question) {
            $payload = data_get($this->answers, $question->id, []);

            if ($question->isAutoScored() && blank(data_get($payload, 'selected_option_id'))) {
                $this->addError("answers.{$question->id}.selected_option_id", __('performance_evaluation::dashboard.validation.option_required'));
            }

            if (! $question->isAutoScored() && trim((string) data_get($payload, 'answer_text', '')) === '') {
                $this->addError("answers.{$question->id}.answer_text", __('performance_evaluation::dashboard.validation.answer_text_required'));
            }
        }
    }

    protected function persistAttemptMeta(PerformanceTestAttempt $attempt): void
    {
        $attempt->update([
            'meta' => [
                'flagged_question_ids' => collect($this->questionFlags)
                    ->filter(fn ($flagged) => (bool) $flagged)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ],
        ]);
    }

    protected function resolveWritableAttempt(PerformanceTestSession $session): PerformanceTestAttempt
    {
        $latestAttempt = $session->attempts->sortByDesc('attempt_no')->first();

        if ($latestAttempt && ! in_array($latestAttempt->status, ['completed', 'review_pending'], true)) {
            return $latestAttempt;
        }

        $nextAttemptNo = $latestAttempt ? ($latestAttempt->attempt_no + 1) : 1;

        return PerformanceTestAttempt::query()->create([
            'performance_test_session_id' => $session->id,
            'attempt_no' => $nextAttemptNo,
            'started_at' => now(),
            'status' => 'draft',
            'meta' => [
                'flagged_question_ids' => [],
            ],
        ]);
    }

    protected function ensureWritableAttemptInitialized(PerformanceTestSession $session): PerformanceTestAttempt
    {
        $attempt = $this->resolveWritableAttempt($session);

        if ($attempt->status === 'draft' && blank($attempt->started_at)) {
            $attempt->update(['started_at' => now()]);
        }

        if ($session->status === 'assigned') {
            $session->update(['status' => 'in_progress']);
        }

        $this->resetRuntimeMemo();
        $this->selectedSessionReadOnly = false;

        return $attempt->fresh();
    }

    protected function resolveAttemptDurationSeconds(PerformanceTestAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        return max(0, $attempt->started_at->diffInSeconds(now()));
    }

    protected function bumpRunnerVersion(): void
    {
        $this->runnerVersion++;
    }
}
