<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Enums\OrderStatusEnum;
use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MyHrRequestsReadService
{
    public function build(Personnel $personnel, array $filters = []): array
    {
        $typeFilter = (string) ($filters['type'] ?? 'all');
        $statusFilter = (string) ($filters['status'] ?? 'all');
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo = $this->parseDate($filters['date_to'] ?? null);

        $rows = collect();

        if (in_array($typeFilter, ['all', 'leave'], true)) {
            $rows = $rows->merge($this->leaveRows($personnel, $statusFilter, $dateFrom, $dateTo));
        }

        if (in_array($typeFilter, ['all', 'vacation'], true)) {
            $rows = $rows->merge($this->vacationRows($personnel, $statusFilter, $dateFrom, $dateTo));
        }

        if (in_array($typeFilter, ['all', 'business_trip'], true)) {
            $rows = $rows->merge($this->businessTripRows($personnel, $statusFilter, $dateFrom, $dateTo));
        }

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row): bool => str_contains($row['search_blob'], $search));
        }

        $rows = $rows
            ->sortByDesc('sort_at')
            ->values();

        $summarySource = $rows->values();

        return [
            'summary' => [
                'total' => $summarySource->count(),
                'pending' => $summarySource->where('status_key', 'pending')->count(),
                'active' => $summarySource->whereIn('status_key', ['approved', 'active', 'upcoming'])->count(),
                'completed' => $summarySource->where('status_key', 'completed')->count(),
            ],
            'rows' => $rows
                ->map(function (array $row): array {
                    unset($row['sort_at'], $row['search_blob']);

                    return $row;
                })
                ->all(),
        ];
    }

    private function leaveRows(Personnel $personnel, string $statusFilter, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): Collection
    {
        return Leave::query()
            ->withTrashed()
            ->with([
                'leaveType:id,name',
                'status:id,name',
                'assigned:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('tabel_no', $personnel->tabel_no)
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (Leave $leave): ?array {
                $statusKey = $this->normalizeLeaveStatus($leave);
                $approver = $leave->assigned?->fullname
                    ?: ($leave->fallbackApprover?->fullname ?: 'HR');
                $period = $leave->period_label ?: __('personnel::my_hr.requests.messages.period_not_available');

                return [
                    'id' => 'leave-'.$leave->getKey(),
                    'record_id' => $leave->getKey(),
                    'request_type' => 'leave',
                    'type_label' => __('personnel::my_hr.requests.types.leave'),
                    'title' => $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
                    'status_key' => $statusKey,
                    'status_label' => __('personnel::my_hr.requests.status.'.$statusKey),
                    'status_mode' => $this->statusMode($statusKey),
                    'date_badge' => optional($leave->starts_at)->format('d.m.Y') ?: '—',
                    'period' => $period,
                    'summary' => $leave->reason ?: __('personnel::my_hr.requests.messages.no_reason'),
                    'details' => [
                        ['label' => __('personnel::my_hr.requests.detail.duration'), 'value' => $leave->durationDetailLabel()],
                        ['label' => __('personnel::my_hr.requests.detail.window'), 'value' => $leave->durationWindowLabel() ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $approver],
                        ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($leave->created_at)->format('d.m.Y H:i') ?: '—'],
                    ],
                    'sort_at' => optional($leave->starts_at)->timestamp ?? 0,
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        $leave->leaveType?->name,
                        $leave->reason,
                        $leave->status?->name,
                        $period,
                    ]))),
                    'effective_from' => optional($leave->starts_at)->format('Y-m-d'),
                    'effective_to' => optional($leave->ends_at)->format('Y-m-d'),
                    'can_request_correction' => $this->canRequestCorrection(Leave::class, $leave->getKey(), $statusKey),
                ];
            })
            ->filter(fn (?array $row): bool => $row !== null)
            ->filter(fn (array $row): bool => $this->matchesStatusAndDate($row, $statusFilter, $dateFrom, $dateTo))
            ->values();
    }

    private function vacationRows(Personnel $personnel, string $statusFilter, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): Collection
    {
        return PersonnelVacation::query()
            ->withTrashed()
            ->with([
                'approver:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('tabel_no', $personnel->tabel_no)
            ->orderByDesc('start_date')
            ->get()
            ->map(function (PersonnelVacation $vacation): array {
                $statusKey = $this->normalizeVacationStatus($vacation);

                $approver = $vacation->approver?->fullname
                    ?: ($vacation->fallbackApprover?->fullname ?: 'HR');

                return [
                    'id' => 'vacation-'.$vacation->getKey(),
                    'record_id' => $vacation->getKey(),
                    'request_type' => 'vacation',
                    'type_label' => __('personnel::my_hr.requests.types.vacation'),
                    'title' => __('personnel::my_hr.requests.titles.vacation'),
                    'status_key' => $statusKey,
                    'status_label' => __('personnel::my_hr.requests.status.'.$statusKey),
                    'status_mode' => $this->statusMode($statusKey),
                    'date_badge' => optional($vacation->start_date)->format('d.m.Y') ?: '—',
                    'period' => trim(implode(' – ', array_filter([
                        optional($vacation->start_date)->format('d.m.Y'),
                        optional($vacation->end_date)->format('d.m.Y'),
                    ]))) ?: __('personnel::my_hr.requests.messages.period_not_available'),
                    'summary' => $vacation->vacation_places ?: __('personnel::my_hr.requests.messages.no_summary'),
                    'details' => [
                        ['label' => __('personnel::my_hr.requests.detail.duration'), 'value' => __('personnel::my_hr.requests.values.days', ['count' => (int) $vacation->duration])],
                        ['label' => __('personnel::my_hr.requests.detail.return_date'), 'value' => optional($vacation->return_work_date)->format('d.m.Y') ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $approver],
                        ['label' => __('personnel::my_hr.requests.detail.order_no'), 'value' => $vacation->order_no ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($vacation->created_at)->format('d.m.Y H:i') ?: '—'],
                    ],
                    'sort_at' => optional($vacation->start_date)->timestamp ?? 0,
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        __('personnel::my_hr.requests.titles.vacation'),
                        $vacation->vacation_places,
                        $vacation->order_no,
                    ]))),
                    'effective_from' => optional($vacation->start_date)->format('Y-m-d'),
                    'effective_to' => optional($vacation->end_date)->format('Y-m-d'),
                    'can_request_correction' => $this->canRequestCorrection(PersonnelVacation::class, $vacation->getKey(), $statusKey),
                ];
            })
            ->filter(fn (array $row): bool => $this->matchesStatusAndDate($row, $statusFilter, $dateFrom, $dateTo))
            ->values();
    }

    private function businessTripRows(Personnel $personnel, string $statusFilter, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): Collection
    {
        return PersonnelBusinessTrip::query()
            ->withTrashed()
            ->with([
                'approver:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('tabel_no', $personnel->tabel_no)
            ->orderByDesc('start_date')
            ->get()
            ->map(function (PersonnelBusinessTrip $trip): array {
                $statusKey = $this->normalizeBusinessTripStatus($trip);

                $approver = $trip->approver?->fullname
                    ?: ($trip->fallbackApprover?->fullname ?: 'HR');

                return [
                    'id' => 'business-trip-'.$trip->getKey(),
                    'record_id' => $trip->getKey(),
                    'request_type' => 'business_trip',
                    'type_label' => __('personnel::my_hr.requests.types.business_trip'),
                    'title' => __('personnel::my_hr.requests.titles.business_trip'),
                    'status_key' => $statusKey,
                    'status_label' => __('personnel::my_hr.requests.status.'.$statusKey),
                    'status_mode' => $this->statusMode($statusKey),
                    'date_badge' => optional($trip->start_date)->format('d.m.Y') ?: '—',
                    'period' => trim(implode(' – ', array_filter([
                        optional($trip->start_date)->format('d.m.Y'),
                        optional($trip->end_date)->format('d.m.Y'),
                    ]))) ?: __('personnel::my_hr.requests.messages.period_not_available'),
                    'summary' => $trip->location ?: __('personnel::my_hr.requests.messages.no_summary'),
                    'details' => [
                        ['label' => __('personnel::my_hr.requests.detail.location'), 'value' => $trip->location ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.description'), 'value' => $trip->description ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $approver],
                        ['label' => __('personnel::my_hr.requests.detail.order_no'), 'value' => $trip->order_no ?: '—'],
                        ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($trip->created_at)->format('d.m.Y H:i') ?: '—'],
                    ],
                    'sort_at' => optional($trip->start_date)->timestamp ?? 0,
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        __('personnel::my_hr.requests.titles.business_trip'),
                        $trip->location,
                        $trip->description,
                        $trip->order_no,
                    ]))),
                    'effective_from' => optional($trip->start_date)->format('Y-m-d'),
                    'effective_to' => optional($trip->end_date)->format('Y-m-d'),
                    'can_request_correction' => $this->canRequestCorrection(PersonnelBusinessTrip::class, $trip->getKey(), $statusKey),
                ];
            })
            ->filter(fn (array $row): bool => $this->matchesStatusAndDate($row, $statusFilter, $dateFrom, $dateTo))
            ->values();
    }

    private function normalizeLeaveStatus(Leave $leave): string
    {
        if ($leave->deleted_at) {
            return 'deleted';
        }

        if ((int) $leave->status_id === OrderStatusEnum::CANCELLED->value) {
            return 'cancelled';
        }

        if ((int) $leave->status_id === OrderStatusEnum::PENDING->value) {
            return 'pending';
        }

        $today = now()->toDateString();
        $startsAt = optional($leave->starts_at)->toDateString();
        $endsAt = optional($leave->ends_at)->toDateString();

        if ($startsAt && $startsAt > $today) {
            return 'approved';
        }

        if ($endsAt && $endsAt < $today) {
            return 'completed';
        }

        return 'active';
    }

    private function normalizeDateBoundStatus(mixed $deletedAt, mixed $startsAt, mixed $endsAt): string
    {
        if ($deletedAt) {
            return 'deleted';
        }

        $today = now()->toDateString();
        $start = $startsAt ? $startsAt->toDateString() : null;
        $end = $endsAt ? $endsAt->toDateString() : null;

        if ($start && $start > $today) {
            return 'upcoming';
        }

        if ($end && $end < $today) {
            return 'completed';
        }

        return 'active';
    }

    private function normalizeVacationStatus(PersonnelVacation $vacation): string
    {
        $approvalStatus = trim((string) ($vacation->approval_status ?? ''));

        if ($approvalStatus === 'pending') {
            return 'pending';
        }

        if (in_array($approvalStatus, ['rejected', 'cancelled'], true)) {
            return 'cancelled';
        }

        return $this->normalizeDateBoundStatus(
            deletedAt: $vacation->deleted_at,
            startsAt: $vacation->start_date,
            endsAt: $vacation->return_work_date
        );
    }

    private function normalizeBusinessTripStatus(PersonnelBusinessTrip $trip): string
    {
        $approvalStatus = trim((string) ($trip->approval_status ?? ''));

        if ($approvalStatus === 'pending') {
            return 'pending';
        }

        if (in_array($approvalStatus, ['rejected', 'cancelled'], true)) {
            return 'cancelled';
        }

        return $this->normalizeDateBoundStatus(
            deletedAt: $trip->deleted_at,
            startsAt: $trip->start_date,
            endsAt: $trip->end_date
        );
    }

    private function statusMode(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'approved', 'active' => 'success',
            'upcoming' => 'info',
            'completed' => 'neutral',
            'deleted', 'cancelled' => 'danger',
            default => 'neutral',
        };
    }

    private function matchesStatusAndDate(array $row, string $statusFilter, ?CarbonImmutable $dateFrom, ?CarbonImmutable $dateTo): bool
    {
        if ($statusFilter !== 'all' && $row['status_key'] !== $statusFilter) {
            return false;
        }

        if (! $dateFrom && ! $dateTo) {
            return true;
        }

        $start = $row['effective_from'] ? CarbonImmutable::parse($row['effective_from']) : null;
        $end = $row['effective_to'] ? CarbonImmutable::parse($row['effective_to']) : null;

        if (! $start || ! $end) {
            return false;
        }

        if ($dateFrom && $end->lessThan($dateFrom)) {
            return false;
        }

        if ($dateTo && $start->greaterThan($dateTo)) {
            return false;
        }

        return true;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    private function canRequestCorrection(string $requestableType, int $recordId, string $statusKey): bool
    {
        if (! in_array($statusKey, ['pending', 'approved', 'upcoming', 'active'], true)) {
            return false;
        }

        return ! EmployeeRequestChangeRequest::query()
            ->where('requestable_type', $requestableType)
            ->where('requestable_id', $recordId)
            ->where('status', 'pending')
            ->exists();
    }
}
