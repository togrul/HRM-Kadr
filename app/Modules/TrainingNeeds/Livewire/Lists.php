<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingNeedItem;
use App\Models\TrainingPlanItem;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Livewire\WithPagination;

#[Isolate]
class Lists extends Component
{
    use InteractsWithTrainingNeedsAccess;
    use WithRuntimeMemo;
    use WithPagination;

    public string $entity = 'needs';
    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $selectedRowId = null;

    /**
     * @var array<int, string>
     */
    public array $entities = ['needs', 'plans', 'sessions', 'deliveries'];

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
    }

    public function switchEntity(string $entity): void
    {
        if (! in_array($entity, $this->entities, true)) {
            return;
        }

        $this->entity = $entity;
        $this->search = '';
        $this->statusFilter = 'all';
        $this->selectedRowId = null;
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function selectRow(int $id): void
    {
        $this->selectedRowId = $this->selectedRowId === $id ? null : $id;
        $this->resetRuntimeMemo();
    }

    public function getSummaryProperty(): array
    {
        $rows = $this->rows;

        return [
            'total' => $rows->total(),
            'visible' => $rows->count(),
        ];
    }

    public function getStatusOptionsProperty(): array
    {
        return match ($this->entity) {
            'sessions' => [
                'all' => __('training_needs::dashboard.labels.all_statuses'),
                'draft' => __('training_needs::dashboard.session_statuses.draft'),
                'scheduled' => __('training_needs::dashboard.session_statuses.scheduled'),
                'completed' => __('training_needs::dashboard.session_statuses.completed'),
            ],
            'deliveries' => [
                'all' => __('training_needs::dashboard.labels.all_statuses'),
                'completed' => __('training_needs::dashboard.delivery_result_statuses.completed'),
                'partial' => __('training_needs::dashboard.delivery_result_statuses.partial'),
                'failed' => __('training_needs::dashboard.delivery_result_statuses.failed'),
            ],
            'plans' => [
                'all' => __('training_needs::dashboard.labels.all_statuses'),
                'draft' => __('training_needs::dashboard.plan_item_statuses.draft'),
                'review' => __('training_needs::dashboard.plan_item_statuses.review'),
                'approved' => __('training_needs::dashboard.plan_item_statuses.approved'),
                'planned' => __('training_needs::dashboard.plan_item_statuses.planned'),
                'completed' => __('training_needs::dashboard.plan_item_statuses.completed'),
            ],
            default => [
                'all' => __('training_needs::dashboard.labels.all_statuses'),
                'draft' => __('training_needs::dashboard.need_statuses.draft'),
                'review' => __('training_needs::dashboard.need_statuses.review'),
                'approved' => __('training_needs::dashboard.need_statuses.approved'),
                'planned' => __('training_needs::dashboard.need_statuses.planned'),
                'completed' => __('training_needs::dashboard.need_statuses.completed'),
            ],
        };
    }

    public function getSelectedRowProperty()
    {
        if (! $this->selectedRowId) {
            return null;
        }

        $currentRow = $this->rows->getCollection()->firstWhere('id', $this->selectedRowId);
        if ($currentRow) {
            return $currentRow;
        }

        $selectedRowId = $this->selectedRowId;

        return $this->rememberRuntime("trainingLists.selectedRow.{$this->entity}.{$selectedRowId}", function () use ($selectedRowId) {
            return match ($this->entity) {
                'plans' => TrainingPlanItem::query()
                    ->with([
                        'plan:id,title,plan_year,plan_quarter,status',
                        'program:id,title',
                        'competency:id,name',
                    ])->find($selectedRowId),
                'sessions' => TrainingSession::query()
                    ->with(['program:id,title', 'plan:id,title'])
                    ->withCount('participants')
                    ->find($selectedRowId),
                'deliveries' => TrainingDeliveryRecord::query()
                    ->with([
                        'session:id,title,scheduled_start_at',
                        'program:id,title',
                        'personnel:id,surname,name,patronymic,tabel_no',
                    ])->find($selectedRowId),
                default => TrainingNeedItem::query()
                    ->with([
                        'personnel:id,surname,name,patronymic,tabel_no',
                        'competency:id,name',
                        'recommendedProgram:id,title',
                    ])->find($selectedRowId),
            };
        });
    }

    public function getRowsProperty()
    {
        $search = trim($this->search);
        $pageName = $this->pageNameForEntity();
        $page = (int) ($this->paginators[$pageName] ?? 1);

        return $this->rememberRuntime("trainingLists.rows.{$this->entity}.{$this->statusFilter}.{$page}.".md5($search), function () use ($search, $pageName) {
            return match ($this->entity) {
                'plans' => TrainingPlanItem::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('plan', fn ($plan) => $plan->where('title', 'like', "%{$search}%"))
                                ->orWhereHas('program', fn ($program) => $program->where('title', 'like', "%{$search}%"))
                                ->orWhereHas('competency', fn ($competency) => $competency->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                    ->with([
                        'plan:id,title,plan_year,plan_quarter,status',
                        'program:id,title',
                        'competency:id,name',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'sessions' => TrainingSession::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('title', 'like', "%{$search}%")
                                ->orWhere('trainer_name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%")
                                ->orWhereHas('program', fn ($program) => $program->where('title', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                    ->with(['program:id,title', 'plan:id,title'])
                    ->withCount('participants')
                    ->latest('scheduled_start_at')
                    ->paginate(12, pageName: $pageName),
                'deliveries' => TrainingDeliveryRecord::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('certificate_name', 'like', "%{$search}%")
                                ->orWhereHas('program', fn ($program) => $program->where('title', 'like', "%{$search}%"))
                                ->orWhereHas('personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                });
                        });
                    })
                    ->when($this->statusFilter !== 'all', fn ($query) => $query->where('result_status', $this->statusFilter))
                    ->with([
                        'session:id,title,scheduled_start_at',
                        'program:id,title',
                        'personnel:id,surname,name,patronymic,tabel_no',
                    ])
                    ->latest('completed_at')
                    ->paginate(12, pageName: $pageName),
                default => TrainingNeedItem::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('personnel', function ($personnel) use ($search) {
                                $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                            })
                                ->orWhereHas('competency', fn ($competency) => $competency->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('recommendedProgram', fn ($program) => $program->where('title', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                    ->with([
                        'personnel:id,surname,name,patronymic,tabel_no',
                        'competency:id,name',
                        'recommendedProgram:id,title',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
            };
        });
    }

    protected function pageNameForEntity(): string
    {
        return match ($this->entity) {
            'plans' => 'training-plan-items-page',
            'sessions' => 'training-sessions-page',
            'deliveries' => 'training-deliveries-page',
            default => 'training-needs-page',
        };
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.lists');
    }
}
