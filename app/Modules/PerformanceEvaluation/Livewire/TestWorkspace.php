<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
use App\Models\Personnel;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class TestWorkspace extends Component
{
    use WithRuntimeMemo;

    public ?int $selectedSessionId = null;

    public ?int $currentQuestionId = null;

    public array $answers = [];

    public array $questionFlags = [];

    public bool $autoSavePending = false;

    public ?string $lastAutoSavedAt = null;

    public int $runnerVersion = 0;

    public bool $selectedSessionReadOnly = true;

    public bool $resolvedPersonnelLoaded = false;

    public ?int $resolvedPersonnelId = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $preferredSessionId = $this->preferredSessionId();
        if ($preferredSessionId !== null) {
            $this->openSession($preferredSessionId);
        }
    }

    public function openSession(int $sessionId): void
    {
        $session = $this->resolveSession($sessionId);

        $this->selectedSessionId = $session->id;
        $this->syncWorkspaceState($session);
        $this->resetValidation();
        $this->bumpRunnerVersion();
    }

    public function beginAttempt(): void
    {
        $session = $this->selectedSession;
        if ($session === null) {
            return;
        }

        if (! $this->canWriteSelectedSession) {
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

    public function getAssignedSessionsProperty(): Collection
    {
        $personnelId = $this->currentPersonnelId();
        if (! $personnelId) {
            return collect();
        }

        return $this->sessionCatalog
            ->sortByDesc(fn (PerformanceTestSession $session) => $this->sessionPriority($session))
            ->values();
    }

    public function getSelectedSessionProperty(): ?PerformanceTestSession
    {
        if (! $this->selectedSessionId) {
            return null;
        }

        return $this->sessionCatalog->firstWhere('id', $this->selectedSessionId);
    }

    public function getSessionCatalogProperty(): Collection
    {
        $personnelId = $this->currentPersonnelId();
        if (! $personnelId) {
            return collect();
        }

        return $this->rememberRuntime('performanceEvaluation.testWorkspace.sessionCatalog.'.$personnelId, function (): Collection {
            return $this->sessionQuery()->get();
        });
    }

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

    protected function sessionQuery()
    {
        return PerformanceTestSession::query()
            ->where('personnel_id', $this->currentPersonnelId())
            ->with([
                'personnel:id,surname,name,patronymic,tabel_no,email',
                'bank:id,name,code,pass_score,duration_minutes,max_attempts',
                'bank.questions:id,performance_test_bank_id,question_type,prompt,max_score,sort_order,is_active',
                'bank.questions.options:id,performance_test_question_id,label',
                'attempts:id,performance_test_session_id,attempt_no,started_at,submitted_at,duration_seconds,score,percentage,passed,status,meta',
                'attempts.answers:id,performance_test_attempt_id,performance_test_question_id,selected_option_id,answer_text',
            ]);
    }

    protected function currentPersonnelId(): ?int
    {
        if ($this->resolvedPersonnelLoaded) {
            return $this->resolvedPersonnelId;
        }

        $user = auth()->user();

        if (! $user) {
            $this->resolvedPersonnelLoaded = true;

            return $this->resolvedPersonnelId = null;
        }

        $sessionKey = 'performanceEvaluation.testWorkspace.personnelId.'.(int) $user->id;
        $cachedPersonnelId = session()->get($sessionKey);
        if (filled($cachedPersonnelId)) {
            $this->resolvedPersonnelLoaded = true;

            return $this->resolvedPersonnelId = (int) $cachedPersonnelId;
        }

        $normalizedEmail = Str::lower(trim((string) $user->email));
        if ($normalizedEmail !== '') {
            $personnelId = Personnel::query()
                ->active()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->orderBy('id')
                ->value('id');

            if ($personnelId) {
                session()->put($sessionKey, (int) $personnelId);
                $this->resolvedPersonnelLoaded = true;

                return $this->resolvedPersonnelId = (int) $personnelId;
            }
        }

        $nameTokens = collect(preg_split('/\s+/', trim((string) $user->name)) ?: [])
            ->filter()
            ->values();

        if ($nameTokens->count() < 2) {
            return $this->resolvedPersonnelId = null;
        }

        $firstToken = Str::lower((string) $nameTokens->first());
        $lastToken = Str::lower((string) $nameTokens->last());

        $candidates = Personnel::query()
            ->active()
            ->select('id', 'added_by')
            ->where(function ($query) use ($firstToken, $lastToken): void {
                $query
                    ->where(function ($match) use ($firstToken, $lastToken): void {
                        $match
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$firstToken])
                            ->whereRaw('LOWER(TRIM(surname)) = ?', [$lastToken]);
                    })
                    ->orWhere(function ($match) use ($firstToken, $lastToken): void {
                        $match
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$lastToken])
                            ->whereRaw('LOWER(TRIM(surname)) = ?', [$firstToken]);
                    });
            })
            ->get();

        if ($candidates->count() === 1) {
            session()->put($sessionKey, (int) $candidates->first()->id);
            $this->resolvedPersonnelLoaded = true;

            return $this->resolvedPersonnelId = (int) $candidates->first()->id;
        }

        $ownedCandidate = $candidates
            ->where('added_by', $user->id)
            ->values();

        if ($ownedCandidate->count() === 1) {
            session()->put($sessionKey, (int) $ownedCandidate->first()->id);
            $this->resolvedPersonnelLoaded = true;

            return $this->resolvedPersonnelId = (int) $ownedCandidate->first()->id;
        }

        $this->resolvedPersonnelLoaded = true;

        return $this->resolvedPersonnelId = null;
    }

    protected function preferredSessionId(?int $exceptSessionId = null): ?int
    {
        $session = $this->assignedSessions
            ->filter(fn (PerformanceTestSession $item) => $exceptSessionId === null || $item->id !== $exceptSessionId)
            ->sortByDesc(fn (PerformanceTestSession $item) => $this->sessionPriority($item))
            ->first();

        return $session?->id;
    }

    protected function advanceToPreferredSession(?int $exceptSessionId = null): bool
    {
        $preferredSessionId = $this->preferredSessionId($exceptSessionId);
        if ($preferredSessionId !== null) {
            $this->openSession($preferredSessionId);
            return true;
        }

        return false;
    }

    protected function sessionPriority(PerformanceTestSession $session): int
    {
        if ($session->status === 'in_progress' && $this->sessionHasRemainingAttempts($session)) {
            return 500000 + $session->id;
        }

        if ($session->status === 'assigned' && $this->sessionHasRemainingAttempts($session)) {
            return 400000 + $session->id;
        }

        if ($session->status === 'completed') {
            return 100000 + $session->id;
        }

        return $session->id;
    }

    protected function sessionHasRemainingAttempts(PerformanceTestSession $session): bool
    {
        $maxAttempts = (int) ($session->max_attempts ?: $session->bank->max_attempts ?: 1);
        $lastAttempt = $session->attempts->sortByDesc('attempt_no')->first();

        if ($lastAttempt === null) {
            return true;
        }

        if (! in_array($lastAttempt->status, ['completed', 'review_pending'], true)) {
            return true;
        }

        return $lastAttempt->attempt_no < $maxAttempts;
    }

    protected function resolveAttemptDurationSeconds(PerformanceTestAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        return max(0, $attempt->started_at->diffInSeconds(now()));
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

    protected function bumpRunnerVersion(): void
    {
        $this->runnerVersion++;
    }

    protected function resolveSession(int $sessionId): PerformanceTestSession
    {
        $session = $this->sessionCatalog->firstWhere('id', $sessionId);

        if ($session instanceof PerformanceTestSession) {
            return $session;
        }

        return $this->sessionQuery()->findOrFail($sessionId);
    }

    protected function reloadSessionSnapshot(int $sessionId): PerformanceTestSession
    {
        $this->resetRuntimeMemo();

        return $this->resolveSession($sessionId);
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

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.test-workspace');
    }
}
