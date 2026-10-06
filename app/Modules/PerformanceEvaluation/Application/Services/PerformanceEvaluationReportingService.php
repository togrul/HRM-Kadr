<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTrainingNeedLink;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class PerformanceEvaluationReportingService
{
    public function formSummaryRows(): EloquentCollection
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

    public function weakLinkPivotRows(): EloquentCollection
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

    public function formRows(): EloquentCollection
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

    public function weakLinkRows(): EloquentCollection
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

    public function auditRows(): EloquentCollection
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

    public function testSessionRows(?int $limit = null): EloquentCollection
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

    public function testAttemptRows(?int $limit = null): EloquentCollection
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

    public function testAnswerRows(?int $limit = null): EloquentCollection
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

    public function testQuestionAnalysisRows(?int $limit = null): Collection
    {
        $query = PerformanceTestAttemptAnswer::query()
            ->join('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
            ->join('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
            ->join('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->join('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
            ->selectRaw('
                performance_test_questions.id,
                performance_test_banks.name as bank_name,
                performance_test_questions.prompt as question_prompt,
                performance_test_questions.question_type,
                COUNT(*) as answers_count,
                SUM(CASE WHEN performance_test_attempt_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers_count,
                SUM(CASE WHEN performance_test_attempt_answers.review_status = "pending" THEN 1 ELSE 0 END) as pending_reviews_count,
                ROUND(COALESCE(AVG(performance_test_attempt_answers.final_score), 0), 2) as average_final_score
            ')
            ->groupBy([
                'performance_test_questions.id',
                'performance_test_banks.name',
                'performance_test_questions.prompt',
                'performance_test_questions.question_type',
            ])
            ->orderByDesc('answers_count');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(function ($row) {
            $answersCount = max(1, (int) $row->answers_count);
            $row->correct_rate = round(((int) $row->correct_answers_count / $answersCount) * 100, 2);

            return $row;
        });
    }

    public function reviewerTurnaroundRows(?int $limit = null): Collection
    {
        $rows = PerformanceTestAttemptAnswer::query()
            ->join('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'performance_test_attempt_answers.reviewed_by')
            ->whereNotNull('performance_test_attempt_answers.reviewed_at')
            ->get([
                'reviewers.id as reviewer_id',
                'reviewers.name as reviewer_name',
                'reviewers.email as reviewer_email',
                'performance_test_attempts.submitted_at',
                'performance_test_attempt_answers.reviewed_at',
            ])
            ->groupBy('reviewer_id')
            ->map(function ($group) {
                $minutes = $group
                    ->filter(fn ($row) => $row->submitted_at && $row->reviewed_at)
                    ->map(function ($row) {
                        return round(
                            \Illuminate\Support\Carbon::parse($row->submitted_at)
                                ->diffInMinutes(\Illuminate\Support\Carbon::parse($row->reviewed_at)),
                            2
                        );
                    });

                return (object) [
                    'reviewer_id' => $group->first()->reviewer_id,
                    'reviewer_name' => $group->first()->reviewer_name ?: $group->first()->reviewer_email,
                    'reviewed_answers_count' => $group->count(),
                    'average_review_minutes' => $minutes->isEmpty() ? 0.0 : round($minutes->avg(), 2),
                ];
            })
            ->sortByDesc('reviewed_answers_count')
            ->values();

        if ($limit !== null) {
            return $rows->take($limit)->values();
        }

        return $rows;
    }

    public function personnelOutcomeRows(?int $limit = null): Collection
    {
        $query = PerformanceTestAttempt::query()
            ->join('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
            ->join('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
            ->selectRaw('
                personnels.id as personnel_id,
                CONCAT_WS(" ", personnels.surname, personnels.name, personnels.patronymic) as personnel_fullname,
                personnels.tabel_no as personnel_tabel_no,
                COUNT(*) as attempts_count,
                SUM(CASE WHEN performance_test_attempts.passed = 1 THEN 1 ELSE 0 END) as passed_attempts_count,
                ROUND(COALESCE(AVG(performance_test_attempts.percentage), 0), 2) as average_percentage
            ')
            ->whereIn('performance_test_attempts.status', ['completed', 'review_pending'])
            ->groupBy([
                'personnels.id',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'personnels.tabel_no',
            ])
            ->orderByDesc('average_percentage');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(function ($row) {
            $attemptsCount = max(1, (int) $row->attempts_count);
            $row->pass_rate = round(((int) $row->passed_attempts_count / $attemptsCount) * 100, 2);

            return $row;
        });
    }
}
