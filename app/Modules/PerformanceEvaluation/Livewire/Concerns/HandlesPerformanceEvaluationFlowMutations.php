<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use Illuminate\Validation\ValidationException;

trait HandlesPerformanceEvaluationFlowMutations
{
    public function storeEvaluationForm(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'evaluationForm.performance_cycle_id' => 'required|exists:performance_cycles,id',
            'evaluationForm.performance_form_template_id' => 'required|exists:performance_form_templates,id',
            'evaluationForm.personnel_id' => 'required|exists:personnels,id',
            'evaluationForm.manager_id' => 'nullable|integer',
            'evaluationForm.hr_reviewer_id' => 'nullable|integer',
        ], attributes: [
            'evaluationForm.performance_cycle_id' => __('performance_evaluation::dashboard.fields.cycle'),
            'evaluationForm.performance_form_template_id' => __('performance_evaluation::dashboard.fields.template'),
            'evaluationForm.personnel_id' => __('performance_evaluation::dashboard.fields.personnel'),
            'evaluationForm.manager_id' => __('performance_evaluation::dashboard.fields.manager'),
            'evaluationForm.hr_reviewer_id' => __('performance_evaluation::dashboard.fields.hr_reviewer'),
        ]);

        $this->guardEvaluatorUsersExist($validated);

        $payload = [
            'performance_cycle_id' => (int) data_get($validated, 'evaluationForm.performance_cycle_id'),
            'performance_form_template_id' => (int) data_get($validated, 'evaluationForm.performance_form_template_id'),
            'personnel_id' => (int) data_get($validated, 'evaluationForm.personnel_id'),
            'manager_id' => data_get($validated, 'evaluationForm.manager_id'),
            'hr_reviewer_id' => data_get($validated, 'evaluationForm.hr_reviewer_id'),
        ];

        if ($this->editingEvaluationFormId) {
            PerformanceForm::query()->findOrFail($this->editingEvaluationFormId)->update($payload);
        } else {
            PerformanceForm::query()->updateOrCreate(
                [
                    'performance_cycle_id' => $payload['performance_cycle_id'],
                    'performance_form_template_id' => $payload['performance_form_template_id'],
                    'personnel_id' => $payload['personnel_id'],
                ],
                [
                    'manager_id' => $payload['manager_id'],
                    'hr_reviewer_id' => $payload['hr_reviewer_id'],
                ]
            );
        }

        $this->reset('evaluationForm', 'searchCycle', 'searchTemplate', 'searchPersonnel', 'searchManager', 'searchHrReviewer');
        $this->evaluationForm = $this->evaluationDefaults();
        $this->editingEvaluationFormId = null;
        $this->resetValidation();
        $this->refreshEvaluationsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.evaluation_saved'));
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    protected function guardEvaluatorUsersExist(array $validated): void
    {
        $evaluatorMap = [
            'evaluationForm.manager_id' => data_get($validated, 'evaluationForm.manager_id'),
            'evaluationForm.hr_reviewer_id' => data_get($validated, 'evaluationForm.hr_reviewer_id'),
        ];

        $ids = collect($evaluatorMap)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $existingIds = User::query()
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missingIds = $ids->diff($existingIds)->all();
        if ($missingIds === []) {
            return;
        }

        $messages = [];

        foreach ($evaluatorMap as $field => $value) {
            if (filled($value) && in_array((int) $value, $missingIds, true)) {
                $messages[$field] = __('validation.exists', [
                    'attribute' => __(
                        $field === 'evaluationForm.manager_id'
                            ? 'performance_evaluation::dashboard.fields.manager'
                            : 'performance_evaluation::dashboard.fields.hr_reviewer'
                    ),
                ]);
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function storeScore(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'scoreForm.performance_form_id' => 'required|exists:performance_forms,id',
            'scoreForm.performance_form_template_item_id' => 'required|exists:performance_form_template_items,id',
            'scoreForm.evaluator_type' => 'required|in:self,manager,hr',
            'scoreForm.score' => 'required|numeric|min:0|max:100',
            'scoreForm.comment' => 'nullable|string|max:1000',
        ], attributes: [
            'scoreForm.performance_form_id' => __('performance_evaluation::dashboard.fields.evaluation_form'),
            'scoreForm.performance_form_template_item_id' => __('performance_evaluation::dashboard.fields.item'),
            'scoreForm.evaluator_type' => __('performance_evaluation::dashboard.fields.evaluator_type'),
            'scoreForm.score' => __('performance_evaluation::dashboard.fields.score'),
            'scoreForm.comment' => __('performance_evaluation::dashboard.fields.comment'),
        ]);

        $score = PerformanceFormScore::query()->updateOrCreate(
            [
                'performance_form_id' => (int) data_get($validated, 'scoreForm.performance_form_id'),
                'performance_form_template_item_id' => (int) data_get($validated, 'scoreForm.performance_form_template_item_id'),
                'evaluator_type' => (string) data_get($validated, 'scoreForm.evaluator_type'),
            ],
            [
                'score' => (float) data_get($validated, 'scoreForm.score'),
                'comment' => data_get($validated, 'scoreForm.comment'),
            ]
        );

        $form = PerformanceForm::query()->findOrFail((int) data_get($validated, 'scoreForm.performance_form_id'));
        $this->markEvaluatorStatus($form, (string) data_get($validated, 'scoreForm.evaluator_type'));

        $service = app(PerformanceWeakAreaTrainingNeedService::class);
        $form = $form->fresh();
        $service->refreshFormResult($form);
        $link = $service
            ->syncForForm($form->fresh(['scores.item:id,training_competency_id,low_score_threshold,name', 'scores.form.personnel:id,position_id']))
            ->get($score->id);

        $this->reset('scoreForm', 'searchPerformanceForm', 'searchTemplateItem');
        $this->scoreForm = $this->scoreDefaults();
        $this->resetValidation();
        $this->refreshEvaluationsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved'));

        if ($link === null && (float) data_get($validated, 'scoreForm.score') < (float) ($score->item?->low_score_threshold ?? 60) && empty($score->item?->training_competency_id)) {
            $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved_without_need_link'));
        }
    }

    public function editEvaluationForm(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $form = PerformanceForm::query()->findOrFail($id);
        $this->editingEvaluationFormId = $form->id;
        $this->evaluationForm = [
            'performance_cycle_id' => $form->performance_cycle_id,
            'performance_form_template_id' => $form->performance_form_template_id,
            'personnel_id' => $form->personnel_id,
            'manager_id' => $form->manager_id,
            'hr_reviewer_id' => $form->hr_reviewer_id,
        ];
        $this->resetValidation();
    }

    public function deleteEvaluationForm(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceForm::query()->findOrFail($id)->delete();
        if ($this->editingEvaluationFormId === $id) {
            $this->cancelEvaluationEdit();
        }

        $this->refreshEvaluationsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.evaluation_deleted'));
    }

    public function cancelEvaluationEdit(): void
    {
        $this->editingEvaluationFormId = null;
        $this->evaluationForm = $this->evaluationDefaults();
        $this->resetValidation();
    }
}
