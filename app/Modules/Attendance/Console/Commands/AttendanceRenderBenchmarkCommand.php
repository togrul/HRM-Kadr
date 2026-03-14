<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Models\User;
use App\Modules\Attendance\Livewire\CalendarRegimes;
use App\Modules\Attendance\Livewire\ManualEntries;
use App\Modules\Attendance\Livewire\MonthClose;
use App\Modules\Attendance\Livewire\OvertimeBoard;
use App\Modules\Attendance\Livewire\ShiftManagement;
use App\Support\Livewire\LivewireComponentProfiler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class AttendanceRenderBenchmarkCommand extends Command
{
    protected $signature = 'attendance:render-benchmark
        {--year= : Target year}
        {--month= : Target month}
        {--manual-response-budget= : Max response size for manual entries render}
        {--manual-render-budget= : Max render time in ms for manual entries render}
        {--overtime-response-budget= : Max response size for overtime board render}
        {--overtime-render-budget= : Max render time in ms for overtime board render}
        {--shifts-response-budget= : Max response size for shift management render}
        {--shifts-render-budget= : Max render time in ms for shift management render}
        {--calendar-response-budget= : Max response size for calendar regimes render}
        {--calendar-render-budget= : Max render time in ms for calendar regimes render}
        {--month-close-response-budget= : Max response size for month close render}
        {--month-close-render-budget= : Max render time in ms for month close render}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Attendance admin workbench flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveObserverUser();
        if (! $user) {
            $this->error('No user with Attendance permissions was found for render benchmarking.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $year = is_numeric($this->option('year')) ? (int) $this->option('year') : (int) $now->year;
        $month = is_numeric($this->option('month')) ? (int) $this->option('month') : (int) $now->month;

        $budgets = [
            'manual_entries_render' => $this->budgetPair('manual_entries_render', 'manual'),
            'overtime_board_render' => $this->budgetPair('overtime_board_render', 'overtime'),
            'shift_management_render' => $this->budgetPair('shift_management_render', 'shifts'),
            'calendar_regimes_render' => $this->budgetPair('calendar_regimes_render', 'calendar'),
            'month_close_render' => $this->budgetPair('month_close_render', 'month_close'),
        ];

        $results = [];
        $results[] = $this->probe('manual_entries_render', $budgets['manual_entries_render'], fn () => $profiler->measureRender($user, ManualEntries::class, ['embedded' => true]));
        $results[] = $this->probe('overtime_board_render', $budgets['overtime_board_render'], fn () => $profiler->measureRender($user, OvertimeBoard::class, ['year' => $year, 'month' => $month]));
        $results[] = $this->probe('shift_management_render', $budgets['shift_management_render'], fn () => $profiler->measureRender($user, ShiftManagement::class));
        $results[] = $this->probe('calendar_regimes_render', $budgets['calendar_regimes_render'], fn () => $profiler->measureRender($user, CalendarRegimes::class, ['year' => $year, 'month' => $month]));
        $results[] = $this->probe('month_close_render', $budgets['month_close_render'], fn () => $profiler->measureRender($user, MonthClose::class, ['year' => $year, 'month' => $month]));

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];
        $this->outputPayload($payload);

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0) ? self::SUCCESS : self::FAILURE;
    }

    private function resolveObserverUser(): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->canAny([
                'show-attendance',
                'add-attendance-manual',
                'approve-attendance-overtime',
                'manage-attendance-shifts',
                'manage-attendance-calendars',
                'manage-attendance-month-close',
            ]));
    }

    private function budgetPair(string $flow, string $optionPrefix): array
    {
        $optionPrefix = str_replace('_', '-', $optionPrefix);
        $responseBudget = (int) ($this->option($optionPrefix.'-response-budget') ?: config("attendance.performance.render_budget.$flow.response_bytes", 180000));
        $renderBudget = (float) ($this->option($optionPrefix.'-render-budget') ?: config("attendance.performance.render_budget.$flow.render_ms", 180));

        return [
            'response_bytes' => max(1, $responseBudget),
            'render_ms' => max(1, $renderBudget),
        ];
    }

    private function probe(string $flow, array $budget, callable $callback): array
    {
        try {
            // Warm the Livewire component/view path so the reported metric reflects steady-state render cost.
            $callback();
            $metrics = $callback();
            $renderMs = (float) data_get($metrics, 'render_ms', 0);
            $responseBytes = (int) data_get($metrics, 'response_bytes', 0);
            $exceeded = [];

            if ($responseBytes > (int) $budget['response_bytes']) {
                $exceeded[] = 'response_bytes';
            }

            if ($renderMs > (float) $budget['render_ms']) {
                $exceeded[] = 'render_ms';
            }

            return [
                'flow' => $flow,
                'status' => 'ok',
                'render_ms' => $renderMs,
                'response_bytes' => $responseBytes,
                'html_bytes' => data_get($metrics, 'html_bytes'),
                'snapshot_bytes' => data_get($metrics, 'snapshot_bytes'),
                'effects_bytes' => data_get($metrics, 'effects_bytes'),
                'budget' => $budget,
                'over_budget' => $exceeded !== [],
                'exceeded' => $exceeded,
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'flow' => $flow,
                'status' => 'failed',
                'render_ms' => null,
                'response_bytes' => null,
                'html_bytes' => null,
                'snapshot_bytes' => null,
                'effects_bytes' => null,
                'budget' => $budget,
                'over_budget' => false,
                'exceeded' => [],
                'error' => $throwable->getMessage(),
            ];
        }
    }

    private function outputPayload(array $payload): void
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->table(
            ['flow', 'status', 'render_ms', 'response_bytes', 'budget_response', 'budget_render_ms', 'over_budget', 'html_bytes', 'snapshot_bytes', 'effects_bytes', 'error'],
            collect($payload['results'])->map(fn (array $result) => [
                $result['flow'],
                $result['status'],
                $result['render_ms'],
                $result['response_bytes'],
                data_get($result, 'budget.response_bytes'),
                data_get($result, 'budget.render_ms'),
                $result['over_budget'] ? implode(',', $result['exceeded']) : 'no',
                $result['html_bytes'],
                $result['snapshot_bytes'],
                $result['effects_bytes'],
                $result['error'] ?? '-',
            ])->all()
        );
    }
}
