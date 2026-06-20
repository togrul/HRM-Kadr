<?php

namespace App\Modules\Notifications\Console\Commands;

use App\Models\User;
use App\Modules\Notifications\Livewire\AnalyticsPanel;
use App\Modules\Notifications\Livewire\AnnouncementComposer;
use App\Modules\Notifications\Livewire\ApprovalQueue;
use App\Modules\Notifications\Livewire\CampaignBoard;
use App\Modules\Notifications\Livewire\HistoryBoard;
use App\Modules\Notifications\Livewire\OverviewPanel;
use App\Modules\Notifications\Livewire\RuleManager;
use App\Modules\Notifications\Livewire\SettingsHub;
use App\Modules\Notifications\Livewire\TemplateManager;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Spatie\Permission\Models\Permission;

class NotificationsRenderBenchmarkCommand extends Command
{
    protected $signature = 'notifications:render-benchmark
        {--shell-response-budget=70000 : Max response size for shell render}
        {--shell-render-budget=200 : Max render time in ms for shell render}
        {--overview-response-budget=220000 : Max response size for overview island render}
        {--overview-render-budget=300 : Max render time in ms for overview island render}
        {--analytics-response-budget=160000 : Max response size for analytics panel render}
        {--analytics-render-budget=220 : Max render time in ms for analytics panel render}
        {--history-response-budget=180000 : Max response size for history board render}
        {--history-render-budget=240 : Max render time in ms for history board render}
        {--templates-response-budget=120000 : Max response size for template manager render}
        {--templates-render-budget=250 : Max render time in ms for template manager render}
        {--rules-response-budget=160000 : Max response size for rule manager render}
        {--rules-render-budget=280 : Max render time in ms for rule manager render}
        {--approval-response-budget=70000 : Max response size for approval queue render}
        {--approval-render-budget=200 : Max render time in ms for approval queue render}
        {--announcements-response-budget=120000 : Max response size for announcement composer render}
        {--announcements-render-budget=250 : Max render time in ms for announcement composer render}
        {--campaigns-response-budget=160000 : Max response size for campaign board render}
        {--campaigns-render-budget=280 : Max render time in ms for campaign board render}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark render time and payload size for Notification settings hub islands';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Notification tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $user = $this->resolveObserverUser();

        if (! $user) {
            $this->error('No active user was found for notification benchmark.');

            return self::FAILURE;
        }

        $budgets = [
            'settings_shell_render' => $this->budgetPair('shell'),
            'overview_panel_render' => $this->budgetPair('overview'),
            'analytics_panel_render' => $this->budgetPair('analytics'),
            'history_board_render' => $this->budgetPair('history'),
            'template_manager_render' => $this->budgetPair('templates'),
            'rule_manager_render' => $this->budgetPair('rules'),
            'approval_queue_render' => $this->budgetPair('approval'),
            'announcement_composer_render' => $this->budgetPair('announcements'),
            'campaign_board_render' => $this->budgetPair('campaigns'),
        ];

        $results = [];
        $results[] = $this->probe('settings_shell_render', $budgets['settings_shell_render'], fn () => $profiler->measureRender(
            $user,
            SettingsHub::class,
            [],
            ['notifications_tab' => 'templates'],
        ));
        $results[] = $this->probe('overview_panel_render', $budgets['overview_panel_render'], fn () => $profiler->measureRender($user, OverviewPanel::class));
        $results[] = $this->probe('analytics_panel_render', $budgets['analytics_panel_render'], fn () => $profiler->measureRender($user, AnalyticsPanel::class));
        $results[] = $this->probe('history_board_render', $budgets['history_board_render'], fn () => $profiler->measureRender($user, HistoryBoard::class));
        $results[] = $this->probe('template_manager_render', $budgets['template_manager_render'], fn () => $profiler->measureRender($user, TemplateManager::class));
        $results[] = $this->probe('rule_manager_render', $budgets['rule_manager_render'], fn () => $profiler->measureRender($user, RuleManager::class));
        $results[] = $this->probe('approval_queue_render', $budgets['approval_queue_render'], fn () => $profiler->measureRender($user, ApprovalQueue::class));
        $results[] = $this->probe('announcement_composer_render', $budgets['announcement_composer_render'], fn () => $profiler->measureRender($user, AnnouncementComposer::class));
        $results[] = $this->probe('campaign_board_render', $budgets['campaign_board_render'], fn () => $profiler->measureRender($user, CampaignBoard::class));

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];
        $this->outputPayload($payload);

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function hasRequiredTables(): bool
    {
        foreach (['notification_templates', 'notification_rules', 'notification_campaigns', 'notification_dispatches', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function resolveObserverUser(): ?User
    {
        $user = User::query()->where('is_active', true)->orderBy('id')->first();

        if ($user) {
            $this->primeBenchmarkPermissions($user);
        }

        return $user;
    }

    private function primeBenchmarkPermissions(User $user): void
    {
        $permissions = [
            'access-settings',
            'manage-notification-templates',
            'manage-notification-rules',
            'manage-notification-campaigns',
            'approve-notification-campaigns',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo($permissions);
    }

    private function budgetPair(string $prefix): array
    {
        return [
            'response_bytes' => (int) $this->option($prefix.'-response-budget'),
            'render_ms' => (float) $this->option($prefix.'-render-budget'),
        ];
    }

    private function probe(string $flow, array $budget, callable $callback): array
    {
        try {
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
                'memory_bytes' => data_get($metrics, 'memory_bytes'),
                'peak_memory_bytes' => data_get($metrics, 'peak_memory_bytes'),
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
                'memory_bytes' => null,
                'peak_memory_bytes' => null,
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
            ['flow', 'status', 'render_ms', 'response_bytes', 'html_bytes', 'snapshot_bytes', 'effects_bytes', 'over_budget', 'error'],
            collect($payload['results'])->map(fn (array $result) => [
                $result['flow'],
                $result['status'],
                $result['render_ms'],
                $result['response_bytes'],
                $result['html_bytes'],
                $result['snapshot_bytes'],
                $result['effects_bytes'],
                $result['over_budget'] ? 'yes' : 'no',
                $result['error'] ?? '-',
            ])->all()
        );
    }
}
