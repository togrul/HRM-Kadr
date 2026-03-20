<?php

namespace App\Modules\Personnel\Application\Services;

class ProfessionalPortfolioRegistryFingerprintService
{
    public function forEvent(array $data): string
    {
        return $this->hash([
            'event',
            $data['event_type'] ?? null,
            $data['title'] ?? null,
            $data['organizer_name'] ?? null,
            $data['start_date'] ?? null,
            $data['country_id'] ?? null,
        ]);
    }

    public function forMediaPublisher(array $data): string
    {
        return $this->hash([
            'media-publisher',
            $data['publisher_type'] ?? null,
            $data['publisher_name'] ?? null,
        ]);
    }

    public function forProject(array $data): string
    {
        return $this->hash([
            'project',
            $data['project_name'] ?? null,
            $data['project_code'] ?? null,
            $data['sponsor_unit_id'] ?? null,
        ]);
    }

    private function hash(array $segments): string
    {
        $normalized = array_map(function ($value): string {
            $value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
            $value = mb_strtolower(trim($value));
            $value = preg_replace('/\s+/u', ' ', $value ?? '') ?? '';

            return $value;
        }, $segments);

        return sha1(implode('|', $normalized));
    }
}
