<?php

namespace App\Modules\Demo\Console\Commands;

use App\Modules\Demo\Application\Services\DemoTenantRepository;
use Illuminate\Console\Command;

class DemoExtendCommand extends Command
{
    protected $signature = 'demo:extend {key} {--days=3 : Neçə gün əlavə olunsun}';

    protected $description = 'Demo müddətini uzadır (bitmiş demonu da yenidən açır)';

    public function handle(DemoTenantRepository $tenants): int
    {
        $tenant = $tenants->find((string) $this->argument('key'));
        $days = (int) $this->option('days');

        if ($tenant === null) {
            $this->error('Belə demo yoxdur.');

            return self::FAILURE;
        }

        if ($days < 1) {
            $this->error('Gün sayı ən azı 1 olmalıdır.');

            return self::FAILURE;
        }

        // Bitmiş demo bu gündən, aktiv demo öz bitmə vaxtından uzadılır.
        $from = $tenant->isExpired() ? now() : $tenant->expires_at;
        $tenant->update(['expires_at' => $from->copy()->addDays($days)]);

        $this->info("«{$tenant->key}» demosu {$tenant->expires_at->format('d.m.Y H:i')} tarixinə qədər aktivdir.");

        return self::SUCCESS;
    }
}
