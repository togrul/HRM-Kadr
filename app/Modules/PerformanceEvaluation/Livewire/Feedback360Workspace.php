<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRater;
use App\Models\PerformanceFormTemplate;
use App\Modules\PerformanceEvaluation\Application\Services\Feedback360Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * 360° feedback workspace: create multi-rater feedback requests, collect scores from
 * manager/peer/subordinate/self raters, and calibrate the raw averages into a final score.
 */
class Feedback360Workspace extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    public ?int $cycleId = null;

    /** list | detail | calibrate */
    public string $section = 'list';

    public ?int $activeRequestId = null;

    /** which inline picker is open: 'subject' | 'rater' */
    public string $personnelTarget = '';

    /** the rater whose scores are being captured in the side modal */
    public ?int $scoringRaterId = null;

    public array $createForm = [
        'performance_cycle_id' => null,
        'performance_form_template_id' => null,
        'subject_personnel_id' => null,
        'subject_label' => null,
        'is_anonymous' => true,
        'due_date' => null,
    ];

    public array $raterForm = [
        'rater_type' => 'peer',
        'rater_personnel_id' => null,
        'rater_label' => null,
    ];

    /** @var array<int,mixed> item id => score */
    public array $scoreInputs = [];

    /** @var array<int,string> item id => comment */
    public array $commentInputs = [];

    /** @var array<int,mixed> item id => calibrated score */
    public array $calibrationInputs = [];

    public ?string $calibrationNote = null;

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()->latest('period_start')->value('id');
        $this->createForm['performance_cycle_id'] = $this->cycleId;
    }

    protected function service(): Feedback360Service
    {
        return app(Feedback360Service::class);
    }

    public function getSummaryProperty(): array
    {
        return $this->service()->summary();
    }

    public function getRequestsProperty()
    {
        return $this->service()->requests($this->cycleId);
    }

    public function getCyclesProperty()
    {
        return PerformanceCycle::query()->orderByDesc('period_start')->get(['id', 'name']);
    }

    public function getTemplatesProperty()
    {
        return PerformanceFormTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function getActiveRequestProperty()
    {
        return $this->activeRequestId ? $this->service()->find($this->activeRequestId) : null;
    }

    public function getTemplateItemsProperty(): array
    {
        $request = $this->activeRequest;

        return $request ? $this->service()->templateItems((int) $request->performance_form_template_id) : [];
    }

    public function getAggregateProperty(): array
    {
        return $this->activeRequestId ? $this->service()->aggregate($this->activeRequestId) : ['items' => [], 'by_type' => [], 'raw_final' => null];
    }

    public function setSection(string $section): void
    {
        $this->section = in_array($section, ['list', 'detail', 'calibrate'], true) ? $section : 'list';
    }

    public function setCycle(int $cycleId): void
    {
        $this->cycleId = $cycleId;
    }

    #[On('personnel-picked')]
    public function onPersonnelPicked(string $target, int $id, string $label): void
    {
        $this->authorize('manage-performance-evaluation');

        if ($target === 'subject') {
            $this->createForm['subject_personnel_id'] = $id;
            $this->createForm['subject_label'] = $label;
        } elseif ($target === 'rater') {
            $this->raterForm['rater_personnel_id'] = $id;
            $this->raterForm['rater_label'] = $label;
        }
    }

    #[On('personnel-cleared')]
    public function onPersonnelCleared(string $target): void
    {
        if ($target === 'subject') {
            $this->createForm['subject_personnel_id'] = null;
            $this->createForm['subject_label'] = null;
        } elseif ($target === 'rater') {
            $this->raterForm['rater_personnel_id'] = null;
            $this->raterForm['rater_label'] = null;
        }
    }

    public function openCreate(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetCreateForm();
        $this->openSideMenu('create');
    }

    public function saveRequest(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'createForm.performance_cycle_id' => ['required', 'integer', 'exists:performance_cycles,id'],
            'createForm.performance_form_template_id' => ['required', 'integer', 'exists:performance_form_templates,id'],
            'createForm.subject_personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'createForm.is_anonymous' => ['boolean'],
            'createForm.due_date' => ['nullable', 'date'],
        ])['createForm'];

        $request = $this->service()->createRequest(
            (int) $data['performance_cycle_id'],
            (int) $data['performance_form_template_id'],
            (int) $data['subject_personnel_id'],
            (bool) ($data['is_anonymous'] ?? true),
            $data['due_date'] ?? null,
        );

        $this->closeSideMenu();
        $this->resetCreateForm();
        $this->openDetail($request->id);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::feedback.messages.request_created'));
    }

    public function openDetail(int $requestId): void
    {
        $this->activeRequestId = $requestId;
        $this->section = 'detail';
        $this->personnelTarget = '';
    }

    public function backToList(): void
    {
        $this->activeRequestId = null;
        $this->section = 'list';
    }

    public function startAddRater(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->personnelTarget = 'rater';
    }

    public function addRater(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'raterForm.rater_type' => ['required', 'in:manager,peer,subordinate,self'],
            'raterForm.rater_personnel_id' => ['required', 'integer', 'exists:personnels,id'],
        ])['raterForm'];

        $this->service()->addRater(
            (int) $this->activeRequestId,
            $data['rater_type'],
            (int) $data['rater_personnel_id'],
        );

        $this->raterForm = ['rater_type' => 'peer', 'rater_personnel_id' => null, 'rater_label' => null];
        $this->personnelTarget = '';
        $this->resetValidation();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::feedback.messages.rater_added'));
    }

    public function removeRater(int $raterId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->service()->removeRater($raterId);
    }

    public function openScoring(int $raterId): void
    {
        $this->authorize('manage-performance-evaluation');

        $rater = PerformanceFeedbackRater::query()->with('scores')->findOrFail($raterId);
        $this->scoringRaterId = $raterId;
        $this->scoreInputs = [];
        $this->commentInputs = [];
        foreach ($rater->scores as $score) {
            $this->scoreInputs[$score->performance_form_template_item_id] = (float) $score->score;
            $this->commentInputs[$score->performance_form_template_item_id] = (string) ($score->comment ?? '');
        }

        $this->openSideMenu('scoring');
    }

    public function saveScores(): void
    {
        $this->authorize('manage-performance-evaluation');

        if (! $this->scoringRaterId) {
            return;
        }

        $this->service()->submitScores($this->scoringRaterId, $this->scoreInputs, $this->commentInputs);

        $this->closeSideMenu();
        $this->scoringRaterId = null;
        $this->scoreInputs = [];
        $this->commentInputs = [];
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::feedback.messages.scores_saved'));
    }

    public function openCalibrate(int $requestId): void
    {
        $this->authorize('manage-performance-evaluation');

        $this->activeRequestId = $requestId;
        $this->section = 'calibrate';

        $request = $this->activeRequest;
        $aggregate = $this->aggregate;

        // Prefill calibrated values with any saved calibration, else the raw item average.
        $saved = (array) ($request?->calibrated_scores ?? []);
        $this->calibrationInputs = [];
        foreach ($aggregate['items'] as $item) {
            $this->calibrationInputs[$item['id']] = $saved[$item['id']] ?? $item['average'];
        }
        $this->calibrationNote = $request?->calibration_note;
    }

    public function saveCalibration(bool $approve = false): void
    {
        $this->authorize('manage-performance-evaluation');

        $this->service()->calibrate(
            (int) $this->activeRequestId,
            $this->calibrationInputs,
            $this->calibrationNote,
            $approve,
            (int) auth()->id(),
        );

        $message = $approve
            ? __('performance_evaluation::feedback.messages.calibration_approved')
            : __('performance_evaluation::feedback.messages.calibration_saved');
        $this->dispatch('notify', type: 'success', message: $message);

        if ($approve) {
            $this->backToList();
        }
    }

    public function reopenRequest(int $requestId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->service()->reopen($requestId);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::feedback.messages.reopened'));
    }

    public function deleteRequest(int $requestId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->service()->delete($requestId);

        if ($this->activeRequestId === $requestId) {
            $this->backToList();
        }
    }

    public function resetCreateForm(): void
    {
        $this->createForm = [
            'performance_cycle_id' => $this->cycleId,
            'performance_form_template_id' => null,
            'subject_personnel_id' => null,
            'subject_label' => null,
            'is_anonymous' => true,
            'due_date' => null,
        ];
        $this->personnelTarget = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.feedback-360-workspace');
    }
}
