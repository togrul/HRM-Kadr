<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceTestAttemptAnswer;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceSkillMeasurementService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class EvaluatorWorkspace extends Component
{
    public string $searchAssignedForms = '';

    public string $assignedRoleFilter = 'all';

    public string $assignedStatusFilter = 'all';

    public string $searchPendingAnswers = '';

    public string $pendingQuestionTypeFilter = 'all';

    public array $scoreForm = [
        'performance_form_id' => null,
        'performance_form_template_item_id' => null,
        'evaluator_type' => 'manager',
        'score' => null,
        'comment' => '',
    ];

    public array $reviewForm = [
        'performance_test_attempt_answer_id' => null,
        'score' => null,
        'feedback' => '',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]), 403);
    }

    public function getAssignedFormsProperty()
    {
        $userId = auth()->id();

        return PerformanceForm::query()
            ->with([
                'cycle:id,name',
                'template:id,name,code',
                'personnel:id,surname,name,patronymic,tabel_no',
                'manager:id,name,email',
                'hrReviewer:id,name,email',
            ])
            ->where(function ($query) use ($userId): void {
                $query->where('manager_id', $userId)
                    ->orWhere('hr_reviewer_id', $userId);
            })
            ->when($this->searchAssignedForms !== '', function ($query): void {
                $search = '%'.$this->searchAssignedForms.'%';

                $query->whereHas('personnel', function ($personnelQuery) use ($search): void {
                    $personnelQuery
                        ->where('surname', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('patronymic', 'like', $search)
                        ->orWhere('tabel_no', 'like', $search);
                });
            })
            ->when($this->assignedRoleFilter !== 'all', function ($query) use ($userId): void {
                if ($this->assignedRoleFilter === 'manager') {
                    $query->where('manager_id', $userId);
                }

                if ($this->assignedRoleFilter === 'hr') {
                    $query->where('hr_reviewer_id', $userId);
                }
            })
            ->when($this->assignedStatusFilter !== 'all', function ($query) use ($userId): void {
                if ($this->assignedStatusFilter === 'pending') {
                    $query->where(function ($statusQuery) use ($userId): void {
                        $statusQuery
                            ->when(true, function ($inner) use ($userId): void {
                                $inner->where(function ($pendingQuery) use ($userId): void {
                                    $pendingQuery
                                        ->when(true, function ($pendingInner) use ($userId): void {
                                            $pendingInner
                                                ->where(function ($managerQuery) use ($userId): void {
                                                    $managerQuery
                                                        ->where('manager_id', $userId)
                                                        ->where('manager_status', 'draft');
                                                })
                                                ->orWhere(function ($hrQuery) use ($userId): void {
                                                    $hrQuery
                                                        ->where('hr_reviewer_id', $userId)
                                                        ->where('hr_status', 'draft');
                                                });
                                        });
                                });
                            });
                    });
                }

                if ($this->assignedStatusFilter === 'submitted') {
                    $query->where(function ($statusQuery) use ($userId): void {
                        $statusQuery
                            ->where(function ($managerQuery) use ($userId): void {
                                $managerQuery
                                    ->where('manager_id', $userId)
                                    ->where('manager_status', 'submitted');
                            })
                            ->orWhere(function ($hrQuery) use ($userId): void {
                                $hrQuery
                                    ->where('hr_reviewer_id', $userId)
                                    ->where('hr_status', 'submitted');
                            });
                    });
                }
            })
            ->latest('id')
            ->limit(24)
            ->get();
    }

    public function getPendingAnswersProperty()
    {
        return PerformanceTestAttemptAnswer::query()
            ->with([
                'attempt.session.personnel:id,surname,name,patronymic,tabel_no',
                'attempt.session.bank:id,name',
                'question:id,prompt,question_type',
            ])
            ->where('review_status', 'pending')
            ->whereHas('attempt.session', fn ($query) => $query->where('reviewer_id', auth()->id()))
            ->when($this->searchPendingAnswers !== '', function ($query): void {
                $search = '%'.$this->searchPendingAnswers.'%';

                $query->whereHas('question', function ($questionQuery) use ($search): void {
                    $questionQuery->where('prompt', 'like', $search);
                });
            })
            ->when($this->pendingQuestionTypeFilter !== 'all', function ($query): void {
                $query->whereHas('question', fn ($questionQuery) => $questionQuery->where('question_type', $this->pendingQuestionTypeFilter));
            })
            ->latest('id')
            ->limit(24)
            ->get();
    }

    public function getAssignedFormsSummaryProperty(): array
    {
        $forms = $this->assignedForms;
        $userId = (int) auth()->id();

        return [
            'total' => $forms->count(),
            'pending' => $forms->filter(function ($form) use ($userId): bool {
                return ((int) $form->manager_id === $userId && $form->manager_status !== 'submitted')
                    || ((int) $form->hr_reviewer_id === $userId && $form->hr_status !== 'submitted');
            })->count(),
            'reviews' => $this->pendingAnswers->count(),
        ];
    }

    public function formItemOptions(): array
    {
        $formId = (int) data_get($this->scoreForm, 'performance_form_id');
        if ($formId <= 0) {
            return [];
        }

        $templateId = PerformanceForm::query()->whereKey($formId)->value('performance_form_template_id');
        if (! $templateId) {
            return [];
        }

        return PerformanceFormTemplateItem::query()
            ->join('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
            ->where('performance_form_template_sections.performance_form_template_id', $templateId)
            ->orderBy('performance_form_template_items.sort_order')
            ->orderBy('performance_form_template_items.name')
            ->get([
                'performance_form_template_items.id',
                'performance_form_template_items.name as label',
            ])
            ->map(fn ($row) => ['id' => $row->id, 'label' => $row->label])
            ->all();
    }

    public function startScoreForm(int $formId): void
    {
        $form = $this->assignedForms->firstWhere('id', $formId);
        abort_if($form === null, 403);

        $evaluatorType = $form->manager_id === auth()->id() ? 'manager' : 'hr';

        $this->scoreForm = [
            'performance_form_id' => $form->id,
            'performance_form_template_item_id' => null,
            'evaluator_type' => $evaluatorType,
            'score' => null,
            'comment' => '',
        ];
    }

    public function saveAssignedScore(): void
    {
        $validated = $this->validate([
            'scoreForm.performance_form_id' => 'required|exists:performance_forms,id',
            'scoreForm.performance_form_template_item_id' => 'required|exists:performance_form_template_items,id',
            'scoreForm.evaluator_type' => 'required|in:manager,hr',
            'scoreForm.score' => 'required|numeric|min:0|max:100',
            'scoreForm.comment' => 'nullable|string|max:1000',
        ], attributes: [
            'scoreForm.performance_form_id' => __('performance_evaluation::dashboard.fields.evaluation_form'),
            'scoreForm.performance_form_template_item_id' => __('performance_evaluation::dashboard.fields.item'),
            'scoreForm.evaluator_type' => __('performance_evaluation::dashboard.fields.evaluator_type'),
            'scoreForm.score' => __('performance_evaluation::dashboard.fields.score'),
            'scoreForm.comment' => __('performance_evaluation::dashboard.fields.comment'),
        ]);

        $form = PerformanceForm::query()->findOrFail((int) data_get($validated, 'scoreForm.performance_form_id'));
        $evaluatorType = (string) data_get($validated, 'scoreForm.evaluator_type');

        abort_unless(
            ($evaluatorType === 'manager' && (int) $form->manager_id === (int) auth()->id()) ||
            ($evaluatorType === 'hr' && (int) $form->hr_reviewer_id === (int) auth()->id()) ||
            auth()->user()?->can('manage-performance-evaluation'),
            403
        );

        $score = PerformanceFormScore::query()->updateOrCreate(
            [
                'performance_form_id' => $form->id,
                'performance_form_template_item_id' => (int) data_get($validated, 'scoreForm.performance_form_template_item_id'),
                'evaluator_type' => $evaluatorType,
            ],
            [
                'score' => (float) data_get($validated, 'scoreForm.score'),
                'comment' => data_get($validated, 'scoreForm.comment'),
            ]
        );

        $column = $evaluatorType === 'manager' ? 'manager_status' : 'hr_status';
        $form->update([$column => 'submitted']);

        $service = app(PerformanceWeakAreaTrainingNeedService::class);
        $form = $form->fresh();
        $service->refreshFormResult($form);
        $service->syncForForm($form->fresh(['scores.item:id,training_competency_id,low_score_threshold,name', 'scores.form.personnel:id,position_id']))
            ->get($score->id);

        $this->scoreForm = [
            'performance_form_id' => null,
            'performance_form_template_item_id' => null,
            'evaluator_type' => 'manager',
            'score' => null,
            'comment' => '',
        ];
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.score_saved'));
    }

    public function startReviewAnswer(int $answerId): void
    {
        $answer = $this->pendingAnswers->firstWhere('id', $answerId);
        abort_if($answer === null, 403);

        $this->reviewForm = [
            'performance_test_attempt_answer_id' => $answer->id,
            'score' => $answer->question?->max_score,
            'feedback' => '',
        ];
    }

    public function saveAnswerReview(): void
    {
        $validated = $this->validate([
            'reviewForm.performance_test_attempt_answer_id' => 'required|exists:performance_test_attempt_answers,id',
            'reviewForm.score' => 'required|numeric|min:0|max:1000',
            'reviewForm.feedback' => 'nullable|string|max:2000',
        ], attributes: [
            'reviewForm.performance_test_attempt_answer_id' => __('performance_evaluation::dashboard.fields.answer'),
            'reviewForm.score' => __('performance_evaluation::dashboard.fields.review_score'),
            'reviewForm.feedback' => __('performance_evaluation::dashboard.fields.feedback'),
        ]);

        $answer = PerformanceTestAttemptAnswer::query()
            ->where('id', (int) data_get($validated, 'reviewForm.performance_test_attempt_answer_id'))
            ->whereHas('attempt.session', fn ($query) => $query->where('reviewer_id', auth()->id()))
            ->firstOrFail();

        app(PerformanceSkillMeasurementService::class)->reviewAnswer(
            $answer,
            (float) data_get($validated, 'reviewForm.score'),
            data_get($validated, 'reviewForm.feedback'),
            auth()->id()
        );

        $this->reviewForm = [
            'performance_test_attempt_answer_id' => null,
            'score' => null,
            'feedback' => '',
        ];
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.answer_reviewed'));
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.evaluator-workspace');
    }
}
