<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTrainingNeedLink;
use Spatie\Activitylog\Models\Activity;

class PerformanceEvaluationReportingService
{
    public function formSummaryRows()
    {
        return PerformanceForm::query()
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
            ->selectRaw('
                performance_cycles.name as cycle_name,
                performance_form_templates.name as template_name,
                COUNT(*) as forms_count,
                ROUND(COALESCE(AVG(performance_forms.final_score), 0), 2) as average_score,
                SUM(CASE WHEN performance_forms.final_category = "high" THEN 1 ELSE 0 END) as high_count,
                SUM(CASE WHEN performance_forms.final_category = "medium" THEN 1 ELSE 0 END) as medium_count,
                SUM(CASE WHEN performance_forms.final_category = "weak" THEN 1 ELSE 0 END) as weak_count
            ')
            ->groupBy('performance_cycles.name', 'performance_form_templates.name')
            ->orderBy('performance_cycles.name')
            ->orderBy('performance_form_templates.name')
            ->get();
    }

    public function weakLinkPivotRows()
    {
        return PerformanceTrainingNeedLink::query()
            ->leftJoin('training_need_items', 'training_need_items.id', '=', 'performance_training_need_links.training_need_item_id')
            ->leftJoin('training_competencies', 'training_competencies.id', '=', 'performance_training_need_links.training_competency_id')
            ->selectRaw('
                COALESCE(training_competencies.name, "-") as competency_name,
                COALESCE(training_need_items.priority, "medium") as priority,
                COALESCE(training_need_items.status, "draft") as status,
                COUNT(*) as links_count
            ')
            ->groupBy('training_competencies.name', 'training_need_items.priority', 'training_need_items.status')
            ->orderBy('training_competencies.name')
            ->get();
    }

    public function formRows()
    {
        return PerformanceForm::query()
            ->with([
                'cycle:id,name',
                'template:id,name,code',
                'personnel:id,surname,name,patronymic,tabel_no',
                'manager:id,name,email',
                'hrReviewer:id,name,email',
            ])
            ->latest('id')
            ->get();
    }

    public function weakLinkRows()
    {
        return PerformanceTrainingNeedLink::query()
            ->with([
                'form:id,personnel_id,performance_cycle_id,performance_form_template_id,final_score,final_category',
                'form.personnel:id,surname,name,patronymic,tabel_no',
                'trainingNeed:id,priority,status,reason',
                'competency:id,name',
            ])
            ->latest('id')
            ->get();
    }

    public function auditRows()
    {
        return Activity::query()
            ->whereIn('subject_type', [
                \App\Models\PerformanceCycle::class,
                \App\Models\PerformanceFormTemplate::class,
                \App\Models\PerformanceFormTemplateSection::class,
                \App\Models\PerformanceFormTemplateItem::class,
                \App\Models\PerformanceForm::class,
                \App\Models\PerformanceFormScore::class,
                \App\Models\PerformanceTestSession::class,
                \App\Models\PerformanceTestAttempt::class,
                \App\Models\PerformanceTestAttemptAnswer::class,
            ])
            ->with('causer:id,name,email')
            ->latest('id')
            ->limit(500)
            ->get();
    }

    public function testSessionRows(?int $limit = null)
    {
        $query = PerformanceTestSession::query()
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_test_sessions.performance_cycle_id')
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'performance_test_sessions.reviewer_id')
            ->leftJoin('performance_test_attempts', 'performance_test_attempts.performance_test_session_id', '=', 'performance_test_sessions.id')
            ->selectRaw('
                performance_test_sessions.id,
                performance_cycles.name as cycle_name,
                performance_test_banks.name as bank_name,
                CONCAT_WS(" ", personnels.surname, personnels.name, personnels.patronymic) as personnel_fullname,
                personnels.tabel_no as personnel_tabel_no,
                COALESCE(NULLIF(reviewers.name, ""), reviewers.email) as reviewer_name,
                performance_test_sessions.status,
                performance_test_sessions.scheduled_at,
                performance_test_sessions.available_until,
                COUNT(DISTINCT performance_test_attempts.id) as attempts_count
            ')
            ->groupBy([
                'performance_test_sessions.id',
                'performance_cycles.name',
                'performance_test_banks.name',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'personnels.tabel_no',
                'reviewers.name',
                'reviewers.email',
                'performance_test_sessions.status',
                'performance_test_sessions.scheduled_at',
                'performance_test_sessions.available_until',
            ])
            ->latest('performance_test_sessions.id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function testAttemptRows(?int $limit = null)
    {
        $query = PerformanceTestAttempt::query()
            ->join('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->selectRaw('
                performance_test_attempts.id,
                performance_test_attempts.attempt_no,
                performance_test_banks.name as bank_name,
                CONCAT_WS(" ", personnels.surname, personnels.name, personnels.patronymic) as personnel_fullname,
                personnels.tabel_no as personnel_tabel_no,
                performance_test_attempts.status,
                performance_test_attempts.score,
                performance_test_attempts.percentage,
                performance_test_attempts.passed,
                performance_test_attempts.submitted_at
            ')
            ->latest('performance_test_attempts.id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function testAnswerRows(?int $limit = null)
    {
        $query = PerformanceTestAttemptAnswer::query()
            ->join('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
            ->join('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->join('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
            ->leftJoin('performance_test_question_options', 'performance_test_question_options.id', '=', 'performance_test_attempt_answers.selected_option_id')
            ->selectRaw('
                performance_test_attempt_answers.id,
                performance_test_attempts.id as attempt_id,
                performance_test_banks.name as bank_name,
                CONCAT_WS(" ", personnels.surname, personnels.name, personnels.patronymic) as personnel_fullname,
                personnels.tabel_no as personnel_tabel_no,
                performance_test_questions.question_type,
                performance_test_questions.prompt as question_prompt,
                performance_test_question_options.label as selected_option_label,
                performance_test_attempt_answers.answer_text,
                performance_test_attempt_answers.is_correct,
                performance_test_attempt_answers.auto_score,
                performance_test_attempt_answers.review_score,
                performance_test_attempt_answers.final_score,
                performance_test_attempt_answers.review_status,
                performance_test_attempt_answers.feedback
            ')
            ->latest('performance_test_attempt_answers.id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
