<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
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
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationFormState;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationState;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class AbstractPerformanceWorkspace extends Component
{
    use ConfirmsDestructiveActions;
    use DropdownConstructTrait;
    use InteractsWithPerformanceEvaluationAccess;
    use InteractsWithPerformanceEvaluationFormState;
    use InteractsWithPerformanceEvaluationQueries;
    use InteractsWithPerformanceEvaluationState;
    use InteractsWithTabbedWorkspace;
    use WithFileUploads;

    public string $activeTab = 'cycles';

    public string $testsSubTab = 'banks';

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

    public array $testQuestionImportForm = [];

    public $testQuestionImportFile = null;

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

    public int $evaluationsSummaryVersion = 0;

    public int $testsSummaryVersion = 0;

    /**
     * @var array<int, string>
     */
    public array $testsSubTabs = ['banks', 'questions', 'import', 'sessions', 'review'];

    public function mount(?string $tab = null, ?string $testsView = null): void
    {
        $this->authorizePerformanceEvaluationView();
        $this->resetForms();
        $this->bootActiveTabFromRequest($tab);
        $this->testsSubTabs = app(HrPolicyPackService::class)->workflowTestTabs('performance_evaluation', $this->testsSubTabs);

        $requestedTestsSubTab = (string) ($testsView ?: request()->query('tests_view', 'banks'));
        if (in_array($requestedTestsSubTab, $this->testsSubTabs, true)) {
            $this->testsSubTab = $requestedTestsSubTab;

            return;
        }

        $this->testsSubTab = $this->testsSubTabs[0] ?? 'banks';
    }

    public function switchTestsSubTab(string $tab): void
    {
        if (! in_array($tab, $this->testsSubTabs, true)) {
            return;
        }

        $this->testsSubTab = $tab;
    }

    public function refreshEvaluationsSummary(): void
    {
        $this->evaluationsSummaryVersion++;
    }

    public function refreshTestsSummary(): void
    {
        $this->testsSummaryVersion++;
    }
}
