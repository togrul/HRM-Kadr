<?php

namespace App\Modules\Payroll\Application\Services;

/**
 * ƏM m.176.1: hər dəfə əmək haqqı verilərkən tutulan bütün məbləğlərin ümumi miqdarı işçiyə
 * verilməli olan əmək haqqının 20 faizindən artıq ola bilməz. «Verilməli olan əmək haqqı» —
 * hesablanmış məbləğ çıxılsın vergi və məcburi sosial ödənişlər («İcra haqqında» Qanun m.64 ilə
 * eyni yanaşma). Qanunda işçinin razılığı ilə bu həddin aşılmasına icazə verən norma yoxdur, Ali
 * Məhkəmə də 20 %-dən artıq tutulmaya qabaqcadan razılıq verən müqavilə şərtini etibarsız sayıb —
 * ona görə konfiqurasiya həddi yalnız AŞAĞI sala bilər, 20 %-dən yuxarı qaldıra bilməz.
 *
 * 50 % (bir neçə icra sənədi — ƏM m.176.2, «İcra haqqında» m.65.1–65.2) və 70 % (aliment, sağlamlığa
 * vurulmuş ziyan və s. — ƏM m.176.3, m.65.3) hədləri icra sənədi üzrə tutulmalara aiddir; sistemdə
 * icra sənədi reyestri olmadığı üçün burada tətbiq edilmir (docs/payroll-legal-basis.md §4).
 */
final class DeductionCap
{
    /** ƏM m.176.1 — ümumi hədd. */
    public const LEGAL_RATIO = 0.20;

    /** Konfiqurasiya edilmiş pay, [0; 0.20] aralığına sıxılmış. */
    public static function ratio(string $configKey = 'payroll.deduction_cap_ratio'): float
    {
        return max(0.0, min(self::LEGAL_RATIO, (float) config($configKey, self::LEGAL_RATIO)));
    }

    /**
     * Bu ödənişdə daha nə qədər tutula bilər: verilməli olan əmək haqqının həddi çıxılsın artıq
     * tutulan qeyri-statutor məbləğlər. Qəpiyə AŞAĞI yuvarlaqlaşdırılır ki, hədd aşılmasın.
     */
    public static function room(float $wageDue, float $alreadyDeducted, ?float $ratio = null): float
    {
        $ratio ??= self::ratio();
        $room = floor((max(0.0, $wageDue) * $ratio - $alreadyDeducted) * 100 + 1e-6) / 100;

        return max(0.0, round($room, 2));
    }
}
