<?php

namespace App\Modules\Demo\Application\Services;

use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sorğunu bir demo müştərinin dünyasına keçirir: baza, audit bazası, cache və fayl
 * diskləri həmin müştəriyə aid olanlarla əvəz olunur. Kod eyni qalır — yalnız
 * konfiqurasiya dəyişir, ona görə heç bir modul demodan xəbərdar olmur.
 */
class DemoTenantSwitcher
{
    private ?DemoTenant $current = null;

    /** @var array<string, array<string, mixed>> */
    private array $originalDisks;

    private string $originalCachePath;

    private string $originalCachePrefix;

    public function __construct()
    {
        $this->originalDisks = (array) config('filesystems.disks', []);
        $this->originalCachePath = (string) config('cache.stores.file.path', storage_path('framework/cache/data'));
        $this->originalCachePrefix = (string) config('cache.prefix', '');
    }

    public function current(): ?DemoTenant
    {
        return $this->current;
    }

    public function activate(DemoTenant $tenant): void
    {
        $this->current = $tenant;

        $this->switchDatabases($tenant);
        $this->switchCache($tenant->key);
        $this->switchDisks($tenant->key);

        // Əvvəlcədən yüklənmiş istifadəçi/icazə varsa (şablon bazadan) — unudulsun.
        Auth::forgetGuards();
        $registrar = app(PermissionRegistrar::class);
        $registrar->initializeCache();
        $registrar->clearPermissionsCollection();
    }

    /** Müştəri faylları üçün qovluqlar (silmə zamanı da istifadə olunur). */
    public function storagePaths(string $key): array
    {
        return [
            storage_path('app/demo/'.$key),
            storage_path('app/public/demo/'.$key),
            $this->cachePath($key),
        ];
    }

    private function switchDatabases(DemoTenant $tenant): void
    {
        $default = (string) config('database.default');

        config([
            "database.connections.{$default}.database" => $tenant->database,
            'database.connections.audit.database' => $tenant->audit_database,
        ]);

        DB::purge($default);
        DB::purge('audit');
    }

    /**
     * Cache açarlarının çoxu qlobaldır (`menus:header`, `structure-accessible-{userId}`…).
     * File cache prefiksi tanımadığı üçün hər müştəriyə ayrıca qovluq verilir; digər
     * sürücülər üçün prefiks də dəyişdirilir.
     */
    private function switchCache(string $key): void
    {
        config([
            'cache.stores.file.path' => $this->cachePath($key),
            'cache.stores.file.lock_path' => $this->cachePath($key),
            'cache.prefix' => $this->originalCachePrefix.'demo_'.$key.'_',
        ]);

        app('cache')->forgetDriver(array_keys((array) config('cache.stores', [])));
        app()->forgetInstance('cache.store');
        Cache::clearResolvedInstances();
    }

    private function switchDisks(string $key): void
    {
        $publicRoot = storage_path('app'.DIRECTORY_SEPARATOR.'public');
        $privateRoot = storage_path('app');
        $disks = $this->originalDisks;

        foreach ($disks as $name => $disk) {
            if (($disk['driver'] ?? null) !== 'local' || ! isset($disk['root'])) {
                continue;
            }

            $root = (string) $disk['root'];

            if (Str::startsWith($root, $publicRoot)) {
                $disks[$name]['root'] = $publicRoot.'/demo/'.$key.Str::after($root, $publicRoot);

                if (isset($disk['url'])) {
                    $disks[$name]['url'] = Str::replaceFirst('/storage', '/storage/demo/'.$key, (string) $disk['url']);
                }
            } elseif (Str::startsWith($root, $privateRoot)) {
                $disks[$name]['root'] = $privateRoot.'/demo/'.$key.Str::after($root, $privateRoot);
            }
        }

        config(['filesystems.disks' => $disks]);
        Storage::forgetDisk(array_keys($disks));
    }

    private function cachePath(string $key): string
    {
        return rtrim($this->originalCachePath, '/').'/demo/'.$key;
    }
}
