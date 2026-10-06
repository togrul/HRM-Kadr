<?php

namespace App\Modules\Demo\Application\Services;

use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Demo müştərilərinin siyahısı. Cədvəl miqrasiya ilə yox, ilk `demo:create` zamanı
 * yaradılır — beləliklə əsas (prod) bazada heç vaxt yaranmır.
 */
class DemoTenantRepository
{
    private const PROBE_CONNECTION = 'demo_probe';

    public function ensureTable(): void
    {
        $schema = Schema::connection(DemoTenant::CONNECTION);

        if ($schema->hasTable('demo_tenants')) {
            return;
        }

        $schema->create('demo_tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('database');
            $table->string('audit_database');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function find(string $key): ?DemoTenant
    {
        if (! $this->tableExists()) {
            return null;
        }

        return DemoTenant::query()->where('key', $key)->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, DemoTenant> */
    public function all()
    {
        if (! $this->tableExists()) {
            return DemoTenant::query()->newModelInstance()->newCollection();
        }

        return DemoTenant::query()->orderBy('expires_at')->get();
    }

    /**
     * Bu e-poçtun istifadəçisi hansı demo bazasındadır? Müştəri sayı az olduğu üçün
     * (2–3) hər bazada sadəcə axtarılır — ayrıca e-poçt xəritəsi saxlanmır.
     */
    public function findByUserEmail(string $email): ?DemoTenant
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        foreach ($this->all() as $tenant) {
            if ($this->tenantHasUser($tenant, $email)) {
                return $tenant;
            }
        }

        return null;
    }

    public function tenantHasUser(DemoTenant $tenant, string $email): bool
    {
        $config = config('database.connections.'.DemoTenant::CONNECTION);
        $config['database'] = $tenant->database;
        config(['database.connections.'.self::PROBE_CONNECTION => $config]);
        DB::purge(self::PROBE_CONNECTION);

        try {
            return DB::connection(self::PROBE_CONNECTION)
                ->table('users')
                ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
                ->exists();
        } catch (Throwable) {
            return false; // baza silinib və ya əlçatmazdır — bu müştəri sayılmır
        } finally {
            DB::purge(self::PROBE_CONNECTION);
        }
    }

    private function tableExists(): bool
    {
        try {
            return Schema::connection(DemoTenant::CONNECTION)->hasTable('demo_tenants');
        } catch (Throwable) {
            return false;
        }
    }
}
