<?php

namespace App\Services\Orders;

use App\Models\Candidate;
use App\Models\Personnel;

class PersonnelResolver
{
    public function tabelNumbersForDefault(array $personnelIds): array
    {
        if (empty($personnelIds)) {
            return [];
        }

        return Personnel::query()
            ->whereIn('id', $personnelIds)
            ->pluck('tabel_no')
            ->toArray();
    }

    public function tabelNumbersForCandidateOrder(array $components): array
    {
        $candidateIds = collect($components)
            ->pluck('personnel_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($candidateIds)) {
            return [];
        }

        return Candidate::query()
            ->whereIn('id', $candidateIds)
            ->pluck('id')
            ->map(fn ($id) => "NMZD{$id}")
            ->toArray();
    }
}
