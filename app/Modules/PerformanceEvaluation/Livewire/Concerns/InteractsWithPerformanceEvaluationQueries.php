<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceCycle;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestQuestionOption;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\Personnel;
use App\Models\TrainingCompetency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait InteractsWithPerformanceEvaluationQueries
{
    public function cycleOptions(): array
    {
        $base = PerformanceCycle::query()
            ->select('id', 'name as label')
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchCycle'),
            selectedId: data_get($this->evaluationForm, 'performance_cycle_id')
                ?: data_get($this->sessionForm, 'performance_cycle_id'),
            limit: 50
        );
    }

    public function templateOptions(): array
    {
        $base = PerformanceFormTemplate::query()
            ->select('id', DB::raw("COALESCE(code, name) as label"))
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchTemplate'),
            selectedId: data_get($this->sectionForm, 'performance_form_template_id')
                ?: data_get($this->evaluationForm, 'performance_form_template_id'),
            limit: 100
        );
    }

    public function sectionOptions(): array
    {
        $base = PerformanceFormTemplateSection::query()
            ->select('id', 'name as label')
            ->orderBy('sort_order')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchSection'),
            selectedId: data_get($this->itemForm, 'performance_form_template_section_id'),
            limit: 100
        );
    }

    public function competencyOptions(): array
    {
        $base = TrainingCompetency::query()
            ->select('id', 'name as label')
            ->where('is_active', true)
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchCompetency'),
            selectedId: data_get($this->itemForm, 'training_competency_id')
                ?: data_get($this->questionForm, 'training_competency_id'),
            limit: 100
        );
    }

    public function personnelOptions(string $searchProperty = 'searchPersonnel', string $selectedProperty = 'personnel_id'): array
    {
        $base = Personnel::query()
            ->select([
                'id',
                DB::raw("CONCAT(surname, ' ', name, ' ', patronymic, ' (#', tabel_no, ')') as label"),
            ])
            ->orderBy('surname')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'surname',
            searchTerm: $this->dropdownSearch($searchProperty),
            selectedId: data_get($this->evaluationForm, $selectedProperty)
                ?: data_get($this->sessionForm, $selectedProperty),
            limit: 100
        );
    }

    public function evaluatorOptions(string $searchProperty, string $selectedProperty): array
    {
        $base = User::query()
            ->select([
                'id',
                DB::raw("COALESCE(NULLIF(name, ''), email) as label"),
            ])
            ->orderBy('name')
            ->orderBy('email');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch($searchProperty),
            selectedId: data_get($this->evaluationForm, $selectedProperty)
                ?: data_get($this->sessionForm, $selectedProperty),
            limit: 100
        );
    }

    public function performanceFormOptions(): array
    {
        $base = PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->select([
                'performance_forms.id',
                DB::raw("CONCAT(performance_forms.id, ' - ', personnels.surname, ' ', personnels.name, ' (#', personnels.tabel_no, ')') as label"),
            ])
            ->orderByDesc('performance_forms.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'personnels.surname',
            searchTerm: $this->dropdownSearch('searchPerformanceForm'),
            selectedId: data_get($this->scoreForm, 'performance_form_id'),
            limit: 100
        );
    }

    public function templateItemOptions(): array
    {
        $base = PerformanceFormTemplateItem::query()
            ->select('id', 'name as label')
            ->orderBy('sort_order')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchTemplateItem'),
            selectedId: data_get($this->scoreForm, 'performance_form_template_item_id'),
            limit: 100
        );
    }

    public function testBankOptions(): array
    {
        $base = PerformanceTestBank::query()
            ->select('id', DB::raw("COALESCE(code, name) as label"))
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchTestBank'),
            selectedId: data_get($this->questionForm, 'performance_test_bank_id')
                ?: data_get($this->sessionForm, 'performance_test_bank_id'),
            limit: 100
        );
    }

    public function testQuestionOptions(): array
    {
        $sessionId = data_get($this->attemptAnswerForm, 'performance_test_session_id');
        $bankId = $sessionId ? PerformanceTestSession::query()->whereKey($sessionId)->value('performance_test_bank_id') : null;

        $base = PerformanceTestQuestion::query()
            ->select('id', DB::raw("SUBSTR(prompt, 1, 120) as label"))
            ->when($bankId, fn ($query) => $query->where('performance_test_bank_id', $bankId))
            ->orderBy('sort_order')
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'prompt',
            searchTerm: $this->dropdownSearch('searchTestQuestion'),
            selectedId: data_get($this->attemptAnswerForm, 'performance_test_question_id'),
            limit: 100
        );
    }

    public function testQuestionOptionChoices(): array
    {
        $questionId = data_get($this->attemptAnswerForm, 'performance_test_question_id');
        if (blank($questionId)) {
            return [];
        }

        return PerformanceTestQuestionOption::query()
            ->where('performance_test_question_id', $questionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'label'])
            ->map(fn ($item) => ['id' => $item->id, 'label' => $item->label])
            ->all();
    }

    public function testSessionOptions(): array
    {
        $base = PerformanceTestSession::query()
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->select([
                'performance_test_sessions.id',
                DB::raw("CONCAT(performance_test_banks.name, ' - ', personnels.surname, ' ', personnels.name, ' (#', personnels.tabel_no, ')') as label"),
            ])
            ->orderByDesc('performance_test_sessions.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'performance_test_banks.name',
            searchTerm: $this->dropdownSearch('searchTestSession'),
            selectedId: data_get($this->attemptAnswerForm, 'performance_test_session_id'),
            limit: 100
        );
    }

    public function attemptOptions(): array
    {
        $base = PerformanceTestAttempt::query()
            ->join('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->select([
                'performance_test_attempts.id',
                DB::raw("CONCAT('#', performance_test_attempts.id, ' - ', performance_test_banks.name, ' / ', personnels.surname, ' ', personnels.name) as label"),
            ])
            ->orderByDesc('performance_test_attempts.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'performance_test_banks.name',
            searchTerm: $this->dropdownSearch('searchTestAttempt'),
            selectedId: data_get($this->attemptSubmitForm, 'performance_test_attempt_id'),
            limit: 100
        );
    }

    public function reviewAnswerOptions(): array
    {
        $base = PerformanceTestAttemptAnswer::query()
            ->join('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
            ->join('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
            ->select([
                'performance_test_attempt_answers.id',
                DB::raw("CONCAT('#', performance_test_attempts.id, ' - ', SUBSTR(performance_test_questions.prompt, 1, 80)) as label"),
            ])
            ->where('performance_test_attempt_answers.review_status', 'pending')
            ->orderByDesc('performance_test_attempt_answers.id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'performance_test_questions.prompt',
            searchTerm: $this->dropdownSearch('searchReviewAnswer'),
            selectedId: data_get($this->reviewForm, 'performance_test_attempt_answer_id'),
            limit: 100
        );
    }

    public function getStatsProperty(): array
    {
        return (array) DB::selectOne(
            'select
                (select count(*) from performance_cycles) as `cycles`,
                (select count(*) from performance_form_templates) as `templates`,
                (select count(*) from performance_form_template_sections) as `sections`,
                (select count(*) from performance_form_template_items) as `items`,
                (select count(*) from performance_forms) as `forms`,
                (select count(*) from performance_form_scores) as `scores`,
                (select count(*) from performance_training_need_links) as `links`,
                (select count(*) from performance_test_banks) as `test_banks`,
                (select count(*) from performance_test_questions) as `test_questions`,
                (select count(*) from performance_test_sessions) as `test_sessions`,
                (select count(*) from performance_test_attempts) as `test_attempts`,
                (select count(*) from performance_test_training_need_links) as `test_need_links`'
        );
    }

    public function getRecentCyclesProperty()
    {
        return PerformanceCycle::query()
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function getRecentTemplatesProperty()
    {
        return PerformanceFormTemplate::query()
            ->withCount('sections')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function getRecentTemplateSectionsProperty()
    {
        return PerformanceFormTemplateSection::query()
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_form_template_sections.performance_form_template_id')
            ->select([
                'performance_form_template_sections.*',
                DB::raw('performance_form_templates.name as template_name'),
                DB::raw('performance_form_templates.code as template_code'),
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentTemplateItemsProperty()
    {
        return PerformanceFormTemplateItem::query()
            ->leftJoin('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_form_template_sections.performance_form_template_id')
            ->leftJoin('training_competencies', 'training_competencies.id', '=', 'performance_form_template_items.training_competency_id')
            ->select([
                'performance_form_template_items.*',
                DB::raw('performance_form_template_sections.name as section_name'),
                DB::raw('performance_form_templates.name as template_name'),
                DB::raw('training_competencies.name as competency_name'),
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentFormsProperty()
    {
        return PerformanceForm::query()
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->leftJoin('users as manager_users', 'manager_users.id', '=', 'performance_forms.manager_id')
            ->leftJoin('users as hr_users', 'hr_users.id', '=', 'performance_forms.hr_reviewer_id')
            ->select([
                'performance_forms.*',
                DB::raw('performance_cycles.name as cycle_name'),
                DB::raw('performance_form_templates.name as template_name'),
                DB::raw("CONCAT(personnels.surname, ' ', personnels.name, ' ', personnels.patronymic) as personnel_fullname"),
                DB::raw('manager_users.name as manager_name'),
                DB::raw('hr_users.name as hr_reviewer_name'),
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentWeakLinksProperty()
    {
        return PerformanceTrainingNeedLink::query()
            ->with([
                'form.personnel:id,tabel_no,surname,name,patronymic',
                'trainingNeed:id,priority,status,reason',
                'competency:id,name',
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getRecentTestBanksProperty()
    {
        return PerformanceTestBank::query()
            ->withCount('questions')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function getRecentTestAttemptsProperty()
    {
        return PerformanceTestAttempt::query()
            ->with([
                'session.bank:id,name',
                'session.personnel:id,tabel_no,surname,name,patronymic',
            ])
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getPendingReviewAnswersProperty()
    {
        return PerformanceTestAttemptAnswer::query()
            ->leftJoin('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
            ->leftJoin('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
            ->leftJoin('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->select([
                'performance_test_attempt_answers.*',
                DB::raw("CONCAT(personnels.surname, ' ', personnels.name, ' ', personnels.patronymic) as personnel_fullname"),
                DB::raw('personnels.tabel_no as personnel_tabel_no'),
                DB::raw('performance_test_questions.prompt as question_prompt'),
                DB::raw('performance_test_questions.question_type as question_type_name'),
            ])
            ->where('review_status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getSelectedScoreItemProperty(): ?PerformanceFormTemplateItem
    {
        $itemId = data_get($this->scoreForm, 'performance_form_template_item_id');

        if (! $itemId) {
            return null;
        }

        return PerformanceFormTemplateItem::query()
            ->with('competency:id,name')
            ->find($itemId);
    }
}
