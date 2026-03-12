<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTrainingNeedLink;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Livewire\WithPagination;

#[Isolate]
class Lists extends Component
{
    use InteractsWithPerformanceEvaluationAccess;
    use WithRuntimeMemo;
    use WithPagination;

    public string $entity = 'forms';
    public string $search = '';
    public string $filter = 'all';
    public ?int $selectedRowId = null;

    /**
     * @var array<int, string>
     */
    public array $entities = ['forms', 'templates', 'items', 'attempts', 'weak_links'];

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
    }

    public function switchEntity(string $entity): void
    {
        if (! in_array($entity, $this->entities, true)) {
            return;
        }

        $this->entity = $entity;
        $this->search = '';
        $this->filter = 'all';
        $this->selectedRowId = null;
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function selectRow(int $id): void
    {
        $this->selectedRowId = $this->selectedRowId === $id ? null : $id;
        $this->resetRuntimeMemo();
    }

    public function getFilterOptionsProperty(): array
    {
        return match ($this->entity) {
            'forms' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'pending' => __('performance_evaluation::dashboard.labels.pending_only'),
                'high' => __('performance_evaluation::dashboard.categories.high'),
                'medium' => __('performance_evaluation::dashboard.categories.medium'),
                'weak' => __('performance_evaluation::dashboard.categories.weak'),
            ],
            'templates' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'active' => __('performance_evaluation::dashboard.labels.active'),
                'inactive' => __('performance_evaluation::dashboard.labels.inactive'),
            ],
            'items' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'linked' => __('performance_evaluation::dashboard.labels.linked_only'),
                'unlinked' => __('performance_evaluation::dashboard.labels.unlinked_only'),
            ],
            'attempts' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'assigned' => __('performance_evaluation::dashboard.test_statuses.assigned'),
                'in_progress' => __('performance_evaluation::dashboard.test_statuses.in_progress'),
                'completed' => __('performance_evaluation::dashboard.test_statuses.completed'),
                'review_pending' => __('performance_evaluation::dashboard.test_statuses.review_pending'),
            ],
            default => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'high' => __('training_needs::dashboard.priorities.high'),
                'medium' => __('training_needs::dashboard.priorities.medium'),
                'low' => __('training_needs::dashboard.priorities.low'),
            ],
        };
    }

    public function getSummaryProperty(): array
    {
        $rows = $this->rows;

        return [
            'total' => $rows->total(),
            'visible' => $rows->count(),
        ];
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

        return $this->rememberRuntime("performanceLists.selectedRow.{$this->entity}.{$selectedRowId}", function () use ($selectedRowId) {
            return match ($this->entity) {
                'templates' => PerformanceFormTemplate::query()
                    ->withCount('sections')
                    ->find($selectedRowId),
                'items' => PerformanceFormTemplateItem::query()
                    ->with([
                        'section:id,name,performance_form_template_id',
                        'section.template:id,name,code',
                        'competency:id,name',
                    ])
                    ->find($selectedRowId),
                'attempts' => PerformanceTestAttempt::query()
                    ->with([
                        'session:id,personnel_id,performance_test_bank_id',
                        'session.personnel:id,surname,name,patronymic,tabel_no',
                        'session.bank:id,name',
                    ])
                    ->find($selectedRowId),
                'weak_links' => PerformanceTrainingNeedLink::query()
                    ->with([
                        'form:id,personnel_id,final_score,final_category',
                        'form.personnel:id,surname,name,patronymic,tabel_no',
                        'competency:id,name',
                        'trainingNeed:id,priority,status,reason',
                    ])
                    ->find($selectedRowId),
                default => PerformanceForm::query()
                    ->with([
                        'cycle:id,name',
                        'template:id,name,code',
                        'personnel:id,surname,name,patronymic,tabel_no',
                    ])
                    ->find($selectedRowId),
            };
        });
    }

    public function getRowsProperty()
    {
        $search = trim($this->search);
        $pageName = $this->pageNameForEntity();
        $page = (int) ($this->paginators[$pageName] ?? 1);

        return $this->rememberRuntime("performanceLists.rows.{$this->entity}.{$this->filter}.{$page}.".md5($search), function () use ($search, $pageName) {
            return match ($this->entity) {
                'templates' => PerformanceFormTemplate::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->when($this->filter === 'active', fn ($query) => $query->where('is_active', true))
                    ->when($this->filter === 'inactive', fn ($query) => $query->where('is_active', false))
                    ->withCount('sections')
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'items' => PerformanceFormTemplateItem::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('name', 'like', "%{$search}%")
                                ->orWhereHas('section', fn ($section) => $section->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('competency', fn ($competency) => $competency->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->filter === 'linked', fn ($query) => $query->whereNotNull('training_competency_id'))
                    ->when($this->filter === 'unlinked', fn ($query) => $query->whereNull('training_competency_id'))
                    ->with([
                        'section:id,name,performance_form_template_id',
                        'section.template:id,name,code',
                        'competency:id,name',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'attempts' => PerformanceTestAttempt::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('id', 'like', "%{$search}%")
                                ->orWhereHas('session.personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                })
                                ->orWhereHas('session.bank', fn ($bank) => $bank->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
                    ->with([
                        'session:id,personnel_id,performance_test_bank_id',
                        'session.personnel:id,surname,name,patronymic,tabel_no',
                        'session.bank:id,name',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'weak_links' => PerformanceTrainingNeedLink::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('competency', fn ($competency) => $competency->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('form.personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                });
                        });
                    })
                    ->when($this->filter !== 'all', fn ($query) => $query->whereHas('trainingNeed', fn ($need) => $need->where('priority', $this->filter)))
                    ->with([
                        'form:id,personnel_id,final_score,final_category',
                        'form.personnel:id,surname,name,patronymic,tabel_no',
                        'competency:id,name',
                        'trainingNeed:id,priority,status,reason',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                default => PerformanceForm::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('cycle', fn ($cycle) => $cycle->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('template', function ($template) use ($search) {
                                    $template->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%");
                                })
                                ->orWhereHas('personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                });
                        });
                    })
                    ->when($this->filter === 'pending', fn ($query) => $query->whereNull('final_category'))
                    ->when(in_array($this->filter, ['high', 'medium', 'weak'], true), fn ($query) => $query->where('final_category', $this->filter))
                    ->with([
                        'cycle:id,name',
                        'template:id,name,code',
                        'personnel:id,surname,name,patronymic,tabel_no',
                    ])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
            };
        });
    }

    protected function pageNameForEntity(): string
    {
        return match ($this->entity) {
            'templates' => 'performance-templates-page',
            'items' => 'performance-items-page',
            'attempts' => 'performance-attempts-page',
            'weak_links' => 'performance-weak-links-page',
            default => 'performance-forms-page',
        };
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.lists');
    }
}
