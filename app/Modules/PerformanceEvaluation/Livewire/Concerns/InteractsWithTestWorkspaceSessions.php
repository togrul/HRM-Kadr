<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceTestSession;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Collection;

trait InteractsWithTestWorkspaceSessions
{
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

    protected function sessionQuery()
    {
        return PerformanceTestSession::query()
            ->where('personnel_id', $this->currentPersonnelId())
            ->with([
                'personnel:id,surname,name,patronymic,tabel_no,email',
                'bank:id,name,code,pass_score,duration_minutes,max_attempts',
                'bank.questions:id,performance_test_bank_id,question_type,prompt,max_score,sort_order,is_active',
                'bank.questions.options:id,performance_test_question_id,label,is_correct,score_value,sort_order',
                'attempts:id,performance_test_session_id,attempt_no,started_at,submitted_at,duration_seconds,score,percentage,passed,status,meta',
                'attempts.answers:id,performance_test_attempt_id,performance_test_question_id,selected_option_id,answer_text,is_correct,auto_score,review_score,final_score,review_status,reviewed_by,reviewed_at,feedback',
                'attempts.answers.reviewer:id,name',
            ]);
    }

    protected function currentPersonnelId(): ?int
    {
        return $this->rememberRuntime('performanceEvaluation.testWorkspace.currentPersonnelId', function (): ?int {
            if ($this->resolvedPersonnelLoaded) {
                return $this->resolvedPersonnelId;
            }

            $user = auth()->user();

            if (! $user) {
                $this->resolvedPersonnelLoaded = true;

                return $this->resolvedPersonnelId = null;
            }

            $sessionKey = 'performanceEvaluation.testWorkspace.personnelId.'.(int) $user->id;
            if (session()->has($sessionKey)) {
                $this->resolvedPersonnelLoaded = true;

                return $this->resolvedPersonnelId = (int) session()->get($sessionKey);
            }

            $personnelId = app(UserPersonnelLinkResolver::class)->resolve($user);
            $this->resolvedPersonnelLoaded = true;
            $this->resolvedPersonnelId = $personnelId;

            if ($personnelId !== null) {
                session()->put($sessionKey, $personnelId);
            }

            return $personnelId;
        });
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
}
