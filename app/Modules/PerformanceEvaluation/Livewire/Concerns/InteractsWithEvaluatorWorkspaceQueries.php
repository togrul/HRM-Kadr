<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceTestAttemptAnswer;

trait InteractsWithEvaluatorWorkspaceQueries
{
    private const ASSIGNED_FORMS_LIMIT = 18;

    private const PENDING_ANSWERS_LIMIT = 18;

    public function getAssignedFormsProperty()
    {
        return $this->rememberRuntime(
            'performanceEvaluation.evaluatorWorkspace.assignedForms.'
            .md5(implode('|', [
                (string) auth()->id(),
                $this->searchAssignedForms,
                $this->assignedRoleFilter,
                $this->assignedStatusFilter,
            ])),
            function () {
                $userId = auth()->id();

                return PerformanceForm::query()
                    ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
                    ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
                    ->leftJoin('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
                    ->leftJoin('users as manager_users', 'manager_users.id', '=', 'performance_forms.manager_id')
                    ->leftJoin('users as hr_users', 'hr_users.id', '=', 'performance_forms.hr_reviewer_id')
                    ->select([
                        'performance_forms.*',
                        'performance_cycles.name as cycle_name',
                        'performance_form_templates.name as template_name',
                        'performance_form_templates.code as template_code',
                        'personnels.surname as personnel_surname',
                        'personnels.name as personnel_name',
                        'personnels.patronymic as personnel_patronymic',
                        'personnels.tabel_no as personnel_tabel_no',
                        'manager_users.name as manager_name',
                        'hr_users.name as hr_reviewer_name',
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
                                    ->where(function ($pendingQuery) use ($userId): void {
                                        $pendingQuery
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
                    ->limit(self::ASSIGNED_FORMS_LIMIT)
                    ->get()
                    ->each(function (PerformanceForm $form): void {
                        $form->setAttribute('personnel_fullname', $this->buildFullname(
                            $form->personnel_surname,
                            $form->personnel_name,
                            $form->personnel_patronymic,
                        ));
                    });
            }
        );
    }

    public function getScoreCaptureFormCatalogProperty(): array
    {
        return $this->rememberRuntime('performanceEvaluation.evaluatorWorkspace.scoreCaptureFormCatalog', function (): array {
            $userId = (int) auth()->id();

            return $this->assignedForms
                ->map(function (PerformanceForm $form) use ($userId): array {
                    return [
                        'id' => (int) $form->id,
                        'label' => ((string) ($form->personnel_fullname ?: '-')) . ' / ' . ((string) ($form->template_name ?: $form->template_code ?: '-')),
                        'template_id' => (int) $form->performance_form_template_id,
                        'evaluator_type' => (int) $form->manager_id === $userId ? 'manager' : 'hr',
                    ];
                })
                ->values()
                ->all();
        });
    }

    public function getPendingAnswersProperty()
    {
        return $this->rememberRuntime(
            'performanceEvaluation.evaluatorWorkspace.pendingAnswers.'
            .md5(implode('|', [
                (string) auth()->id(),
                $this->searchPendingAnswers,
                $this->pendingQuestionTypeFilter,
            ])),
            function () {
                return PerformanceTestAttemptAnswer::query()
                    ->leftJoin('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
                    ->leftJoin('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
                    ->leftJoin('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
                    ->leftJoin('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
                    ->leftJoin('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
                    ->select([
                        'performance_test_attempt_answers.*',
                        'performance_test_attempts.id as attempt_id',
                        'performance_test_sessions.reviewer_id',
                        'personnels.surname as personnel_surname',
                        'personnels.name as personnel_name',
                        'personnels.patronymic as personnel_patronymic',
                        'personnels.tabel_no as personnel_tabel_no',
                        'performance_test_banks.name as bank_name',
                        'performance_test_questions.prompt as question_prompt',
                        'performance_test_questions.question_type as question_type_name',
                        'performance_test_questions.max_score as question_max_score',
                    ])
                    ->where('review_status', 'pending')
                    ->where('performance_test_sessions.reviewer_id', auth()->id())
                    ->when($this->searchPendingAnswers !== '', function ($query): void {
                        $search = '%'.$this->searchPendingAnswers.'%';

                        $query->where('performance_test_questions.prompt', 'like', $search);
                    })
                    ->when($this->pendingQuestionTypeFilter !== 'all', function ($query): void {
                        $query->where('performance_test_questions.question_type', $this->pendingQuestionTypeFilter);
                    })
                    ->latest('performance_test_attempt_answers.id')
                    ->limit(self::PENDING_ANSWERS_LIMIT)
                    ->get()
                    ->each(function (PerformanceTestAttemptAnswer $answer): void {
                        $answer->setAttribute('personnel_fullname', $this->buildFullname(
                            $answer->personnel_surname,
                            $answer->personnel_name,
                            $answer->personnel_patronymic,
                        ));
                    });
            }
        );
    }

    public function getAssignedFormsSummaryProperty(): array
    {
        return $this->rememberRuntime('performanceEvaluation.evaluatorWorkspace.assignedFormsSummary', function (): array {
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
        });
    }

    public function getAssignedFormProgressProperty(): array
    {
        return $this->rememberRuntime('performanceEvaluation.evaluatorWorkspace.assignedFormProgress', function (): array {
            $forms = $this->assignedForms;

            if ($forms->isEmpty()) {
                return [];
            }

            $templateItemCounts = PerformanceFormTemplateItem::query()
                ->join('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
                ->whereIn('performance_form_template_sections.performance_form_template_id', $forms->pluck('performance_form_template_id')->unique()->all())
                ->selectRaw('performance_form_template_sections.performance_form_template_id as template_id, COUNT(*) as item_count')
                ->groupBy('performance_form_template_sections.performance_form_template_id')
                ->pluck('item_count', 'template_id');

            $scoreCounts = PerformanceFormScore::query()
                ->whereIn('performance_form_id', $forms->pluck('id')->all())
                ->selectRaw('performance_form_id, evaluator_type, COUNT(*) as score_count')
                ->groupBy('performance_form_id', 'evaluator_type')
                ->get()
                ->groupBy('performance_form_id');

            $userId = (int) auth()->id();

            return $forms->mapWithKeys(function (PerformanceForm $form) use ($scoreCounts, $templateItemCounts, $userId): array {
                $evaluatorType = (int) $form->manager_id === $userId ? 'manager' : 'hr';
                $totalItems = (int) ($templateItemCounts[$form->performance_form_template_id] ?? 0);
                $completedItems = (int) optional($scoreCounts->get($form->id))->firstWhere('evaluator_type', $evaluatorType)?->score_count;

                return [
                    $form->id => [
                        'total' => $totalItems,
                        'completed' => $completedItems,
                        'remaining' => max($totalItems - $completedItems, 0),
                    ],
                ];
            })->all();
        });
    }

    private function buildFullname(?string $surname, ?string $name, ?string $patronymic): string
    {
        return trim(implode(' ', array_filter([$surname, $name, $patronymic])));
    }
}
