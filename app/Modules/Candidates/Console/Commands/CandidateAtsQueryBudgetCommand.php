<?php

namespace App\Modules\Candidates\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\CandidateApplication;
use App\Modules\Candidates\Application\Services\CandidateAtsCompletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateAtsQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'candidates:ats-query-budget
        {--allow-empty : Return success when ATS dataset is empty}
        {--aging-budget= : Max query count for requisition aging}
        {--application-budget= : Max query count for active application snapshot}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Candidate ATS completion flows';

    public function handle(CandidateAtsCompletionService $service): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Candidate ATS tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('job_requisitions')->exists()
            || DB::table('candidate_applications')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            return $this->outputPayload($this->skippedPayload('candidate_ats_dataset_empty'));
        }

        $budgets = [
            'requisition_aging' => max(1, (int) ($this->option('aging-budget') ?: config('candidates.performance.query_budget.ats_requisition_aging', 15))),
            'application_snapshot' => max(1, (int) ($this->option('application-budget') ?: config('candidates.performance.query_budget.ats_application_snapshot', 18))),
        ];

        $results = [
            $this->probe('requisition_aging', $budgets['requisition_aging'], fn () => $service->requisitionAging()),
            $this->probe('application_snapshot', $budgets['application_snapshot'], fn () => $this->applicationSnapshot()),
        ];

        return $this->outputPayload($this->payload($results));
    }

    private function hasRequiredTables(): bool
    {
        foreach (['candidates', 'candidate_applications', 'job_openings', 'job_requisitions'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function applicationSnapshot(): void
    {
        CandidateApplication::query()
            ->with([
                'candidate:id,surname,name,patronymic,phone,birthdate',
                'opening:id,title,job_requisition_id,structure_id,position_id,owner_id',
                'opening.requisition:id,title,status,approval_status,owner_id,requested_by',
                'opening.structure:id,name',
                'opening.position:id,name',
                'interviews:id,candidate_application_id,status,scheduled_at,score',
                'offers:id,candidate_application_id,status,start_date,expires_at',
                'stageEvents:id,candidate_application_id,stage_key,action,actor_id,occurred_at',
            ])
            ->latest('id')
            ->limit(15)
            ->get();
    }

    private function skippedPayload(string $reason): array
    {
        return [
            'summary' => [
                'failed_probes' => 0,
                'over_budget_probes' => 0,
                'passed_probes' => 0,
                'skipped' => true,
                'reason' => $reason,
            ],
            'results' => [],
        ];
    }

    private function payload(array $results): array
    {
        return [
            'summary' => [
                'failed_probes' => collect($results)->where('status', 'failed')->count(),
                'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
                'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
            ],
            'results' => $results,
        ];
    }

    private function outputPayload(array $payload): int
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($payload['results'])->map(fn (array $result): array => [
                    $result['flow'],
                    $result['status'],
                    $result['queries'],
                    $result['budget'],
                    $result['over_budget'] ? 'yes' : 'no',
                    $result['elapsed_ms'],
                    $result['db_time_ms'],
                    $result['error'] ?? '-',
                ])->all()
            );
        }

        return ($payload['summary']['failed_probes'] === 0 && $payload['summary']['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
