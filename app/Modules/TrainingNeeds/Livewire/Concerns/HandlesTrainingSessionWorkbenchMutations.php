<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedPlanningService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionParticipantService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService;

trait HandlesTrainingSessionWorkbenchMutations
{
    public function selectVisibleSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = collect($this->sessionProposals)
            ->pluck('plan_item_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = [];
    }

    public function createSelectedSessionProposals(): void
    {
        $this->authorizeTrainingNeedsManage();

        $validated = $this->validate([
            'bulkProposalPlanItemIds' => 'required|array|min:1',
            'bulkProposalPlanItemIds.*' => 'integer|exists:training_plan_items,id',
        ]);

        $created = 0;

        foreach ($validated['bulkProposalPlanItemIds'] as $planItemId) {
            $proposal = app(TrainingSessionProposalService::class)
                ->proposals()
                ->firstWhere('plan_item_id', (int) $planItemId);

            if ($proposal === null) {
                continue;
            }

            $session = TrainingSession::query()->create([
                'training_plan_item_id' => $proposal['plan_item_id'],
                'training_annual_plan_id' => $proposal['training_annual_plan_id'],
                'training_program_id' => $proposal['training_program_id'],
                'title' => $proposal['title'],
                'scheduled_start_at' => $proposal['scheduled_start_at'],
                'scheduled_end_at' => $proposal['scheduled_end_at'],
                'location' => $proposal['location'],
                'trainer_name' => $proposal['trainer_name'],
                'capacity' => $proposal['capacity'],
                'planned_budget' => $proposal['planned_budget'],
                'actual_budget' => null,
                'auto_fill_participants' => (bool) $proposal['auto_fill_participants'],
                'status' => $proposal['status'],
                'notes' => $proposal['notes'],
            ]);

            if ($session->auto_fill_participants) {
                app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
            }

            $created++;
        }

        $this->bulkProposalPlanItemIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_session_proposals_created', ['count' => $created]));
    }

    public function storeSession(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'sessionForm.training_annual_plan_id' => 'nullable|exists:training_annual_plans,id',
            'sessionForm.training_program_id' => 'nullable|exists:training_programs,id',
            'sessionForm.title' => 'nullable|string|min:2|max:160',
            'sessionForm.scheduled_start_at' => 'required|date',
            'sessionForm.scheduled_end_at' => 'nullable|date|after_or_equal:sessionForm.scheduled_start_at',
            'sessionForm.location' => 'nullable|string|max:160',
            'sessionForm.trainer_name' => 'nullable|string|max:160',
            'sessionForm.capacity' => 'nullable|integer|min:1|max:5000',
            'sessionForm.planned_budget' => 'nullable|numeric|min:0|max:9999999.99',
            'sessionForm.actual_budget' => 'nullable|numeric|min:0|max:9999999.99',
            'sessionForm.auto_fill_participants' => 'nullable|boolean',
            'sessionForm.status' => 'required|in:draft,scheduled,in_progress,completed,cancelled',
            'sessionForm.notes' => 'nullable|string|max:2000',
        ], attributes: [
            'sessionForm.training_annual_plan_id' => __('training_needs::dashboard.fields.plan'),
            'sessionForm.training_program_id' => __('training_needs::dashboard.fields.program'),
            'sessionForm.title' => __('training_needs::dashboard.fields.session_title'),
            'sessionForm.scheduled_start_at' => __('training_needs::dashboard.fields.scheduled_start_at'),
            'sessionForm.scheduled_end_at' => __('training_needs::dashboard.fields.scheduled_end_at'),
            'sessionForm.location' => __('training_needs::dashboard.fields.location'),
            'sessionForm.trainer_name' => __('training_needs::dashboard.fields.trainer_name'),
            'sessionForm.capacity' => __('training_needs::dashboard.fields.capacity'),
            'sessionForm.planned_budget' => __('training_needs::dashboard.fields.planned_budget'),
            'sessionForm.actual_budget' => __('training_needs::dashboard.fields.actual_budget'),
            'sessionForm.auto_fill_participants' => __('training_needs::dashboard.fields.auto_fill_participants'),
            'sessionForm.status' => __('training_needs::dashboard.fields.status'),
        ]);

        $program = null;
        if (data_get($validated, 'sessionForm.training_program_id')) {
            $program = TrainingProgram::query()->find(data_get($validated, 'sessionForm.training_program_id'));
        }

        $payload = [
            'training_annual_plan_id' => data_get($validated, 'sessionForm.training_annual_plan_id'),
            'training_program_id' => data_get($validated, 'sessionForm.training_program_id'),
            'title' => trim((string) (data_get($validated, 'sessionForm.title') ?: ($program?->title ?: __('training_needs::dashboard.labels.default_session_title')))),
            'scheduled_start_at' => data_get($validated, 'sessionForm.scheduled_start_at'),
            'scheduled_end_at' => data_get($validated, 'sessionForm.scheduled_end_at'),
            'location' => blank(data_get($validated, 'sessionForm.location')) ? null : trim((string) data_get($validated, 'sessionForm.location')),
            'trainer_name' => blank(data_get($validated, 'sessionForm.trainer_name')) ? null : trim((string) data_get($validated, 'sessionForm.trainer_name')),
            'capacity' => data_get($validated, 'sessionForm.capacity'),
            'planned_budget' => data_get($validated, 'sessionForm.planned_budget'),
            'actual_budget' => data_get($validated, 'sessionForm.actual_budget'),
            'auto_fill_participants' => (bool) (data_get($validated, 'sessionForm.auto_fill_participants') ?? true),
            'status' => (string) data_get($validated, 'sessionForm.status'),
            'notes' => data_get($validated, 'sessionForm.notes'),
        ];
        $existingSession = $this->editingSessionId
            ? TrainingSession::query()->findOrFail($this->editingSessionId)
            : null;
        $session = $existingSession ?? new TrainingSession();

        $session->fill([
            ...$payload,
            'training_plan_item_id' => $this->selectedSessionProposalPlanItemId ?: $existingSession?->training_plan_item_id,
        ]);
        $session->save();

        $filledParticipants = 0;
        if ($session->auto_fill_participants) {
            $filledParticipants = app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
        }

        $planIdsToSync = collect([
            $session->training_annual_plan_id,
            $existingSession?->training_annual_plan_id,
        ])->filter()->unique()->values();

        foreach ($planIdsToSync as $planId) {
            $plan = TrainingAnnualPlan::query()->find($planId);
            if ($plan) {
                app(TrainingNeedPlanningService::class)->syncPlanStatus($plan);
            }
        }

        $this->cancelSessionEdit();
        $this->reset('searchSessionPlan', 'searchTrainingProgram');
        $this->selectedSessionId = $session->id;
        $this->sessionDetailWorkspaceVersion++;
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_saved', [
            'count' => $filledParticipants,
        ]));
    }

    public function editSession(int $id): void
    {
        $this->authorizeTrainingNeedsManage();

        $session = TrainingSession::query()->findOrFail($id);

        $this->editingSessionId = $session->id;
        $this->selectedSessionId = $session->id;
        $this->selectedSessionProposalPlanItemId = $session->training_plan_item_id;
        $this->sessionForm = [
            'training_annual_plan_id' => $session->training_annual_plan_id,
            'training_program_id' => $session->training_program_id,
            'title' => (string) $session->title,
            'scheduled_start_at' => optional($session->scheduled_start_at)->format('Y-m-d\TH:i'),
            'scheduled_end_at' => optional($session->scheduled_end_at)->format('Y-m-d\TH:i'),
            'location' => (string) ($session->location ?? ''),
            'trainer_name' => (string) ($session->trainer_name ?? ''),
            'capacity' => $session->capacity,
            'planned_budget' => $session->planned_budget,
            'actual_budget' => $session->actual_budget,
            'auto_fill_participants' => (bool) $session->auto_fill_participants,
            'status' => (string) $session->status,
            'notes' => (string) ($session->notes ?? ''),
        ];
    }

    public function cancelSessionEdit(): void
    {
        $this->editingSessionId = null;
        $this->selectedSessionProposalPlanItemId = null;
        $this->sessionForm = $this->sessionDefaults();
        $this->resetValidation();
    }

    public function deleteSession(int $sessionId): void
    {
        $this->authorizeTrainingNeedsManage();

        $session = TrainingSession::query()->findOrFail($sessionId);
        $planId = $session->training_annual_plan_id;
        $selectedFeedbackFormBelongsToSession = (int) data_get($this->feedbackResponseForm, 'training_feedback_form_id') > 0
            ? TrainingFeedbackForm::query()
                ->where('id', (int) data_get($this->feedbackResponseForm, 'training_feedback_form_id'))
                ->where('training_session_id', $sessionId)
                ->exists()
            : false;
        $session->delete();

        if ($this->editingSessionId === $sessionId) {
            $this->cancelSessionEdit();
        }

        if ($this->selectedSessionId === $sessionId) {
            $this->selectedSessionId = null;
            $this->bulkParticipantIds = [];
            $this->sessionDetailWorkspaceVersion++;
        }

        if ((int) data_get($this->participantForm, 'training_session_id') === $sessionId) {
            $this->participantForm['training_session_id'] = null;
            $this->participantForm['training_need_item_id'] = null;
        }

        if ((int) data_get($this->feedbackForm, 'training_session_id') === $sessionId) {
            $this->feedbackForm['training_session_id'] = null;
        }

        if ($selectedFeedbackFormBelongsToSession) {
            $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        }

        if ($planId) {
            $plan = TrainingAnnualPlan::query()->find($planId);
            if ($plan) {
                app(TrainingNeedPlanningService::class)->syncPlanStatus($plan);
            }
        }

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_deleted'));
    }
}
