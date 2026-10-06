<?php

namespace App\Modules\Demo\Console\Commands;

use App\Models\User;
use App\Modules\Demo\Application\Services\DemoDatabaseCloner;
use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class DemoCreateCommand extends Command
{
    protected $signature = 'demo:create
        {key : Qısa açar, yalnız kiçik latın hərfi və rəqəm (məs. abc)}
        {email : Müştərinin giriş e-poçtu}
        {--name= : Müştərinin / şirkətin adı}
        {--days= : Neçə gün aktiv qalsın (standart: config demo.default_days)}
        {--password= : Parol (verilməsə təsadüfi yaradılır)}';

    protected $description = 'Yeni demo müştəri: ayrıca təmiz baza + admin istifadəçi';

    public function handle(
        DemoTenantRepository $tenants,
        DemoDatabaseCloner $cloner,
        DemoTenantSwitcher $switcher,
    ): int {
        $key = Str::lower((string) $this->argument('key'));
        $email = Str::lower(trim((string) $this->argument('email')));
        $days = (int) ($this->option('days') ?: config('demo.default_days', 3));
        $password = (string) ($this->option('password') ?: Str::password(12, symbols: false));

        if (! preg_match('/^[a-z0-9]{2,20}$/', $key)) {
            $this->error('Açar 2–20 simvol olmalıdır: yalnız kiçik latın hərfləri və rəqəmlər.');

            return self::FAILURE;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('E-poçt düzgün deyil.');

            return self::FAILURE;
        }

        if ($days < 1) {
            $this->error('Gün sayı ən azı 1 olmalıdır.');

            return self::FAILURE;
        }

        $tenants->ensureTable();

        if ($tenants->find($key) !== null) {
            $this->error("«{$key}» açarı ilə demo artıq var.");

            return self::FAILURE;
        }

        if (($other = $tenants->findByUserEmail($email)) !== null) {
            $this->error("Bu e-poçt artıq «{$other->key}» demosunda istifadə olunur.");

            return self::FAILURE;
        }

        $database = config('demo.database_prefix', 'hrm_demo_').$key;
        $auditDatabase = $database.'_audit';

        foreach ([$database, $auditDatabase] as $name) {
            if ($cloner->databaseExists($name)) {
                $this->error("«{$name}» bazası artıq mövcuddur. Əvvəlcə silin və ya başqa açar seçin.");

                return self::FAILURE;
            }
        }

        $this->info("Baza qurulur: {$database} …");

        try {
            $main = $cloner->cloneMain($database);
            $cloner->cloneAudit($auditDatabase);

            $tenant = DemoTenant::query()->create([
                'key' => $key,
                'name' => (string) ($this->option('name') ?: $key),
                'email' => $email,
                'database' => $database,
                'audit_database' => $auditDatabase,
                'expires_at' => now()->addDays($days),
            ]);

            $switcher->activate($tenant);

            $user = User::query()->create([
                'name' => (string) ($this->option('name') ?: 'Demo Admin'),
                'email' => $email,
                'password' => $password,
                'is_active' => true,
                'must_reset_password' => false,
            ]);
            $user->assignRole((string) config('demo.role', 'Admin'));
        } catch (Throwable $e) {
            $cloner->drop($database);
            $cloner->drop($auditDatabase);
            DemoTenant::query()->where('key', $key)->delete();

            $this->error('Demo yaradıla bilmədi, yarımçıq bazalar silindi: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Demo hazırdır. Müştəriyə göndərin:');
        $this->table(['', ''], [
            ['Ünvan', (string) config('app.url')],
            ['E-poçt', $email],
            ['Parol', $password],
            ['Bitmə vaxtı', $tenant->expires_at->format('d.m.Y H:i')],
            ['Cədvəl', "{$main['tables']} cədvəl, {$main['copied']} soraq məlumatla"],
        ]);

        return self::SUCCESS;
    }
}
