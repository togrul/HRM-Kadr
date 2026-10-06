<?php

namespace App\Modules\Demo\Console\Commands;

use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Console\Command;

class DemoListCommand extends Command
{
    protected $signature = 'demo:list';

    protected $description = 'Demo müştərilərin siyahısı və qalan günlər';

    public function handle(DemoTenantRepository $tenants): int
    {
        $rows = $tenants->all()->map(fn (DemoTenant $tenant): array => [
            $tenant->key,
            $tenant->name,
            $tenant->email,
            $tenant->expires_at->format('d.m.Y H:i'),
            $tenant->isExpired() ? 'bitib' : $tenant->daysLeft().' gün',
        ])->all();

        if ($rows === []) {
            $this->info('Hələ demo müştəri yoxdur.');

            return self::SUCCESS;
        }

        $this->table(['Açar', 'Ad', 'E-poçt', 'Bitir', 'Qalıb'], $rows);

        return self::SUCCESS;
    }
}
