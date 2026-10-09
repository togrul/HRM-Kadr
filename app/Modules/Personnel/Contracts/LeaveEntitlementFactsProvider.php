<?php

namespace App\Modules\Personnel\Contracts;

/**
 * Modullararası rəsmi səth: işçinin əmək məzuniyyəti hüququnu müəyyən edən faktlar (cins, yaş,
 * əlillik, uşaqlar, əmək stajı dövrləri). Vacation modulu normaları bu interfeys vasitəsilə
 * oxuyur — Personnel cədvəllərinə birbaşa yox.
 *
 * @see \App\Modules\Personnel\Application\Services\LeaveEntitlementFactsService
 */
interface LeaveEntitlementFactsProvider
{
    /**
     * Mövcud olan hər tabel nömrəsi üçün bir faktlar dəsti (paket üçün sabit sayda sorğu).
     *
     * @param  array<int, string>  $tabelNos
     * @return array<string, LeaveEntitlementFacts> tabel_no => faktlar
     */
    public function facts(array $tabelNos): array;
}
