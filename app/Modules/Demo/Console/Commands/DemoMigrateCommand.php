<?php

namespace App\Modules\Demo\Console\Commands;

use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * `migrate` on a deploy only reaches the template database; every demo customer has its
 * own cloned copy. This runs the pending migrations inside each customer's database.
 */
class DemoMigrateCommand extends Command
{
    protected $signature = 'demo:migrate';

    protected $description = 'Hər demo müştərinin bazasında gözləyən miqrasiyaları işə salır';

    public function handle(DemoTenantRepository $tenants, DemoTenantSwitcher $switcher): int
    {
        $list = $tenants->all();

        if ($list->isEmpty()) {
            $this->info('Hələ demo müştəri yoxdur.');

            return self::SUCCESS;
        }

        $default = (string) config('database.default');
        $original = [
            "database.connections.{$default}.database" => config("database.connections.{$default}.database"),
            'database.connections.audit.database' => config('database.connections.audit.database'),
        ];
        $failed = 0;

        try {
            $list->each(function (DemoTenant $tenant) use ($switcher, &$failed): void {
                try {
                    $switcher->activate($tenant);
                    Artisan::call('migrate', ['--force' => true]);
                    $this->line("<info>✓</info> {$tenant->key} ({$tenant->database})");
                    $this->line(trim(Artisan::output()));
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("✗ {$tenant->key}: {$e->getMessage()}");
                }
            });
        } finally {
            config($original);
            DB::purge($default);
            DB::purge('audit');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
