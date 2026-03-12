<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Livewire\Concerns\WithRuntimeMemo;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingCatalogMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingCalendarMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingPlanningMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingResultsMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsFormState;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsState;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use ConfirmsDestructiveActions;
    use DropdownConstructTrait;
    use HandlesTrainingCatalogMutations;
    use HandlesTrainingPlanningMutations;
    use HandlesTrainingCalendarMutations;
    use HandlesTrainingResultsMutations;
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsFormState;
    use InteractsWithTrainingNeedsQueries;
    use InteractsWithTrainingNeedsState;
    use WithRuntimeMemo;
    use WithFileUploads;

    public string $activeTab = 'overview';

    public array $groupForm = [];

    public array $levelForm = [];

    public array $competencyForm = [];

    public array $programForm = [];

    public array $programMapForm = [];

    public array $requirementForm = [];

    public array $profileForm = [];

    public array $needForm = [];

    public array $planForm = [];

    public array $planItemReviewForm = [];

    public array $sessionForm = [];

    public array $participantForm = [];

    public array $feedbackForm = [];

    public array $feedbackResponseForm = [];

    public array $deliveryDocumentForm = [];

    /**
     * @var array<int>
     */
    public array $bulkParticipantIds = [];

    /**
     * @var array<int>
     */
    public array $bulkProposalPlanItemIds = [];

    public ?int $selectedSessionId = null;

    public ?int $selectedPlanItemId = null;

    public ?int $selectedSessionProposalPlanItemId = null;

    public ?int $editingPlanId = null;

    public ?int $editingSessionId = null;

    public ?int $editingFeedbackFormId = null;

    public int $sessionDetailWorkspaceVersion = 0;

    public string $bulkAttendanceStatus = 'confirmed';

    public string $selectedParticipantAttendanceFilter = 'all';

    public string $selectedParticipantSourceFilter = 'all';

    public string $searchCompetencyGroup = '';

    public string $searchCompetencyLevel = '';

    public string $searchCompetency = '';

    public string $searchTrainingProgram = '';

    public string $searchRequirementPosition = '';

    public string $searchPersonnel = '';

    public string $searchPlanProgram = '';

    public string $searchSessionPlan = '';

    public string $searchSessionProgram = '';

    public string $searchSession = '';

    public string $searchFeedbackForm = '';

    public string $searchDeliveryRecord = '';

    public string $searchSelectedParticipant = '';

    /**
     * @var array<int, string>
     */
    public array $tabs = ['overview', 'catalogs', 'matrix', 'profiles', 'planning', 'calendar', 'results', 'analytics', 'lists'];

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
        $this->resetForms();

        $requestedTab = (string) request()->query('tab', 'overview');
        if (in_array($requestedTab, $this->tabs, true)) {
            $this->activeTab = $requestedTab;
        }
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, $this->tabs, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function applySessionProposal(int $planItemId): void
    {
        $proposal = app(TrainingSessionProposalService::class)
            ->proposals()
            ->firstWhere('plan_item_id', $planItemId);

        abort_if($proposal === null, 404);

        $this->activeTab = 'calendar';
        $this->selectedSessionProposalPlanItemId = $planItemId;
        $this->sessionForm = [
            'training_annual_plan_id' => $proposal['training_annual_plan_id'],
            'training_program_id' => $proposal['training_program_id'],
            'title' => $proposal['title'],
            'scheduled_start_at' => $proposal['scheduled_start_at'],
            'scheduled_end_at' => $proposal['scheduled_end_at'],
            'location' => $proposal['location'] ?? '',
            'trainer_name' => $proposal['trainer_name'] ?? '',
            'capacity' => $proposal['capacity'],
            'planned_budget' => $proposal['planned_budget'],
            'auto_fill_participants' => (bool) $proposal['auto_fill_participants'],
            'status' => $proposal['status'],
            'notes' => $proposal['notes'] ?? '',
        ];

        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_proposal_applied'));
    }

    public function createSessionFromProposal(int $planItemId): void
    {
        $this->applySessionProposal($planItemId);
        $this->storeSession();
    }

    public function confirmRemoveSelectedParticipants(): void
    {
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkParticipantIds' => 'required|array|min:1',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $this->confirmDeletion(
            action: 'removeSelectedParticipants',
            message: __('training_needs::dashboard.confirmations.remove_selected_participants'),
            description: __('training_needs::dashboard.fields.selected_participants').': '.count((array) $validated['bulkParticipantIds']),
            confirmLabel: __('training_needs::dashboard.actions.remove_selected_participants'),
        );
    }

    public function confirmDeleteDeliveryCertificate(int $deliveryRecordId): void
    {
        $record = TrainingDeliveryRecord::query()->with(['personnel', 'program'])->findOrFail($deliveryRecordId);

        $details = array_filter([
            $record->personnel?->fullname,
            $record->program?->title,
            $record->certificate_name,
        ]);

        $this->confirmDeletion(
            action: 'deleteDeliveryCertificate',
            parameters: [$deliveryRecordId],
            message: __('training_needs::dashboard.confirmations.delete_certificate'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete_certificate'),
        );
    }

    public function confirmDeletePlan(int $planId): void
    {
        $plan = TrainingAnnualPlan::query()->findOrFail($planId);

        $details = array_filter([
            $plan->title,
            __('training_needs::dashboard.labels.plan_scope', [
                'year' => $plan->plan_year,
                'quarter' => $plan->plan_quarter ? 'Q'.$plan->plan_quarter : __('training_needs::dashboard.labels.all_year'),
            ]),
        ]);

        $this->confirmDeletion(
            action: 'deletePlan',
            parameters: [$planId],
            message: __('training_needs::dashboard.confirmations.delete_plan'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteSession(int $sessionId): void
    {
        $session = TrainingSession::query()->with(['program:id,title'])->findOrFail($sessionId);

        $details = array_filter([
            $session->title,
            $session->program?->title,
            optional($session->scheduled_start_at)->format('d.m.Y H:i'),
        ]);

        $this->confirmDeletion(
            action: 'deleteSession',
            parameters: [$sessionId],
            message: __('training_needs::dashboard.confirmations.delete_session'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteFeedbackForm(int $feedbackFormId): void
    {
        $form = TrainingFeedbackForm::query()->with(['session:id,title'])->findOrFail($feedbackFormId);

        $details = array_filter([
            $form->title,
            $form->session?->title,
        ]);

        $this->confirmDeletion(
            action: 'deleteFeedbackForm',
            parameters: [$feedbackFormId],
            message: __('training_needs::dashboard.confirmations.delete_feedback_form'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    #[On('training-needs:edit-feedback-form')]
    public function handleEditFeedbackForm(int $feedbackFormId): void
    {
        $this->activeTab = 'results';
        $this->editFeedbackForm($feedbackFormId);
    }

    #[On('training-needs:confirm-delete-feedback-form')]
    public function handleConfirmDeleteFeedbackForm(int $feedbackFormId): void
    {
        $this->activeTab = 'results';
        $this->confirmDeleteFeedbackForm($feedbackFormId);
    }

    #[On('training-needs:calendar-mutated')]
    public function handleCalendarMutated(): void
    {
        $this->refreshRuntimeCaches();
        $this->sessionDetailWorkspaceVersion++;
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.dashboard');
    }

    protected function uniqueSlug(string $modelClass, string $value, string $sourceColumn = 'name'): string
    {
        $base = Str::slug($value);
        $slug = $base !== '' ? $base : 'item';
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($value).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
