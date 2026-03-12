<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\DropdownConstructTrait;
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
use App\Models\PerformanceTestQuestionOption;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTestTrainingNeedLink;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\Personnel;
use App\Models\TrainingCompetency;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceEvaluationFlowMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceFoundationMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceReportingMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceTestingMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationFormState;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationState;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    use DropdownConstructTrait;
    use HandlesPerformanceFoundationMutations;
    use HandlesPerformanceEvaluationFlowMutations;
    use HandlesPerformanceTestingMutations;
    use HandlesPerformanceReportingMutations;
    use InteractsWithPerformanceEvaluationAccess;
    use InteractsWithPerformanceEvaluationFormState;
    use InteractsWithPerformanceEvaluationQueries;
    use InteractsWithPerformanceEvaluationState;

    public string $activeTab = 'overview';

    public array $cycleForm = [];

    public array $templateForm = [];

    public array $sectionForm = [];

    public array $itemForm = [];

    public array $evaluationForm = [];

    public array $scoreForm = [];

    public array $bankForm = [];

    public array $questionForm = [];

    public array $sessionForm = [];

    public array $attemptAnswerForm = [];

    public array $attemptSubmitForm = [];

    public array $reviewForm = [];

    public ?int $editingCycleId = null;

    public ?int $editingTemplateId = null;

    public ?int $editingSectionId = null;

    public ?int $editingItemId = null;

    public ?int $editingEvaluationFormId = null;

    public string $searchTemplate = '';

    public string $searchSection = '';

    public string $searchCompetency = '';

    public string $searchCycle = '';

    public string $searchPersonnel = '';

    public string $searchManager = '';

    public string $searchHrReviewer = '';

    public string $searchPerformanceForm = '';

    public string $searchTemplateItem = '';

    public string $searchTestBank = '';

    public string $searchTestQuestion = '';

    public string $searchTestCompetency = '';

    public string $searchTestSession = '';

    public string $searchTestPersonnel = '';

    public string $searchTestReviewer = '';

    public string $searchTestAttempt = '';

    public string $searchReviewAnswer = '';

    /**
     * @var array<int, string>
     */
    public array $tabs = ['overview', 'cycles', 'templates', 'evaluations', 'tests', 'lists'];

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
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


    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.dashboard');
    }

}
