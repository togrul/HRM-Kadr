<?php

namespace App\Modules\Orders\Domain\Contracts;

use Illuminate\Support\Collection;

interface PersonnelLookupReadRepository
{
    /**
     * @param  array<int,int>  $excludeIds
     * @param  array<int,int>  $accessibleStructureIds
     */
    public function activePersonnel(array $excludeIds, array $accessibleStructureIds, ?string $search, int $defaultLimit): Collection;

    /**
     * @param  array<int,int>  $excludeIds
     */
    public function candidatePersonnelReady(array $excludeIds, ?string $search): Collection;

    /**
     * @return array{name:string,surname:string}|null
     */
    public function findCandidateNameParts(int $id): ?array;

    /**
     * @return array{name:string,surname:string}|null
     */
    public function findPersonnelNameParts(int $id): ?array;
}
