<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithEvaluatorScoreCaptureQueries;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithEvaluatorWorkspaceScoreForm;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

#[Isolate]
class EvaluatorScoreCapture extends Component
{
    use InteractsWithEvaluatorScoreCaptureQueries;
    use InteractsWithEvaluatorWorkspaceScoreForm;
    use WithRuntimeMemo;

    public array $scoreForm = [
        'performance_form_id' => null,
        'performance_form_template_item_id' => null,
        'evaluator_type' => 'manager',
        'score' => null,
        'comment' => '',
    ];

    #[Reactive]
    public array $formCatalog = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]), 403);
    }

    #[On('performance-evaluation:open-score-capture')]
    public function handleOpenScoreCapture(int $formId): void
    {
        $this->startScoreForm($formId);
    }

    public function saveAssignedScore(): void
    {
        $validated = $this->validate([
            'scoreForm.performance_form_id' => 'required|exists:performance_forms,id',
            'scoreForm.performance_form_template_item_id' => 'required|exists:performance_form_template_items,id',
            'scoreForm.evaluator_type' => 'required|in:manager,hr',
            'scoreForm.score' => 'required|numeric|min:0|max:100',
            'scoreForm.comment' => 'nullable|string|max:1000',
        ], attributes: [
            'scoreForm.performance_form_id' => __('performance_evaluation::dashboard.fields.evaluation_form'),
            'scoreForm.performance_form_template_item_id' => __('performance_evaluation::dashboard.fields.item'),
            'scoreForm.evaluator_type' => __('performance_evaluation::dashboard.fields.evaluator_type'),
            'scoreForm.score' => __('performance_evaluation::dashboard.fields.score'),
            'scoreForm.comment' => __('performance_evaluation::dashboard.fields.comment'),
        ]);

        $form = PerformanceForm::query()->findOrFail((int) data_get($validated, 'scoreForm.performance_form_id'));
        $evaluatorType = (string) data_get($validated, 'scoreForm.evaluator_type');

        abort_unless(
            ($evaluatorType === 'manager' && (int) $form->manager_id === (int) auth()->id()) ||
            ($evaluatorType === 'hr' && (int) $form->hr_reviewer_id === (int) auth()->id()) ||
            auth()->user()?->can('manage-performance-evaluation'),
            403
        );

        $score = PerformanceFormScore::query()->updateOrCreate(
            [
                'performance_form_id' => $form->id,
                'performance_form_template_item_id' => (int) data_get($validated, 'scoreForm.performance_form_template_item_id'),
                'evaluator_type' => $evaluatorType,
            ],
            [
                'score' => (float) data_get($validated, 'scoreForm.score'),
                'comment' => data_get($validated, 'scoreForm.comment'),
            ]
        );

        $column = $evaluatorType === 'manager' ? 'manager_status' : 'hr_status';
        $form->update([$column => 'submitted']);

        $service = app(PerformanceWeakAreaTrainingNeedService::class);
        $form = $form->fresh();
        $service->refreshFormResult($form);
        $service->syncForForm($form->fresh(['scores.item:id,training_competency_id,low_score_threshold,name', 'scores.form.personnel:id,position_id']))
            ->get($score->id);

        $this->scoreForm = [
            'performance_form_id' => null,
            'performance_form_template_item_id' => null,
            'evaluator_type' => 'manager',
            'score' => null,
            'comment' => '',
        ];
        $this->resetRuntimeMemo();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved'));
        $this->dispatch('performance-evaluation:score-capture-mutated');
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.evaluator-score-capture');
    }
}
