<?php

namespace App\Modules\Candidates\Contracts;

/**
 * Təsdiqlənmiş işə qəbul əmrinin geri alınması zamanı namizəd tərəfinin bərpası.
 * Orders modulu Candidates-in daxili sinifləri əvəzinə yalnız bu kontrakta bağlıdır.
 */
interface CandidateHireReversal
{
    /** Bu əmrlə işə qəbul edilmiş əməkdaşın id-si; əlaqə tapılmazsa null. */
    public function hiredPersonnelId(int $candidateId, int $orderId): ?int;

    /**
     * Əməkdaşı yumşaq silir (səbəb audit jurnalına yazılır), namizədi "Əmrə hazır"
     * statusuna qaytarır və işə qəbul əlaqələrini (namizəd, müraciət, adaptasiya hadisəsi) təmizləyir.
     */
    public function revertOrderHire(int $candidateId, int $personnelId, int $orderId, string $reason): void;
}
