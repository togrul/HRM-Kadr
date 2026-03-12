<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceCycle;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceEvaluationReportingService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use App\Modules\PerformanceEvaluation\Exports\PerformanceEvaluationReportExport;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesPerformanceEvaluationMutations
{
    public function storeCycle(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'cycleForm.name' => 'required|string|min:2|max:160',
            'cycleForm.cycle_type' => 'required|in:annual,academic,quarterly',
            'cycleForm.period_start' => 'required|date',
            'cycleForm.period_end' => 'required|date|after_or_equal:cycleForm.period_start',
            'cycleForm.status' => 'required|in:draft,active,closed',
            'cycleForm.auto_generate_forms' => 'nullable|boolean',
            'cycleForm.description' => 'nullable|string|max:1000',
        ], attributes: [
            'cycleForm.name' => __('performance_evaluation::dashboard.fields.cycle_name'),
            'cycleForm.cycle_type' => __('performance_evaluation::dashboard.fields.cycle_type'),
            'cycleForm.period_start' => __('performance_evaluation::dashboard.fields.period_start'),
            'cycleForm.period_end' => __('performance_evaluation::dashboard.fields.period_end'),
            'cycleForm.status' => __('performance_evaluation::dashboard.fields.status'),
            'cycleForm.description' => __('performance_evaluation::dashboard.fields.description'),
        ]);

        $payload = [
            'name' => trim((string) data_get($validated, 'cycleForm.name')),
            'cycle_type' => (string) data_get($validated, 'cycleForm.cycle_type'),
            'period_start' => data_get($validated, 'cycleForm.period_start'),
            'period_end' => data_get($validated, 'cycleForm.period_end'),
            'status' => (string) data_get($validated, 'cycleForm.status'),
            'auto_generate_forms' => (bool) (data_get($validated, 'cycleForm.auto_generate_forms') ?? true),
            'description' => data_get($validated, 'cycleForm.description'),
        ];

        if ($this->editingCycleId) {
            PerformanceCycle::query()->findOrFail($this->editingCycleId)->update($payload);
        } else {
            PerformanceCycle::query()->create($payload);
        }

        $this->reset('cycleForm');
        $this->cycleForm = $this->cycleDefaults();
        $this->editingCycleId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.cycle_saved'));
    }

    public function storeTemplate(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'templateForm.name' => 'required|string|min:2|max:160',
            'templateForm.code' => 'nullable|string|max:40',
            'templateForm.description' => 'nullable|string|max:1000',
            'templateForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'templateForm.name' => __('performance_evaluation::dashboard.fields.template_name'),
            'templateForm.code' => __('performance_evaluation::dashboard.fields.template_code'),
            'templateForm.description' => __('performance_evaluation::dashboard.fields.description'),
        ]);

        $payload = [
            'name' => trim((string) data_get($validated, 'templateForm.name')),
            'code' => blank(data_get($validated, 'templateForm.code')) ? null : trim((string) data_get($validated, 'templateForm.code')),
            'description' => data_get($validated, 'templateForm.description'),
            'is_active' => (bool) (data_get($validated, 'templateForm.is_active') ?? true),
        ];

        if ($this->editingTemplateId) {
            PerformanceFormTemplate::query()->findOrFail($this->editingTemplateId)->update($payload);
        } else {
            PerformanceFormTemplate::query()->create($payload);
        }

        $this->reset('templateForm', 'searchTemplate');
        $this->templateForm = $this->templateDefaults();
        $this->editingTemplateId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.template_saved'));
    }

    public function storeSection(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'sectionForm.performance_form_template_id' => 'required|exists:performance_form_templates,id',
            'sectionForm.name' => 'required|string|min:2|max:160',
            'sectionForm.weight_percent' => 'nullable|numeric|min:0|max:100',
            'sectionForm.sort_order' => 'nullable|integer|min:0',
        ], attributes: [
            'sectionForm.performance_form_template_id' => __('performance_evaluation::dashboard.fields.template'),
            'sectionForm.name' => __('performance_evaluation::dashboard.fields.section_name'),
            'sectionForm.weight_percent' => __('performance_evaluation::dashboard.fields.weight_percent'),
            'sectionForm.sort_order' => __('performance_evaluation::dashboard.fields.sort_order'),
        ]);

        $payload = [
            'performance_form_template_id' => (int) data_get($validated, 'sectionForm.performance_form_template_id'),
            'name' => trim((string) data_get($validated, 'sectionForm.name')),
            'weight_percent' => (float) (data_get($validated, 'sectionForm.weight_percent') ?? 0),
            'sort_order' => (int) (data_get($validated, 'sectionForm.sort_order') ?? 0),
        ];

        if ($this->editingSectionId) {
            PerformanceFormTemplateSection::query()->findOrFail($this->editingSectionId)->update($payload);
        } else {
            PerformanceFormTemplateSection::query()->create($payload);
        }

        $this->reset('sectionForm', 'searchTemplate');
        $this->sectionForm = $this->sectionDefaults();
        $this->editingSectionId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.section_saved'));
    }

    public function storeItem(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'itemForm.performance_form_template_section_id' => 'required|exists:performance_form_template_sections,id',
            'itemForm.training_competency_id' => 'required|exists:training_competencies,id',
            'itemForm.name' => 'required|string|min:2|max:160',
            'itemForm.description' => 'nullable|string|max:1000',
            'itemForm.weight_percent' => 'nullable|numeric|min:0|max:100',
            'itemForm.low_score_threshold' => 'nullable|numeric|min:0|max:100',
            'itemForm.requires_comment' => 'nullable|boolean',
            'itemForm.sort_order' => 'nullable|integer|min:0',
        ], attributes: [
            'itemForm.performance_form_template_section_id' => __('performance_evaluation::dashboard.fields.section'),
            'itemForm.training_competency_id' => __('performance_evaluation::dashboard.fields.competency'),
            'itemForm.name' => __('performance_evaluation::dashboard.fields.item_name'),
            'itemForm.description' => __('performance_evaluation::dashboard.fields.description'),
            'itemForm.weight_percent' => __('performance_evaluation::dashboard.fields.weight_percent'),
            'itemForm.low_score_threshold' => __('performance_evaluation::dashboard.fields.low_score_threshold'),
            'itemForm.sort_order' => __('performance_evaluation::dashboard.fields.sort_order'),
        ]);

        $payload = [
            'performance_form_template_section_id' => (int) data_get($validated, 'itemForm.performance_form_template_section_id'),
            'training_competency_id' => data_get($validated, 'itemForm.training_competency_id'),
            'name' => trim((string) data_get($validated, 'itemForm.name')),
            'description' => data_get($validated, 'itemForm.description'),
            'weight_percent' => (float) (data_get($validated, 'itemForm.weight_percent') ?? 0),
            'low_score_threshold' => (float) (data_get($validated, 'itemForm.low_score_threshold') ?? 60),
            'requires_comment' => (bool) (data_get($validated, 'itemForm.requires_comment') ?? false),
            'sort_order' => (int) (data_get($validated, 'itemForm.sort_order') ?? 0),
        ];

        if ($this->editingItemId) {
            PerformanceFormTemplateItem::query()->findOrFail($this->editingItemId)->update($payload);
        } else {
            PerformanceFormTemplateItem::query()->create($payload);
        }

        $this->reset('itemForm', 'searchSection', 'searchCompetency');
        $this->itemForm = $this->itemDefaults();
        $this->editingItemId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.item_saved'));
    }

    public function storeEvaluationForm(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'evaluationForm.performance_cycle_id' => 'required|exists:performance_cycles,id',
            'evaluationForm.performance_form_template_id' => 'required|exists:performance_form_templates,id',
            'evaluationForm.personnel_id' => 'required|exists:personnels,id',
            'evaluationForm.manager_id' => 'nullable|exists:users,id',
            'evaluationForm.hr_reviewer_id' => 'nullable|exists:users,id',
        ], attributes: [
            'evaluationForm.performance_cycle_id' => __('performance_evaluation::dashboard.fields.cycle'),
            'evaluationForm.performance_form_template_id' => __('performance_evaluation::dashboard.fields.template'),
            'evaluationForm.personnel_id' => __('performance_evaluation::dashboard.fields.personnel'),
            'evaluationForm.manager_id' => __('performance_evaluation::dashboard.fields.manager'),
            'evaluationForm.hr_reviewer_id' => __('performance_evaluation::dashboard.fields.hr_reviewer'),
        ]);

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
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.evaluation_saved'));
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
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved'));

        if ($link === null && (float) data_get($validated, 'scoreForm.score') < (float) ($score->item?->low_score_threshold ?? 60) && empty($score->item?->training_competency_id)) {
            $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved_without_need_link'));
        }
    }

    public function storeTestBank(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'bankForm.name' => 'required|string|min:2|max:160',
            'bankForm.code' => 'nullable|string|max:40',
            'bankForm.description' => 'nullable|string|max:1000',
            'bankForm.pass_score' => 'required|numeric|min:0|max:100',
            'bankForm.duration_minutes' => 'required|integer|min:1|max:1440',
            'bankForm.max_attempts' => 'required|integer|min:1|max:20',
            'bankForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'bankForm.name' => __('performance_evaluation::dashboard.fields.test_bank_name'),
            'bankForm.code' => __('performance_evaluation::dashboard.fields.test_bank_code'),
            'bankForm.pass_score' => __('performance_evaluation::dashboard.fields.pass_score'),
            'bankForm.duration_minutes' => __('performance_evaluation::dashboard.fields.duration_minutes'),
            'bankForm.max_attempts' => __('performance_evaluation::dashboard.fields.max_attempts'),
        ]);

        PerformanceTestBank::query()->create([
            'name' => trim((string) data_get($validated, 'bankForm.name')),
            'code' => blank(data_get($validated, 'bankForm.code')) ? null : trim((string) data_get($validated, 'bankForm.code')),
            'description' => data_get($validated, 'bankForm.description'),
            'pass_score' => (float) data_get($validated, 'bankForm.pass_score'),
            'duration_minutes' => (int) data_get($validated, 'bankForm.duration_minutes'),
            'max_attempts' => (int) data_get($validated, 'bankForm.max_attempts'),
            'is_active' => (bool) (data_get($validated, 'bankForm.is_active') ?? true),
        ]);

        $this->reset('bankForm');
        $this->bankForm = $this->bankDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.test_bank_saved'));
    }

    public function storeTestQuestion(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'questionForm.performance_test_bank_id' => 'required|exists:performance_test_banks,id',
            'questionForm.training_competency_id' => 'nullable|exists:training_competencies,id',
            'questionForm.question_type' => 'required|in:multiple_choice,open_answer,case_study,behavioral',
            'questionForm.prompt' => 'required|string|min:5|max:5000',
            'questionForm.description' => 'nullable|string|max:1000',
            'questionForm.max_score' => 'required|numeric|min:1|max:1000',
            'questionForm.sort_order' => 'nullable|integer|min:0',
            'questionForm.is_active' => 'nullable|boolean',
            'questionForm.options_text' => 'nullable|string|max:5000',
        ], attributes: [
            'questionForm.performance_test_bank_id' => __('performance_evaluation::dashboard.fields.test_bank'),
            'questionForm.training_competency_id' => __('performance_evaluation::dashboard.fields.competency'),
            'questionForm.question_type' => __('performance_evaluation::dashboard.fields.question_type'),
            'questionForm.prompt' => __('performance_evaluation::dashboard.fields.prompt'),
            'questionForm.max_score' => __('performance_evaluation::dashboard.fields.max_score'),
            'questionForm.options_text' => __('performance_evaluation::dashboard.fields.options_text'),
        ]);

        if (data_get($validated, 'questionForm.question_type') === 'multiple_choice' && blank(data_get($validated, 'questionForm.options_text'))) {
            $this->addError('questionForm.options_text', __('performance_evaluation::dashboard.validation.options_required'));

            return;
        }

        $question = PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => (int) data_get($validated, 'questionForm.performance_test_bank_id'),
            'training_competency_id' => data_get($validated, 'questionForm.training_competency_id'),
            'question_type' => (string) data_get($validated, 'questionForm.question_type'),
            'prompt' => trim((string) data_get($validated, 'questionForm.prompt')),
            'description' => data_get($validated, 'questionForm.description'),
            'max_score' => (float) data_get($validated, 'questionForm.max_score'),
            'sort_order' => (int) (data_get($validated, 'questionForm.sort_order') ?? 0),
            'is_active' => (bool) (data_get($validated, 'questionForm.is_active') ?? true),
        ]);

        app(PerformanceSkillMeasurementService::class)->syncQuestionOptions(
            $question,
            (string) data_get($validated, 'questionForm.options_text', '')
        );

        $this->reset('questionForm', 'searchTestBank', 'searchTestCompetency');
        $this->questionForm = $this->questionDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.test_question_saved'));
    }

    public function storeTestSession(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'sessionForm.performance_cycle_id' => 'nullable|exists:performance_cycles,id',
            'sessionForm.performance_test_bank_id' => 'required|exists:performance_test_banks,id',
            'sessionForm.personnel_id' => 'required|exists:personnels,id',
            'sessionForm.reviewer_id' => 'nullable|exists:users,id',
            'sessionForm.scheduled_at' => 'nullable|date',
            'sessionForm.available_until' => 'nullable|date|after_or_equal:sessionForm.scheduled_at',
            'sessionForm.pass_score' => 'nullable|numeric|min:0|max:100',
            'sessionForm.duration_minutes' => 'nullable|integer|min:1|max:1440',
            'sessionForm.max_attempts' => 'nullable|integer|min:1|max:20',
            'sessionForm.status' => 'required|in:assigned,in_progress,completed,closed',
        ], attributes: [
            'sessionForm.performance_cycle_id' => __('performance_evaluation::dashboard.fields.cycle'),
            'sessionForm.performance_test_bank_id' => __('performance_evaluation::dashboard.fields.test_bank'),
            'sessionForm.personnel_id' => __('performance_evaluation::dashboard.fields.personnel'),
            'sessionForm.reviewer_id' => __('performance_evaluation::dashboard.fields.reviewer'),
        ]);

        PerformanceTestSession::query()->create([
            'performance_cycle_id' => data_get($validated, 'sessionForm.performance_cycle_id'),
            'performance_test_bank_id' => (int) data_get($validated, 'sessionForm.performance_test_bank_id'),
            'personnel_id' => (int) data_get($validated, 'sessionForm.personnel_id'),
            'reviewer_id' => data_get($validated, 'sessionForm.reviewer_id'),
            'assigned_by' => auth()->id(),
            'scheduled_at' => data_get($validated, 'sessionForm.scheduled_at'),
            'available_until' => data_get($validated, 'sessionForm.available_until'),
            'pass_score' => data_get($validated, 'sessionForm.pass_score'),
            'duration_minutes' => data_get($validated, 'sessionForm.duration_minutes'),
            'max_attempts' => data_get($validated, 'sessionForm.max_attempts'),
            'status' => (string) data_get($validated, 'sessionForm.status'),
        ]);

        $this->reset('sessionForm', 'searchCycle', 'searchTestBank', 'searchTestPersonnel', 'searchTestReviewer');
        $this->sessionForm = $this->sessionDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.test_session_saved'));
    }

    public function storeAttemptAnswer(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'attemptAnswerForm.performance_test_session_id' => 'required|exists:performance_test_sessions,id',
            'attemptAnswerForm.performance_test_question_id' => 'required|exists:performance_test_questions,id',
            'attemptAnswerForm.attempt_no' => 'required|integer|min:1|max:20',
            'attemptAnswerForm.selected_option_id' => 'nullable|exists:performance_test_question_options,id',
            'attemptAnswerForm.answer_text' => 'nullable|string|max:5000',
        ], attributes: [
            'attemptAnswerForm.performance_test_session_id' => __('performance_evaluation::dashboard.fields.test_session'),
            'attemptAnswerForm.performance_test_question_id' => __('performance_evaluation::dashboard.fields.question'),
            'attemptAnswerForm.attempt_no' => __('performance_evaluation::dashboard.fields.attempt_no'),
        ]);

        $question = PerformanceTestQuestion::query()->with('options:id,performance_test_question_id')->findOrFail((int) data_get($validated, 'attemptAnswerForm.performance_test_question_id'));
        $session = PerformanceTestSession::query()->findOrFail((int) data_get($validated, 'attemptAnswerForm.performance_test_session_id'));

        if ($session->performance_test_bank_id !== $question->performance_test_bank_id) {
            $this->addError('attemptAnswerForm.performance_test_question_id', __('performance_evaluation::dashboard.validation.question_bank_mismatch'));

            return;
        }

        if ($question->isAutoScored()) {
            if (blank(data_get($validated, 'attemptAnswerForm.selected_option_id'))) {
                $this->addError('attemptAnswerForm.selected_option_id', __('performance_evaluation::dashboard.validation.option_required'));

                return;
            }

            $optionBelongsToQuestion = $question->options->contains('id', (int) data_get($validated, 'attemptAnswerForm.selected_option_id'));
            if (! $optionBelongsToQuestion) {
                $this->addError('attemptAnswerForm.selected_option_id', __('performance_evaluation::dashboard.validation.invalid_option'));

                return;
            }
        } elseif (blank(data_get($validated, 'attemptAnswerForm.answer_text'))) {
            $this->addError('attemptAnswerForm.answer_text', __('performance_evaluation::dashboard.validation.answer_text_required'));

            return;
        }

        $attempt = PerformanceTestAttempt::query()->firstOrCreate(
            [
                'performance_test_session_id' => $session->id,
                'attempt_no' => (int) data_get($validated, 'attemptAnswerForm.attempt_no'),
            ],
            [
                'started_at' => now(),
                'status' => 'draft',
            ]
        );

        PerformanceTestAttemptAnswer::query()->updateOrCreate(
            [
                'performance_test_attempt_id' => $attempt->id,
                'performance_test_question_id' => $question->id,
            ],
            [
                'selected_option_id' => $question->isAutoScored() ? data_get($validated, 'attemptAnswerForm.selected_option_id') : null,
                'answer_text' => $question->isAutoScored() ? null : data_get($validated, 'attemptAnswerForm.answer_text'),
                'review_status' => $question->isAutoScored() ? 'auto_ready' : 'pending',
            ]
        );

        $this->attemptSubmitForm['performance_test_attempt_id'] = $attempt->id;
        $this->reset('attemptAnswerForm', 'searchTestSession', 'searchTestQuestion');
        $this->attemptAnswerForm = $this->attemptAnswerDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.attempt_answer_saved'));
    }

    public function finalizeAttempt(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'attemptSubmitForm.performance_test_attempt_id' => 'required|exists:performance_test_attempts,id',
        ], attributes: [
            'attemptSubmitForm.performance_test_attempt_id' => __('performance_evaluation::dashboard.fields.attempt'),
        ]);

        $attempt = PerformanceTestAttempt::query()->findOrFail((int) data_get($validated, 'attemptSubmitForm.performance_test_attempt_id'));
        app(PerformanceSkillMeasurementService::class)->submitAttempt($attempt);

        $this->reset('attemptSubmitForm', 'searchTestAttempt');
        $this->attemptSubmitForm = $this->attemptSubmitDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.attempt_submitted'));
    }

    public function reviewAttemptAnswer(): void
    {
        $this->authorizePerformanceEvaluationReview();
        $validated = $this->validate([
            'reviewForm.performance_test_attempt_answer_id' => 'required|exists:performance_test_attempt_answers,id',
            'reviewForm.score' => 'required|numeric|min:0|max:1000',
            'reviewForm.feedback' => 'nullable|string|max:2000',
        ], attributes: [
            'reviewForm.performance_test_attempt_answer_id' => __('performance_evaluation::dashboard.fields.answer'),
            'reviewForm.score' => __('performance_evaluation::dashboard.fields.review_score'),
            'reviewForm.feedback' => __('performance_evaluation::dashboard.fields.feedback'),
        ]);

        $answer = PerformanceTestAttemptAnswer::query()->findOrFail((int) data_get($validated, 'reviewForm.performance_test_attempt_answer_id'));
        app(PerformanceSkillMeasurementService::class)->reviewAnswer(
            $answer,
            (float) data_get($validated, 'reviewForm.score'),
            data_get($validated, 'reviewForm.feedback'),
            auth()->id()
        );

        $this->reset('reviewForm', 'searchReviewAnswer');
        $this->reviewForm = $this->reviewDefaults();
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.answer_reviewed'));
    }

    public function editCycle(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $cycle = PerformanceCycle::query()->findOrFail($id);
        $this->editingCycleId = $cycle->id;
        $this->cycleForm = [
            'name' => (string) $cycle->name,
            'cycle_type' => (string) $cycle->cycle_type,
            'period_start' => optional($cycle->period_start)->toDateString(),
            'period_end' => optional($cycle->period_end)->toDateString(),
            'status' => (string) $cycle->status,
            'auto_generate_forms' => (bool) $cycle->auto_generate_forms,
            'description' => (string) ($cycle->description ?? ''),
        ];
        $this->resetValidation();
    }

    public function deleteCycle(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceCycle::query()->findOrFail($id)->delete();
        if ($this->editingCycleId === $id) {
            $this->cancelCycleEdit();
        }
    }

    public function editTemplate(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $template = PerformanceFormTemplate::query()->findOrFail($id);
        $this->editingTemplateId = $template->id;
        $this->templateForm = [
            'name' => (string) $template->name,
            'code' => (string) ($template->code ?? ''),
            'description' => (string) ($template->description ?? ''),
            'is_active' => (bool) $template->is_active,
        ];
        $this->resetValidation();
    }

    public function deleteTemplate(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplate::query()->findOrFail($id)->delete();
        if ($this->editingTemplateId === $id) {
            $this->cancelTemplateEdit();
        }
    }

    public function editSection(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $section = PerformanceFormTemplateSection::query()->findOrFail($id);
        $this->editingSectionId = $section->id;
        $this->sectionForm = [
            'performance_form_template_id' => $section->performance_form_template_id,
            'name' => (string) $section->name,
            'weight_percent' => (float) $section->weight_percent,
            'sort_order' => (int) $section->sort_order,
        ];
        $this->resetValidation();
    }

    public function deleteSection(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplateSection::query()->findOrFail($id)->delete();
        if ($this->editingSectionId === $id) {
            $this->cancelSectionEdit();
        }
    }

    public function editItem(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $item = PerformanceFormTemplateItem::query()->findOrFail($id);
        $this->editingItemId = $item->id;
        $this->itemForm = [
            'performance_form_template_section_id' => $item->performance_form_template_section_id,
            'training_competency_id' => $item->training_competency_id,
            'name' => (string) $item->name,
            'description' => (string) ($item->description ?? ''),
            'weight_percent' => (float) $item->weight_percent,
            'low_score_threshold' => (float) $item->low_score_threshold,
            'requires_comment' => (bool) $item->requires_comment,
            'sort_order' => (int) $item->sort_order,
        ];
        $this->resetValidation();
    }

    public function deleteItem(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplateItem::query()->findOrFail($id)->delete();
        if ($this->editingItemId === $id) {
            $this->cancelItemEdit();
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
    }

    public function cancelCycleEdit(): void
    {
        $this->editingCycleId = null;
        $this->cycleForm = $this->cycleDefaults();
        $this->resetValidation();
    }

    public function cancelTemplateEdit(): void
    {
        $this->editingTemplateId = null;
        $this->templateForm = $this->templateDefaults();
        $this->resetValidation();
    }

    public function cancelSectionEdit(): void
    {
        $this->editingSectionId = null;
        $this->sectionForm = $this->sectionDefaults();
        $this->resetValidation();
    }

    public function cancelItemEdit(): void
    {
        $this->editingItemId = null;
        $this->itemForm = $this->itemDefaults();
        $this->resetValidation();
    }

    public function cancelEvaluationEdit(): void
    {
        $this->editingEvaluationFormId = null;
        $this->evaluationForm = $this->evaluationDefaults();
        $this->resetValidation();
    }

    public function exportPerformanceFormsReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->formRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.cycle'),
                    __('performance_evaluation::dashboard.fields.template'),
                    __('performance_evaluation::dashboard.fields.manager'),
                    __('performance_evaluation::dashboard.fields.hr_reviewer'),
                    __('performance_evaluation::dashboard.fields.score'),
                    __('performance_evaluation::dashboard.fields.final_category'),
                    __('performance_evaluation::dashboard.fields.self_status'),
                    __('performance_evaluation::dashboard.fields.manager_status'),
                    __('performance_evaluation::dashboard.fields.hr_status'),
                ],
                'forms'
            ),
            'performance-forms-report.xlsx'
        );
    }

    public function exportPerformanceWeakLinksReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->weakLinkRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.competency'),
                    __('performance_evaluation::dashboard.fields.score'),
                    __('performance_evaluation::dashboard.fields.final_category'),
                    __('performance_evaluation::dashboard.fields.priority'),
                    __('performance_evaluation::dashboard.fields.status'),
                    __('performance_evaluation::dashboard.fields.reason'),
                ],
                'weak_links'
            ),
            'performance-weak-links-report.xlsx'
        );
    }

    public function exportPerformanceAuditReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->auditRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.audit_subject'),
                    __('performance_evaluation::dashboard.fields.audit_subject_id'),
                    __('performance_evaluation::dashboard.fields.audit_event'),
                    __('performance_evaluation::dashboard.fields.audit_actor'),
                    __('performance_evaluation::dashboard.fields.audit_created_at'),
                    __('performance_evaluation::dashboard.fields.audit_properties'),
                ],
                'audit'
            ),
            'performance-audit-report.xlsx'
        );
    }
}
