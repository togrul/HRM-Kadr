<?php

namespace App\Services;

use App\Models\Candidate;
use App\Modules\Candidates\Application\Services\CandidateHireConversionService;

class ImportCandidateToPersonnel
{
    public function __construct(
        protected PersonnelTabelNoGeneratorService $tabelNoGenerator,
        protected ?CandidateHireConversionService $candidateHireConversionService = null,
    ) {}

    public function handle(array $components, $status): array
    {
        $tabel_no_list = [];
        foreach ($components as $component) {
            $candidateId = $component['personnel_id'] ?? null;
            if (is_array($candidateId)) {
                $candidateId = $candidateId['id'] ?? null;
            }
            if (! $candidateId) {
                continue;
            }

            $candidate = Candidate::find($candidateId);
            if (! $candidate) {
                continue;
            }

            $personnel = $this->conversionService()->convertCandidateForOrder($candidate, $component, $status);
            $tabel_no_list[] = $personnel->tabel_no;
        }

        return $tabel_no_list;
    }

    protected function conversionService(): CandidateHireConversionService
    {
        return $this->candidateHireConversionService ??= app(CandidateHireConversionService::class);
    }
}
