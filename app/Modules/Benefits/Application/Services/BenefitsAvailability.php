<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\Setting;
use App\Services\Profiles\ProfileState;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * İmtiyazlar modulunun bu quraşdırmada (və ya demo kirayəçidə) açıq olub-olmaması.
 *
 * Üç qat:
 *  1. Modul kataloqu (config/modules.php → `benefits.enabled`, profil `modules` override-u) —
 *     provider ümumiyyətlə yüklənirmi. Söndürülübsə heç nə işləmir.
 *  2. Şirkət növü (quraşdırma profili, APP_TYPE → config/profiles.php `features.benefits`) —
 *     standart vəziyyət: özəl/korporativ profillərdə açıq, «Publik» (dövlət) və hərbi profildə
 *     bağlı, çünki dövlət qulluğunda imtiyazlar adətən qanunla müəyyən olunur. Profildə açar
 *     yoxdursa açıq sayılır.
 *  3. Adminin əl ilə seçimi — `settings` cədvəlində bool sətir; sətir yoxdursa (2) qüvvədədir.
 *
 * Dəyər hər sorğuda bir dəfə oxunur (scoped binding); demo kirayəçidə baza dəyişəndən sonra
 * (middleware) hesablanır.
 */
class BenefitsAvailability
{
    public const SETTING = 'Benefits module enabled';

    public const PROFILE_FEATURE = 'benefits';

    public const CACHE_KEY = 'benefits:module-override';

    private ?bool $resolved = null;

    public function __construct(private readonly ProfileState $profiles) {}

    public function enabled(): bool
    {
        return $this->resolved ??= $this->installed() && ($this->override() ?? $this->profileDefault());
    }

    /** Provider modul kataloqunda açıqdırmı (profilin `modules` override-u nəzərə alınmaqla). */
    public function installed(): bool
    {
        return (bool) ($this->profiles->modules()['benefits']['enabled'] ?? false);
    }

    /** Şirkət növünün (profilin) standart dəyəri. */
    public function profileDefault(): bool
    {
        $features = $this->profiles->features();

        return array_key_exists(self::PROFILE_FEATURE, $features) ? (bool) $features[self::PROFILE_FEATURE] : true;
    }

    public function activeProfile(): string
    {
        return $this->profiles->active();
    }

    /**
     * Adminin açıq seçimi; seçim yoxdursa null (profil üzrə). Dəyər keşdə saxlanır (hər səhifədə
     * sorğu olmasın); sətir dəyişəndə (setOverride və ya Tənzimləmələr ekranı — Setting hadisələri,
     * bax BenefitsServiceProvider) keş silinir. Demo kirayəçidə keş prefiksi kirayəçiyə görədir.
     */
    public function override(): ?bool
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_string($cached)) {
            $cached = $this->readOverride();

            if ($cached === null) {
                return null;
            }

            Cache::forever(self::CACHE_KEY, $cached);
        }

        return match ($cached) {
            'on' => true,
            'off' => false,
            default => null,
        };
    }

    public static function forgetCachedOverride(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return 'on'|'off'|'auto'|null null — settings cədvəli hələ yoxdur (keşlənmir)
     */
    private function readOverride(): ?string
    {
        if (! InstalledTables::has('settings')) {
            return null;
        }

        try {
            $value = Setting::query()->where('name', self::SETTING)->value('value');
        } catch (Throwable) {
            return null;
        }

        if ($value === null || trim((string) $value) === '') {
            return 'auto';
        }

        return match (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
            true => 'on',
            false => 'off',
            null => 'auto',
        };
    }

    /**
     * Admin seçimini yazır (null — profil üzrə standarta qayıt) və audit jurnalına salır.
     */
    public function setOverride(?bool $enabled): void
    {
        $before = $this->enabled();

        if ($enabled === null) {
            Setting::query()->where('name', self::SETTING)->delete();
        } else {
            Setting::query()->updateOrCreate(['name' => self::SETTING], ['value' => $enabled ? '1' : '0', 'type' => 'bool']);
        }

        self::forgetCachedOverride();
        $this->resolved = null;

        activity('benefits')
            ->causedBy(auth()->user())
            ->withProperties([
                'profile' => $this->activeProfile(),
                'profile_default' => $this->profileDefault(),
                'override' => $enabled,
                'old' => $before,
                'new' => $this->enabled(),
            ])
            ->event('benefits_module_toggled')
            ->log('benefits_module_toggled');
    }

    /** Yaddaşdakı dəyəri unut (test və uzunömürlü proseslər üçün). */
    public function forget(): void
    {
        $this->resolved = null;
    }

    /**
     * @return array{installed: bool, profile: string, profile_default: bool, override: ?bool, enabled: bool}
     */
    public function state(): array
    {
        return [
            'installed' => $this->installed(),
            'profile' => $this->activeProfile(),
            'profile_default' => $this->profileDefault(),
            'override' => $this->override(),
            'enabled' => $this->enabled(),
        ];
    }
}
