<?php

namespace App\Modules\Compensation\Domain\Contracts;

/**
 * İşçinin həmin ay qüvvədə olan gəlir vergisi güzəşti kartları (Vergi Məcəlləsi m.102.1-1, 102.2–102.5).
 */
interface TaxReliefReadRepository
{
    /**
     * Ayda (dövrdə) qüvvədə olan güzəşt kateqoriyalarının kodları (`statutory_exemptions.code`).
     *
     * $legal = false — əmək haqqı hesablaması: kart `payroll_from` tarixindən (kilidlənmiş aylardan
     * sonra) tətbiq olunur. $legal = true — vergi düzəliş hesabatı: qanuni başlanğıc `legal_from`
     * (VM m.102.8) götürülür, ödənilmiş aylarda verilməmiş güzəşt də görünür.
     *
     * @return list<string>
     */
    public function codesFor(string $tabelNo, string $periodStart, string $periodEnd, bool $legal = false): array;
}
