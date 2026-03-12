<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;

trait HandlesTrainingFeedbackMutations
{
    public function editFeedbackForm(int $id): void
    {
        $this->authorizeTrainingNeedsManage();

        $form = TrainingFeedbackForm::query()->findOrFail($id);

        $this->editingFeedbackFormId = $form->id;
        $this->feedbackForm = [
            'training_session_id' => $form->training_session_id,
            'title' => (string) $form->title,
            'status' => (string) $form->status,
            'default_question_type' => (string) data_get($form->questions, '0.type', 'rating'),
            'questions_text' => collect($form->questions ?? [])
                ->map(fn (array $question): string => trim((string) data_get($question, 'text', data_get($question, 'prompt', ''))))
                ->filter()
                ->implode(PHP_EOL),
        ];
    }

    public function cancelFeedbackFormEdit(): void
    {
        $this->editingFeedbackFormId = null;
        $this->feedbackForm = $this->feedbackFormDefaults();
        $this->resetValidation();
    }

    public function storeFeedbackForm(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'feedbackForm.training_session_id' => 'required|exists:training_sessions,id',
            'feedbackForm.title' => 'required|string|min:2|max:160',
            'feedbackForm.status' => 'required|in:draft,open,closed',
            'feedbackForm.default_question_type' => 'required|in:rating,text,multiple_choice',
            'feedbackForm.questions_text' => 'nullable|string|max:2000',
        ], attributes: [
            'feedbackForm.training_session_id' => __('training_needs::dashboard.fields.session'),
            'feedbackForm.title' => __('training_needs::dashboard.fields.feedback_title'),
            'feedbackForm.status' => __('training_needs::dashboard.fields.status'),
            'feedbackForm.default_question_type' => __('training_needs::dashboard.fields.default_question_type'),
            'feedbackForm.questions_text' => __('training_needs::dashboard.fields.feedback_questions'),
        ]);

        $questions = collect(preg_split('/\r\n|\r|\n/', (string) data_get($validated, 'feedbackForm.questions_text')))
            ->map(fn ($question) => trim((string) $question))
            ->filter()
            ->values()
            ->map(fn ($question) => [
                'type' => (string) data_get($validated, 'feedbackForm.default_question_type'),
                'text' => $question,
            ])
            ->all();

        $form = $this->editingFeedbackFormId
            ? TrainingFeedbackForm::query()->findOrFail($this->editingFeedbackFormId)
            : new TrainingFeedbackForm();

        $form->fill([
            'training_session_id' => (int) data_get($validated, 'feedbackForm.training_session_id'),
            'title' => trim((string) data_get($validated, 'feedbackForm.title')),
            'status' => (string) data_get($validated, 'feedbackForm.status'),
            'questions' => $questions,
        ]);
        $form->save();

        $this->cancelFeedbackFormEdit();
        $this->reset('searchSession');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.feedback_form_saved'));
    }

    public function deleteFeedbackForm(int $feedbackFormId): void
    {
        $this->authorizeTrainingNeedsManage();

        $form = TrainingFeedbackForm::query()->findOrFail($feedbackFormId);
        $form->delete();

        if ($this->editingFeedbackFormId === $feedbackFormId) {
            $this->cancelFeedbackFormEdit();
        }

        if ((int) data_get($this->feedbackResponseForm, 'training_feedback_form_id') === $feedbackFormId) {
            $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        }

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.feedback_form_deleted'));
    }

    public function submitFeedbackResponse(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'feedbackResponseForm.training_feedback_form_id' => 'required|exists:training_feedback_forms,id',
            'feedbackResponseForm.personnel_id' => 'required|exists:personnels,id',
            'feedbackResponseForm.overall_score' => 'required|integer|min:1|max:5',
            'feedbackResponseForm.comments' => 'nullable|string|max:2000',
            'feedbackResponseForm.answers_text' => 'nullable|string|max:3000',
        ], attributes: [
            'feedbackResponseForm.training_feedback_form_id' => __('training_needs::dashboard.fields.feedback_form'),
            'feedbackResponseForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'feedbackResponseForm.overall_score' => __('training_needs::dashboard.fields.overall_score'),
            'feedbackResponseForm.comments' => __('training_needs::dashboard.fields.comments'),
            'feedbackResponseForm.answers_text' => __('training_needs::dashboard.fields.feedback_answers'),
        ]);

        $form = TrainingFeedbackForm::query()->findOrFail((int) data_get($validated, 'feedbackResponseForm.training_feedback_form_id'));

        $answers = collect(preg_split('/\r\n|\r|\n/', (string) data_get($validated, 'feedbackResponseForm.answers_text')))
            ->map(fn ($answer) => trim((string) $answer))
            ->filter()
            ->values()
            ->all();

        TrainingFeedbackResponse::query()->updateOrCreate(
            [
                'training_feedback_form_id' => $form->id,
                'personnel_id' => (int) data_get($validated, 'feedbackResponseForm.personnel_id'),
            ],
            [
                'training_session_id' => $form->training_session_id,
                'overall_score' => (int) data_get($validated, 'feedbackResponseForm.overall_score'),
                'comments' => data_get($validated, 'feedbackResponseForm.comments'),
                'answers' => $answers,
                'submitted_at' => now(),
            ]
        );

        $this->reset('feedbackResponseForm', 'searchFeedbackForm', 'searchPersonnel');
        $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.feedback_response_saved'));
    }
}
