<?php

namespace App\Modules\Demo\Console\Commands;

use App\Modules\Demo\Application\Services\DemoDatabaseCloner;
use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DemoDeleteCommand extends Command
{
    protected $signature = 'demo:delete
        {key? : Silinəcək demonun açarı}
        {--expired= : Açar əvəzinə: neçə gündən çox əvvəl bitmiş bütün demoları sil}
        {--force : Təsdiq soruşma}';

    protected $description = 'Demo müştərini bazaları və faylları ilə birlikdə tam silir';

    public function handle(DemoTenantRepository $tenants, DemoDatabaseCloner $cloner, DemoTenantSwitcher $switcher): int
    {
        $targets = $this->targets($tenants);

        if ($targets === []) {
            $this->info('Silinəcək demo tapılmadı.');

            return self::SUCCESS;
        }

        $keys = implode(', ', array_map(fn (DemoTenant $tenant): string => $tenant->key, $targets));

        if (! $this->option('force') && ! $this->confirm("Bu demolar və bütün məlumatları silinəcək: {$keys}. Davam edilsin?")) {
            return self::FAILURE;
        }

        foreach ($targets as $tenant) {
            $cloner->drop($tenant->database);
            $cloner->drop($tenant->audit_database);

            foreach ($switcher->storagePaths($tenant->key) as $path) {
                File::deleteDirectory($path);
            }

            $tenant->delete();
            $this->info("«{$tenant->key}» silindi.");
        }

        return self::SUCCESS;
    }

    /** @return list<DemoTenant> */
    private function targets(DemoTenantRepository $tenants): array
    {
        $olderThan = $this->option('expired');

        if ($olderThan !== null) {
            $cutoff = now()->subDays((int) $olderThan);

            return $tenants->all()
                ->filter(fn (DemoTenant $tenant): bool => $tenant->expires_at->lt($cutoff))
                ->values()
                ->all();
        }

        $tenant = $tenants->find((string) $this->argument('key'));

        if ($tenant === null) {
            $this->error('Açar verin (və ya --expired=GÜN).');
        }

        return $tenant === null ? [] : [$tenant];
    }
}
