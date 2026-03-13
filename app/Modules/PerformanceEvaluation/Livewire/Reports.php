<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\PerformanceForm;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTrainingNeedLink;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceEvaluationReportingService;
use App\Modules\PerformanceEvaluation\Exports\PerformanceEvaluationReportExport;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Isolate]
class Reports extends Component
{
    use InteractsWithPerformanceEvaluationAccess;

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
    }

    public function getReportStatsProperty(): array
    {
        return [
            'forms' => PerformanceForm::query()->count(),
            'weak_links' => PerformanceTrainingNeedLink::query()->count(),
            'test_sessions' => PerformanceTestSession::query()->count(),
            'test_attempts' => PerformanceTestAttempt::query()->count(),
            'test_answers' => PerformanceTestAttemptAnswer::query()->count(),
        ];
    }

    public function getRecentTestSessionsProperty()
    {
        return app(PerformanceEvaluationReportingService::class)->testSessionRows(limit: 8);
    }

    public function getRecentTestAttemptsProperty()
    {
        return app(PerformanceEvaluationReportingService::class)->testAttemptRows(limit: 8);
    }

    public function getRecentTestAnswersProperty()
    {
        return app(PerformanceEvaluationReportingService::class)->testAnswerRows(limit: 8);
    }

    public function exportPerformanceFormsReport()
    {
        $this->authorizePerformanceEvaluationExport();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->formRows(),
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

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->formSummaryRows(),
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

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->weakLinkRows(),
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

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->weakLinkPivotRows(),
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

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->auditRows(),
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

    public function exportPerformanceTestSessionsReport()
    {
        $this->authorizePerformanceEvaluationExport();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->testSessionRows(),
                [
                    '#',
                    __('performance_evaluation::dashboard.fields.cycle'),
                    __('performance_evaluation::dashboard.fields.test_bank'),
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.reviewer'),
                    __('performance_evaluation::dashboard.fields.status'),
                    __('performance_evaluation::dashboard.fields.scheduled_at'),
                    __('performance_evaluation::dashboard.fields.available_until'),
                    __('performance_evaluation::dashboard.fields.attempts_count'),
                ],
                'test_sessions'
            ),
            'performance-test-sessions-report.xlsx'
        );
    }

    public function exportPerformanceTestAttemptsReport()
    {
        $this->authorizePerformanceEvaluationExport();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->testAttemptRows(),
                [
                    '#',
                    __('performance_evaluation::dashboard.fields.attempt_no'),
                    __('performance_evaluation::dashboard.fields.test_bank'),
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.status'),
                    __('performance_evaluation::dashboard.fields.score'),
                    __('performance_evaluation::dashboard.fields.percentage'),
                    __('performance_evaluation::dashboard.fields.passed'),
                    __('performance_evaluation::dashboard.fields.submitted_at'),
                ],
                'test_attempts'
            ),
            'performance-test-attempts-report.xlsx'
        );
    }

    public function exportPerformanceTestAnswersReport()
    {
        $this->authorizePerformanceEvaluationExport();

        return Excel::download(
            new PerformanceEvaluationReportExport(
                app(PerformanceEvaluationReportingService::class)->testAnswerRows(),
                [
                    '#',
                    __('performance_evaluation::dashboard.fields.attempt'),
                    __('performance_evaluation::dashboard.fields.test_bank'),
                    __('performance_evaluation::dashboard.fields.personnel'),
                    __('performance_evaluation::dashboard.fields.tabel_no'),
                    __('performance_evaluation::dashboard.fields.question_type'),
                    __('performance_evaluation::dashboard.fields.prompt'),
                    __('performance_evaluation::dashboard.fields.option'),
                    __('performance_evaluation::dashboard.fields.answer_text'),
                    __('performance_evaluation::dashboard.fields.is_correct'),
                    __('performance_evaluation::dashboard.fields.auto_score'),
                    __('performance_evaluation::dashboard.fields.review_score'),
                    __('performance_evaluation::dashboard.fields.final_score'),
                    __('performance_evaluation::dashboard.fields.review_status'),
                    __('performance_evaluation::dashboard.fields.feedback'),
                ],
                'test_answers'
            ),
            'performance-test-answers-report.xlsx'
        );
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.reports');
    }
}
