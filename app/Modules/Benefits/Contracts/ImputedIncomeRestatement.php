<?php

namespace App\Modules\Benefits\Contracts;

/**
 * İmtiyazın natura formasında gəlir sətrini (Payroll-un ödədiyi birdəfəlik sətir) Benefits-in
 * İNDİKİ qaydası ilə yenidən ifadə edir. Payroll-un «Vergi düzəliş hesabatı» keçmiş aylarda artıq
 * tutulmuş DSMF / işsizlik / tibbi haqqını görmək üçün işlədir (məs. yemək kartının gündəlik norması
 * sonradan tətbiq olunub — DVX izahı 10.07.2026). Heç nə yazmır.
 *
 * @see \App\Modules\Benefits\Application\Services\BenefitPayrollSync
 */
interface ImputedIncomeRestatement
{
    /**
     * null — sətir bu modulun deyil və ya köhnə qayda ilə yazılmayıb (dəyişmir); [] — indiki qayda ilə
     * gəlir deyil (norma daxilində, qaimə-faktura).
     *
     * @return list<array{code: string, amount: float, taxable: bool, affects_social: bool}>|null
     */
    public function restate(string $sourceKey, string $code, float $amount): ?array;
}
