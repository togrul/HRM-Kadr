<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithTestWorkspaceAttempts;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithTestWorkspaceResults;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithTestWorkspaceRunner;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithTestWorkspaceSessions;
use Livewire\Component;

class TestWorkspace extends Component
{
    use InteractsWithTestWorkspaceAttempts;
    use InteractsWithTestWorkspaceResults;
    use InteractsWithTestWorkspaceRunner;
    use InteractsWithTestWorkspaceSessions;
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

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.test-workspace');
    }
}
