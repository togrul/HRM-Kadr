<?php

namespace App\Services;

class EducationDurationService
{
    public function __construct(
        private readonly CalculateSeniorityService $seniorityService,
    ) {
    }

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $educationCache = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $extraEducationCache = [];

    /**
     * @param  array<string, mixed>  $education
     */
    public function education(array $education): array
    {
        if (! $this->shouldCalculateEducation($education)) {
            return [];
        }

        $cacheKey = $this->hashPayload($education);

        return $this->educationCache[$cacheKey]
            ??= $this->seniorityService->calculateEducation($education);
    }

    /**
     * @param  array<int, array<string, mixed>>  $extraEducationList
     */
    public function extraEducations(array $extraEducationList): array
    {
        if (empty($extraEducationList)) {
            return [];
        }

        $normalized = array_values($extraEducationList);
        $cacheKey = $this->hashPayload($normalized);

        return $this->extraEducationCache[$cacheKey]
            ??= $this->seniorityService->calculateMultiEducation($normalized);
    }

    private function shouldCalculateEducation(array $education): bool
    {
        return ! empty($education['admission_year']);
    }

    private function hashPayload(array $payload): string
    {
        return md5(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}

