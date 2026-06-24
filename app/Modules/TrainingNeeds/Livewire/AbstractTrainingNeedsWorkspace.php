<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Livewire\Concerns\WithRuntimeMemo;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\TrainingAnnualPlan;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsFormState;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsState;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class AbstractTrainingNeedsWorkspace extends Component
{
    use ConfirmsDestructiveActions;
    use DropdownConstructTrait;
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsFormState;
    use InteractsWithTrainingNeedsQueries;
    use InteractsWithTrainingNeedsState;
    use InteractsWithTabbedWorkspace;
    use WithRuntimeMemo;

    public string $activeTab = 'catalogs';

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

    public int $resultsSummaryVersion = 0;

    public int $reportsVersion = 0;

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

    public function mount(?string $tab = null): void
    {
        $this->authorizeTrainingNeedsView();
        $this->resetForms();
        $this->bootActiveTabFromRequest($tab);
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

    protected function uniqueSlug(string $modelClass, string $value, string $sourceColumn = 'name', ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base !== '' ? $base : 'item';
        $suffix = 2;

        while (
            $modelClass::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = Str::slug($value).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
