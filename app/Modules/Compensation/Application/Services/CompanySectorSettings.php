<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\CompensationRegime;
use App\Models\Setting;
use App\Services\Profiles\ProfileState;
use Illuminate\Validation\ValidationException;

/**
 * Şirkətin vergi/sosial sığorta baxımından sektoru və ondan çıxan standart əmək haqqı rejimi
 * (docs/payroll-legal-basis.md §8).
 *
 * Vergi Məcəlləsinin m.101.1-1 güzəşt cədvəli, «Sosial sığorta haqqında» Qanunun m.14.4 pilləsi və
 * «Tibbi sığorta haqqında» Qanunun 2500 manatlıq pilləsi yalnız «neft-qaz sahəsində fəaliyyəti olmayan
 * və qeyri-dövlət sektoruna aid edilən» işəgötürənə aiddir. Neft-qaz sahəsində fəaliyyəti olan özəl
 * şirkət də, dövlət sektoruna aid olan şirkət də ümumi qaydalara (m.101.1 1-ci cədvəl, m.14.3,
 * m.15-10.1.2) tabedir — sistemdə bu, «state» rejimidir. Hərbi qulluqçular üçün ayrıca «military»
 * rejimi var.
 *
 * Ayar Admin → Tənzimləmələr cədvəlində (`settings`) saxlanır; təyin edilməyibsə quraşdırma profilindən
 * (APP_TYPE: military → hərbi, public «Publik» → dövlət, qalanları → özəl qeyri-neft-qaz) götürülür.
 * Ayar yalnız YENİ təyinatların standart rejimini müəyyən edir — mövcud təyinatlar avtomatik dəyişmir
 * (bax RegimeAlignmentService).
 */
class CompanySectorSettings
{
    public const SETTING = 'Payroll company sector';

    public const PRIVATE_NON_OIL = 'private_non_oil';

    public const OIL_GAS = 'oil_gas';

    public const STATE = 'state';

    public const MILITARY = 'military';

    /** Sektor → `compensation_regimes.code`. */
    public const REGIME_FOR_SECTOR = [
        self::PRIVATE_NON_OIL => 'private',
        self::OIL_GAS => 'state',
        self::STATE => 'state',
        self::MILITARY => 'military',
    ];

    /** Quraşdırma profili (config/profiles.php, APP_TYPE) → sektor, ayar boş olanda. */
    private const SECTOR_FOR_PROFILE = [
        'military' => self::MILITARY,
        'public' => self::STATE,
    ];

    public function __construct(private readonly ProfileState $profile) {}

    /**
     * @return list<string>
     */
    public static function sectors(): array
    {
        return array_keys(self::REGIME_FOR_SECTOR);
    }

    public function sector(): string
    {
        $stored = $this->storedSector();

        return $stored ?? (self::SECTOR_FOR_PROFILE[$this->profile->active()] ?? self::PRIVATE_NON_OIL);
    }

    /** Ayar açıq şəkildə seçilibmi (yoxsa profildən götürülür). */
    public function isExplicit(): bool
    {
        return $this->storedSector() !== null;
    }

    public function defaultRegimeCode(): string
    {
        return self::REGIME_FOR_SECTOR[$this->sector()];
    }

    /** Standart rejimin id-si; rejim kataloqda yoxdursa və ya deaktivdirsə null. */
    public function defaultRegimeId(): ?int
    {
        $id = CompensationRegime::query()
            ->where('code', $this->defaultRegimeCode())
            ->where('is_active', true)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Sektoru dəyişir və audit jurnalına yazır. Mövcud işçilərin rejiminə toxunmur.
     *
     * @throws ValidationException
     */
    public function setSector(string $sector, string $reason): void
    {
        if (! in_array($sector, self::sectors(), true)) {
            throw ValidationException::withMessages(['sector' => __('compensation::regimes.messages.invalid_sector')]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['sectorReason' => __('compensation::regimes.messages.reason_required')]);
        }

        $old = $this->sector();
        $setting = Setting::query()->firstOrNew(['name' => self::SETTING]);
        $setting->forceFill(['value' => $sector, 'type' => 'string'])->save();

        activity('compensation')
            ->causedBy(auth()->user())
            ->withProperties(['old' => $old, 'new' => $sector, 'reason' => trim($reason)])
            ->event('company_sector_changed')
            ->log('company_sector_changed');
    }

    private function storedSector(): ?string
    {
        $value = trim((string) Setting::query()->where('name', self::SETTING)->value('value'));

        return in_array($value, self::sectors(), true) ? $value : null;
    }
}
