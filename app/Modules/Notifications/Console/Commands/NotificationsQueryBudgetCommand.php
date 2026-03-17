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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Throwable;

class NotificationsQueryBudgetCommand extends Command
{
    protected $signature = 'notifications:query-budget
        {--shell-budget=2 : Max query count for shell render}
        {--overview-budget=18 : Max query count for overview island render}
        {--analytics-budget=6 : Max query count for analytics panel render}
        {--history-budget=8 : Max query count for history board render}
        {--templates-budget=6 : Max query count for template manager render}
        {--rules-budget=10 : Max query count for rule manager render}
        {--approval-budget=3 : Max query count for approval queue render}
        {--announcements-budget=8 : Max query count for announcement composer render}
        {--campaigns-budget=6 : Max query count for campaign board render}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Notification settings hub and islands';

    public function handle(): int
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
            'settings_shell_render' => (int) $this->option('shell-budget'),
            'overview_panel_render' => (int) $this->option('overview-budget'),
            'analytics_panel_render' => (int) $this->option('analytics-budget'),
            'history_board_render' => (int) $this->option('history-budget'),
            'template_manager_render' => (int) $this->option('templates-budget'),
            'rule_manager_render' => (int) $this->option('rules-budget'),
            'approval_queue_render' => (int) $this->option('approval-budget'),
            'announcement_composer_render' => (int) $this->option('announcements-budget'),
            'campaign_board_render' => (int) $this->option('campaigns-budget'),
        ];

        $results = [];
        $results[] = $this->probe('settings_shell_render', $budgets['settings_shell_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::withQueryParams(['notifications_tab' => 'templates'])
                ->test(SettingsHub::class);
        });
        $results[] = $this->probe('overview_panel_render', $budgets['overview_panel_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(OverviewPanel::class);
        });
        $results[] = $this->probe('analytics_panel_render', $budgets['analytics_panel_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AnalyticsPanel::class);
        });
        $results[] = $this->probe('history_board_render', $budgets['history_board_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(HistoryBoard::class);
        });
        $results[] = $this->probe('template_manager_render', $budgets['template_manager_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(TemplateManager::class);
        });
        $results[] = $this->probe('rule_manager_render', $budgets['rule_manager_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(RuleManager::class);
        });
        $results[] = $this->probe('approval_queue_render', $budgets['approval_queue_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(ApprovalQueue::class);
        });
        $results[] = $this->probe('announcement_composer_render', $budgets['announcement_composer_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AnnouncementComposer::class);
        });
        $results[] = $this->probe('campaign_board_render', $budgets['campaign_board_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(CampaignBoard::class);
        });

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
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

    private function probe(string $flow, int $budget, callable $callback): array
    {
        DB::flushQueryLog();
        DB::connection()->flushQueryLog();
        DB::enableQueryLog();

        try {
            $startedAt = microtime(true);
            $callback();
            $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            $fingerprints = collect($queries)
                ->map(fn (array $query) => ($query['query'] ?? '').'|'.json_encode($query['bindings'] ?? [], JSON_UNESCAPED_UNICODE))
                ->all();

            return [
                'flow' => $flow,
                'status' => 'ok',
                'queries' => $queryCount,
                'duplicates' => $queryCount - count(array_unique($fingerprints)),
                'budget' => $budget,
                'over_budget' => $queryCount > $budget,
                'elapsed_ms' => $elapsedMs,
                'db_time_ms' => round((float) collect($queries)->sum('time'), 2),
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'flow' => $flow,
                'status' => 'failed',
                'queries' => null,
                'duplicates' => null,
                'budget' => $budget,
                'over_budget' => false,
                'elapsed_ms' => null,
                'db_time_ms' => null,
                'error' => $throwable->getMessage(),
            ];
        } finally {
            DB::disableQueryLog();
        }
    }

    private function outputPayload(array $payload): void
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->table(
            ['flow', 'status', 'queries', 'duplicates', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
            collect($payload['results'])->map(fn (array $result) => [
                $result['flow'],
                $result['status'],
                $result['queries'],
                $result['duplicates'],
                $result['budget'],
                $result['over_budget'] ? 'yes' : 'no',
                $result['elapsed_ms'],
                $result['db_time_ms'],
                $result['error'] ?? '-',
            ])->all()
        );
    }
}
