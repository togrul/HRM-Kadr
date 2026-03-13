<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\PerformanceTestAttemptAnswer;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithEvaluatorWorkspaceQueries;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class EvaluatorWorkspace extends Component
{
    use WithRuntimeMemo;
    use InteractsWithEvaluatorWorkspaceQueries;

    public string $searchAssignedForms = '';

    public string $assignedRoleFilter = 'all';

    public string $assignedStatusFilter = 'all';

    public string $searchPendingAnswers = '';

    public string $pendingQuestionTypeFilter = 'all';

    public array $reviewForm = [
        'performance_test_attempt_answer_id' => null,
        'score' => null,
        'feedback' => '',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]), 403);
    }

    public function openScoreCapture(int $formId): void
    {
        $this->dispatch('performance-evaluation:open-score-capture', formId: $formId);
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'searchAssignedForms',
            'assignedRoleFilter',
            'assignedStatusFilter',
            'searchPendingAnswers',
            'pendingQuestionTypeFilter',
        ], true)) {
            $this->resetRuntimeMemo();
        }
    }

    public function startReviewAnswer(int $answerId): void
    {
        $answer = $this->pendingAnswers->firstWhere('id', $answerId);
        abort_if($answer === null, 403);

        $this->reviewForm = [
            'performance_test_attempt_answer_id' => $answer->id,
            'score' => $answer->question_max_score,
            'feedback' => '',
        ];
    }

    public function saveAnswerReview(): void
    {
        $validated = $this->validate([
            'reviewForm.performance_test_attempt_answer_id' => 'required|integer',
            'reviewForm.score' => 'required|numeric|min:0|max:1000',
            'reviewForm.feedback' => 'nullable|string|max:2000',
        ], attributes: [
            'reviewForm.performance_test_attempt_answer_id' => __('performance_evaluation::dashboard.fields.answer'),
            'reviewForm.score' => __('performance_evaluation::dashboard.fields.review_score'),
            'reviewForm.feedback' => __('performance_evaluation::dashboard.fields.feedback'),
        ]);

        $answerId = (int) data_get($validated, 'reviewForm.performance_test_attempt_answer_id');
        abort_if(! $this->pendingAnswers->contains('id', $answerId), 403);

        $answer = PerformanceTestAttemptAnswer::query()->findOrFail($answerId);

        app(PerformanceSkillMeasurementService::class)->reviewAnswer(
            $answer,
            (float) data_get($validated, 'reviewForm.score'),
            data_get($validated, 'reviewForm.feedback'),
            auth()->id()
        );

        $this->reviewForm = [
            'performance_test_attempt_answer_id' => null,
            'score' => null,
            'feedback' => '',
        ];
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.answer_reviewed'));
    }

    #[On('performance-evaluation:score-capture-mutated')]
    public function handleScoreCaptureMutated(): void
    {
        $this->resetRuntimeMemo();
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.evaluator-workspace');
    }
}
