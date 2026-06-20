<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceTestQuestionImportService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use App\Modules\PerformanceEvaluation\Exports\PerformanceTestQuestionImportTemplateExport;
use App\Modules\PerformanceEvaluation\Imports\PerformanceTestQuestionSheetImport;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesPerformanceTestingMutations
{
    public function downloadTestQuestionImportTemplate()
    {
        $this->authorizePerformanceEvaluationManage();

        return Excel::download(
            new PerformanceTestQuestionImportTemplateExport(),
            'performance-test-question-import-template.xlsx'
        );
    }

    public function importTestQuestions(): void
    {
        $this->authorizePerformanceEvaluationManage();

        $validated = $this->validate([
            'testQuestionImportForm.performance_test_bank_id' => 'nullable|exists:performance_test_banks,id',
            'testQuestionImportFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], attributes: [
            'testQuestionImportForm.performance_test_bank_id' => __('performance_evaluation::dashboard.fields.test_bank'),
            'testQuestionImportFile' => __('performance_evaluation::dashboard.fields.import_file'),
        ]);

        $rows = Excel::toArray(new PerformanceTestQuestionSheetImport(), $this->testQuestionImportFile)[0] ?? [];

        $result = app(PerformanceTestQuestionImportService::class)->import(
            $rows,
            data_get($validated, 'testQuestionImportForm.performance_test_bank_id')
                ? (int) data_get($validated, 'testQuestionImportForm.performance_test_bank_id')
                : null
        );

        $this->reset('testQuestionImportFile');
        $this->testQuestionImportForm = $this->testQuestionImportDefaults();
        $this->resetValidation();
        $this->refreshTestsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.test_question_imported', $result));
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
        $this->refreshTestsSummary();
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
        $this->refreshTestsSummary();
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
        $this->refreshTestsSummary();
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
        $this->refreshTestsSummary();
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
        $this->refreshTestsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.attempt_submitted'));
    }

    public function reviewAttemptAnswer(): void
    {
        $this->authorizePerformanceEvaluationReview();
        $validated = $this->validate([
            'reviewForm.performance_test_attempt_answer_id' => 'required|integer',
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
        $this->refreshTestsSummary();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.answer_reviewed'));
    }
}
