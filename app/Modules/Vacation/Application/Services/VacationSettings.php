<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Məzuniyyət uçotunun qurum ayarları (Admin → Tənzimləmələr):
 *   - uçotun başlama tarixi: bu tarixdən əvvəl başlamış iş illəri avtomatik hesablanmır —
 *     onların qalığı açılış qalığı (və ya köhnə təqvim ili balansı) ilə daxil edilir;
 *   - işləmə dövründə kompensasiya: ƏM m.144.2 yalnız xitamda kompensasiyanı açıq tənzimləyir,
 *     işləmə dövründə ödənişə (m.135.2) hüquqi şərh birmənalı deyil — standart olaraq bağlıdır.
 */
class VacationSettings
{
    public const LEDGER_START = 'Vacation ledger start date';

    public const COMPENSATION_WITHOUT_TERMINATION = 'Vacation compensation without termination allowed';

    /** ƏM m.131.1: birinci iş ili üçün məzuniyyət 6 ay işlədikdən sonra. */
    public const FIRST_YEAR_WAIT_MONTHS = 6;

    /**
     * İş ilinə daxil olmayan məzuniyyət növləri (ƏM m.132.2 — m.127 üzrə qismən ödənişli uşağa
     * qulluq məzuniyyəti): bu əmr şablonları ilə verilmiş məzuniyyət günləri qədər iş ili uzanır.
     */
    public const WORK_YEAR_EXCLUDED_TEMPLATES = ['usaga_qulluq_mezuniyyeti'];

    public function ledgerStart(): ?CarbonImmutable
    {
        $value = trim((string) Setting::query()->where('name', self::LEDGER_START)->value('value'));

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    public function compensationWithoutTermination(): bool
    {
        $value = mb_strtolower(trim((string) Setting::query()->where('name', self::COMPENSATION_WITHOUT_TERMINATION)->value('value')));

        return in_array($value, ['1', 'true', 'yes', 'bəli', 'on'], true);
    }
}
