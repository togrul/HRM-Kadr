<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestSession;
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
    public array $entities = ['forms', 'templates', 'items', 'test_banks', 'test_questions', 'test_sessions', 'attempts', 'test_answers', 'weak_links'];

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
            'test_banks' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'active' => __('performance_evaluation::dashboard.labels.active'),
                'inactive' => __('performance_evaluation::dashboard.labels.inactive'),
            ],
            'test_questions' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'active' => __('performance_evaluation::dashboard.labels.active'),
                'inactive' => __('performance_evaluation::dashboard.labels.inactive'),
                'auto_scored' => __('performance_evaluation::dashboard.labels.auto_scored_only'),
                'manual_review' => __('performance_evaluation::dashboard.labels.manual_review_only'),
            ],
            'test_sessions' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'assigned' => __('performance_evaluation::dashboard.test_statuses.assigned'),
                'in_progress' => __('performance_evaluation::dashboard.test_statuses.in_progress'),
                'completed' => __('performance_evaluation::dashboard.test_statuses.completed'),
                'closed' => __('performance_evaluation::dashboard.test_statuses.closed'),
            ],
            'attempts' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'assigned' => __('performance_evaluation::dashboard.test_statuses.assigned'),
                'in_progress' => __('performance_evaluation::dashboard.test_statuses.in_progress'),
                'completed' => __('performance_evaluation::dashboard.test_statuses.completed'),
                'review_pending' => __('performance_evaluation::dashboard.test_statuses.review_pending'),
            ],
            'test_answers' => [
                'all' => __('performance_evaluation::dashboard.labels.all_statuses'),
                'pending' => __('performance_evaluation::dashboard.labels.pending_review'),
                'auto_ready' => __('performance_evaluation::dashboard.labels.auto_scored_ready'),
                'reviewed' => __('performance_evaluation::dashboard.labels.reviewed_only'),
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
                'test_banks' => PerformanceTestBank::query()
                    ->withCount(['questions', 'sessions'])
                    ->with([
                        'questions:id,performance_test_bank_id,training_competency_id,question_type,prompt,max_score,sort_order,is_active',
                        'questions.competency:id,name',
                    ])
                    ->find($selectedRowId),
                'test_questions' => PerformanceTestQuestion::query()
                    ->with([
                        'bank:id,name,code',
                        'competency:id,name',
                        'options:id,performance_test_question_id,label,is_correct,score_value,sort_order',
                    ])
                    ->withCount('answers')
                    ->find($selectedRowId),
                'test_sessions' => PerformanceTestSession::query()
                    ->with([
                        'cycle:id,name',
                        'bank:id,name,code',
                        'personnel:id,surname,name,patronymic,tabel_no',
                        'reviewer:id,name,email',
                    ])
                    ->withCount('attempts')
                    ->find($selectedRowId),
                'attempts' => PerformanceTestAttempt::query()
                    ->with([
                        'session:id,personnel_id,performance_test_bank_id',
                        'session.personnel:id,surname,name,patronymic,tabel_no',
                        'session.bank:id,name',
                    ])
                    ->find($selectedRowId),
                'test_answers' => PerformanceTestAttemptAnswer::query()
                    ->with([
                        'attempt:id,performance_test_session_id,attempt_no,status,score,percentage',
                        'attempt.session:id,personnel_id,performance_test_bank_id',
                        'attempt.session.personnel:id,surname,name,patronymic,tabel_no',
                        'attempt.session.bank:id,name',
                        'question:id,prompt,question_type,max_score',
                        'selectedOption:id,label',
                        'reviewer:id,name,email',
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
                'test_banks' => PerformanceTestBank::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->when($this->filter === 'active', fn ($query) => $query->where('is_active', true))
                    ->when($this->filter === 'inactive', fn ($query) => $query->where('is_active', false))
                    ->withCount(['questions', 'sessions'])
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'test_questions' => PerformanceTestQuestion::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->where('prompt', 'like', "%{$search}%")
                                ->orWhereHas('bank', fn ($bank) => $bank->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('competency', fn ($competency) => $competency->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->filter === 'active', fn ($query) => $query->where('is_active', true))
                    ->when($this->filter === 'inactive', fn ($query) => $query->where('is_active', false))
                    ->when($this->filter === 'auto_scored', fn ($query) => $query->where('question_type', 'multiple_choice'))
                    ->when($this->filter === 'manual_review', fn ($query) => $query->whereIn('question_type', ['open_answer', 'case_study', 'behavioral']))
                    ->with([
                        'bank:id,name,code',
                        'competency:id,name',
                    ])
                    ->withCount('answers')
                    ->latest('id')
                    ->paginate(12, pageName: $pageName),
                'test_sessions' => PerformanceTestSession::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('bank', fn ($bank) => $bank->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                })
                                ->orWhereHas('reviewer', fn ($reviewer) => $reviewer->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
                    ->with([
                        'cycle:id,name',
                        'bank:id,name,code',
                        'personnel:id,surname,name,patronymic,tabel_no',
                        'reviewer:id,name,email',
                    ])
                    ->withCount('attempts')
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
                'test_answers' => PerformanceTestAttemptAnswer::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($inner) use ($search) {
                            $inner->whereHas('question', fn ($question) => $question->where('prompt', 'like', "%{$search}%"))
                                ->orWhereHas('attempt.session.bank', fn ($bank) => $bank->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('attempt.session.personnel', function ($personnel) use ($search) {
                                    $personnel->whereRaw("concat_ws(' ', surname, name, patronymic, tabel_no) like ?", ["%{$search}%"]);
                                })
                                ->orWhere('answer_text', 'like', "%{$search}%");
                        });
                    })
                    ->when($this->filter === 'pending', fn ($query) => $query->where('review_status', 'pending'))
                    ->when($this->filter === 'auto_ready', fn ($query) => $query->where('review_status', 'auto_ready'))
                    ->when($this->filter === 'reviewed', fn ($query) => $query->whereNotIn('review_status', ['pending', 'auto_ready']))
                    ->with([
                        'attempt:id,performance_test_session_id,attempt_no,status,score,percentage',
                        'attempt.session:id,personnel_id,performance_test_bank_id',
                        'attempt.session.personnel:id,surname,name,patronymic,tabel_no',
                        'attempt.session.bank:id,name',
                        'question:id,prompt,question_type,max_score',
                        'selectedOption:id,label',
                        'reviewer:id,name,email',
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
            'test_banks' => 'performance-test-banks-page',
            'test_questions' => 'performance-test-questions-page',
            'test_sessions' => 'performance-test-sessions-page',
            'attempts' => 'performance-attempts-page',
            'test_answers' => 'performance-test-answers-page',
            'weak_links' => 'performance-weak-links-page',
            default => 'performance-forms-page',
        };
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.lists');
    }
}
