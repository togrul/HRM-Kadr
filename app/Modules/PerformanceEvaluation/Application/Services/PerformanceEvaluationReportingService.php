<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceForm;
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
}
