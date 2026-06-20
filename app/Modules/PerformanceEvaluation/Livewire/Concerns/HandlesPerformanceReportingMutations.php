<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Modules\PerformanceEvaluation\Application\Services\PerformanceEvaluationReportingService;
use App\Modules\PerformanceEvaluation\Exports\PerformanceEvaluationReportExport;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesPerformanceReportingMutations
{
    public function exportPerformanceFormsReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->formRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.cycle'),
                    __('performance_evaluation::dashboard.fields.template'),
                    __('performance_evaluation::dashboard.fields.manager'),
                    __('performance_evaluation::dashboard.fields.hr_reviewer'),
                    __('performance_evaluation::dashboard.fields.score'),
                    __('performance_evaluation::dashboard.fields.final_category'),
                    __('performance_evaluation::dashboard.fields.self_status'),
                    __('performance_evaluation::dashboard.fields.manager_status'),
                    __('performance_evaluation::dashboard.fields.hr_status'),
                ],
                'forms'
            ),
            'performance-forms-report.xlsx'
        );
    }

    public function exportPerformanceSummaryReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->formSummaryRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.cycle'),
                    __('performance_evaluation::dashboard.fields.template'),
                    __('performance_evaluation::dashboard.fields.forms_count'),
                    __('performance_evaluation::dashboard.fields.average_score'),
                    __('performance_evaluation::dashboard.fields.high_count'),
                    __('performance_evaluation::dashboard.fields.medium_count'),
                    __('performance_evaluation::dashboard.fields.weak_count'),
                ],
                'summary'
            ),
            'performance-summary-report.xlsx'
        );
    }

    public function exportPerformanceWeakLinksReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->weakLinkRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.competency'),
                    __('performance_evaluation::dashboard.fields.score'),
                    __('performance_evaluation::dashboard.fields.final_category'),
                    __('performance_evaluation::dashboard.fields.priority'),
                    __('performance_evaluation::dashboard.fields.status'),
                    __('performance_evaluation::dashboard.fields.reason'),
                ],
                'weak_links'
            ),
            'performance-weak-links-report.xlsx'
        );
    }

    public function exportPerformanceWeakPivotReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->weakLinkPivotRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.competency'),
                    __('performance_evaluation::dashboard.fields.priority'),
                    __('performance_evaluation::dashboard.fields.status'),
                    __('performance_evaluation::dashboard.fields.links_count'),
                ],
                'weak_pivot'
            ),
            'performance-weak-pivot-report.xlsx'
        );
    }

    public function exportPerformanceAuditReport()
    {
        $this->authorizePerformanceEvaluationExport();
        $rows = app(PerformanceEvaluationReportingService::class)->auditRows();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                $rows,
                [
                    __('performance_evaluation::dashboard.fields.audit_subject'),
                    __('performance_evaluation::dashboard.fields.audit_subject_id'),
                    __('performance_evaluation::dashboard.fields.audit_event'),
                    __('performance_evaluation::dashboard.fields.audit_actor'),
                    __('performance_evaluation::dashboard.fields.audit_created_at'),
                    __('performance_evaluation::dashboard.fields.audit_properties'),
                ],
                'audit'
            ),
            'performance-audit-report.xlsx'
        );
    }
}
