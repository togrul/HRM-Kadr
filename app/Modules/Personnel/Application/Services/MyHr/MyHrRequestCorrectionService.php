<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use InvalidArgumentException;

class MyHrRequestCorrectionService
{
    public function create(Personnel $personnel, User $user, string $requestType, int $recordId, string $reason, array $patch): EmployeeRequestChangeRequest
    {
        $requestable = $this->resolveRequestable($personnel, $requestType, $recordId);

        return EmployeeRequestChangeRequest::query()->create([
            'requestable_type' => $requestable::class,
            'requestable_id' => $requestable->getKey(),
            'personnel_id' => $personnel->id,
            'requested_by_user_id' => $user->id,
            'reason' => trim($reason),
            'proposed_patch' => $this->normalizePatch($requestable, $patch),
            'status' => 'pending',
        ]);
    }

    private function resolveRequestable(Personnel $personnel, string $requestType, int $recordId): Leave|PersonnelVacation|PersonnelBusinessTrip
    {
        return match ($requestType) {
            'leave' => Leave::query()
                ->whereKey($recordId)
                ->where('tabel_no', $personnel->tabel_no)
                ->firstOrFail(),
            'vacation' => PersonnelVacation::query()
                ->whereKey($recordId)
                ->where('tabel_no', $personnel->tabel_no)
                ->firstOrFail(),
            'business_trip' => PersonnelBusinessTrip::query()
                ->whereKey($recordId)
                ->where('tabel_no', $personnel->tabel_no)
                ->firstOrFail(),
            default => throw new InvalidArgumentException("Unsupported request type [{$requestType}]."),
        };
    }

    private function normalizePatch(Leave|PersonnelVacation|PersonnelBusinessTrip $requestable, array $patch): array
    {
        if ($requestable instanceof Leave) {
            return array_filter([
                'starts_at' => data_get($patch, 'starts_at') ?: null,
                'ends_at' => data_get($patch, 'ends_at') ?: null,
                'reason' => filled(data_get($patch, 'reason')) ? trim((string) data_get($patch, 'reason')) : null,
            ], fn ($value) => filled($value));
        }

        if ($requestable instanceof PersonnelVacation) {
            return array_filter([
                'vacation_places' => filled(data_get($patch, 'vacation_places')) ? trim((string) data_get($patch, 'vacation_places')) : null,
                'start_date' => data_get($patch, 'start_date') ?: null,
                'end_date' => data_get($patch, 'end_date') ?: null,
            ], fn ($value) => filled($value));
        }

        return array_filter([
            'location' => filled(data_get($patch, 'location')) ? trim((string) data_get($patch, 'location')) : null,
            'description' => filled(data_get($patch, 'description')) ? trim((string) data_get($patch, 'description')) : null,
            'start_date' => data_get($patch, 'start_date') ?: null,
            'end_date' => data_get($patch, 'end_date') ?: null,
        ], fn ($value) => filled($value));
    }
}
