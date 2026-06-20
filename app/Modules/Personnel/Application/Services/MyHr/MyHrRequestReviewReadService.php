<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MyHrRequestReviewReadService
{
    public function __construct(
        private readonly UserPersonnelLinkResolver $personnelResolver,
    ) {}

    public function build(array $filters = [], ?User $reviewer = null): array
    {
        $type = (string) ($filters['type'] ?? 'all');
        $scope = (string) ($filters['scope'] ?? 'mine');
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $reviewAll = $scope === 'all' && (bool) $reviewer?->can('review-all-self-service-requests');
        $reviewerPersonnelId = $reviewer ? $this->personnelResolver->resolve($reviewer) : null;

        $rows = collect();

        if (in_array($type, ['all', 'leave'], true)) {
            $rows = $rows->merge($this->leaveRows($reviewAll, $reviewerPersonnelId));
        }

        if (in_array($type, ['all', 'vacation'], true)) {
            $rows = $rows->merge($this->vacationRows($reviewAll, $reviewerPersonnelId));
        }

        if (in_array($type, ['all', 'business_trip'], true)) {
            $rows = $rows->merge($this->businessTripRows($reviewAll, $reviewerPersonnelId));
        }

        if (in_array($type, ['all', 'correction'], true)) {
            $rows = $rows->merge($this->correctionRows($reviewAll, $reviewerPersonnelId));
        }

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row): bool => str_contains($row['search_blob'], $search));
        }

        $rows = $rows->sortByDesc('sort_at')->values();

        return [
            'summary' => [
                'total' => $rows->count(),
                'leave' => $rows->where('request_type', 'leave')->count(),
                'vacation' => $rows->where('request_type', 'vacation')->count(),
                'business_trip' => $rows->where('request_type', 'business_trip')->count(),
                'correction' => $rows->where('request_type', 'correction')->count(),
            ],
            'rows' => $rows->map(function (array $row): array {
                unset($row['sort_at'], $row['search_blob']);

                return $row;
            })->all(),
        ];
    }

    private function leaveRows(bool $reviewAll, ?int $reviewerPersonnelId): Collection
    {
        $query = Leave::query()
            ->with([
                'personnel:id,tabel_no,surname,name,patronymic,position_id,structure_id',
                'personnel.position:id,name',
                'personnel.structure:id,name,parent_id',
                'leaveType:id,name',
                'submittedBy:id,name,email',
                'assigned:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('submission_source', 'employee_self_service')
            ->pending()
            ->latest('created_at');

        if (! $reviewAll) {
            if (! $reviewerPersonnelId) {
                return collect();
            }

            $query->where(function ($nested) use ($reviewerPersonnelId): void {
                $nested->where('assigned_to', $reviewerPersonnelId)
                    ->orWhere('fallback_approver_personnel_id', $reviewerPersonnelId);
            });
        }

        return $query
            ->get()
            ->map(fn (Leave $leave): array => [
                'id' => 'leave-'.$leave->id,
                'record_id' => $leave->id,
                'request_type' => 'leave',
                'request_type_label' => __('personnel::my_hr.requests.types.leave'),
                'title' => $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
                'personnel' => $leave->personnel?->fullname ?: $leave->tabel_no,
                'position' => $leave->personnel?->position?->name ?: '—',
                'structure' => $leave->personnel?->structure?->name ?: '—',
                'period' => $leave->period_label ?: '—',
                'summary' => $leave->reason ?: __('personnel::my_hr.requests.messages.no_reason'),
                'status_label' => __('personnel::my_hr.requests.status.pending'),
                'details' => [
                    ['label' => __('personnel::my_hr.requests.detail.duration'), 'value' => $leave->durationDetailLabel()],
                    ['label' => __('personnel::my_hr.requests.detail.window'), 'value' => $leave->durationWindowLabel() ?: '—'],
                    ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $leave->assigned?->fullname ?: ($leave->fallbackApprover?->fullname ?: 'HR')],
                    ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($leave->created_at)->format('d.m.Y H:i') ?: '—'],
                    ['label' => __('personnel::my_hr.review.labels.submitted_by'), 'value' => $leave->submittedBy?->email ?: ($leave->submittedBy?->name ?: '—')],
                ],
                'audit' => $this->auditRows(
                    (string) ($leave->approval_route_source ?: 'hierarchy_policy'),
                    $leave->assigned?->fullname,
                    $leave->fallbackApprover?->fullname,
                    (bool) data_get($leave->getAttributes(), 'hr_always_included', false),
                ),
                'sort_at' => optional($leave->created_at)->timestamp ?? 0,
                'search_blob' => Str::lower(implode(' ', array_filter([
                    $leave->leaveType?->name,
                    $leave->reason,
                    $leave->personnel?->fullname,
                    $leave->personnel?->position?->name,
                    $leave->personnel?->structure?->name,
                ]))),
            ]);
    }

    private function vacationRows(bool $reviewAll, ?int $reviewerPersonnelId): Collection
    {
        $query = PersonnelVacation::query()
            ->with([
                'personnel:id,tabel_no,surname,name,patronymic,position_id,structure_id',
                'personnel.position:id,name',
                'personnel.structure:id,name,parent_id',
                'submittedBy:id,name,email',
                'approver:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('submission_source', 'employee_self_service')
            ->where('approval_status', 'pending')
            ->latest('created_at');

        if (! $reviewAll) {
            if (! $reviewerPersonnelId) {
                return collect();
            }

            $query->where(function ($nested) use ($reviewerPersonnelId): void {
                $nested->where('approver_personnel_id', $reviewerPersonnelId)
                    ->orWhere('fallback_approver_personnel_id', $reviewerPersonnelId);
            });
        }

        return $query
            ->get()
            ->map(fn (PersonnelVacation $vacation): array => [
                'id' => 'vacation-'.$vacation->id,
                'record_id' => $vacation->id,
                'request_type' => 'vacation',
                'request_type_label' => __('personnel::my_hr.requests.types.vacation'),
                'title' => __('personnel::my_hr.requests.titles.vacation'),
                'personnel' => $vacation->personnel?->fullname ?: $vacation->tabel_no,
                'position' => $vacation->personnel?->position?->name ?: '—',
                'structure' => $vacation->personnel?->structure?->name ?: '—',
                'period' => trim(implode(' – ', array_filter([
                    optional($vacation->start_date)->format('d.m.Y'),
                    optional($vacation->end_date)->format('d.m.Y'),
                ]))) ?: '—',
                'summary' => $vacation->vacation_places ?: __('personnel::my_hr.requests.messages.no_summary'),
                'status_label' => __('personnel::my_hr.requests.status.pending'),
                'details' => [
                    ['label' => __('personnel::my_hr.requests.detail.duration'), 'value' => __('personnel::my_hr.requests.values.days', ['count' => (int) $vacation->duration])],
                    ['label' => __('personnel::my_hr.requests.detail.return_date'), 'value' => optional($vacation->return_work_date)->format('d.m.Y') ?: '—'],
                    ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $vacation->approver?->fullname ?: ($vacation->fallbackApprover?->fullname ?: 'HR')],
                    ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($vacation->created_at)->format('d.m.Y H:i') ?: '—'],
                    ['label' => __('personnel::my_hr.review.labels.submitted_by'), 'value' => $vacation->submittedBy?->email ?: ($vacation->submittedBy?->name ?: '—')],
                ],
                'audit' => $this->auditRows(
                    (string) ($vacation->approval_route_source ?: 'hierarchy_policy'),
                    $vacation->approver?->fullname,
                    $vacation->fallbackApprover?->fullname,
                    (bool) $vacation->hr_always_included,
                ),
                'sort_at' => optional($vacation->created_at)->timestamp ?? 0,
                'search_blob' => Str::lower(implode(' ', array_filter([
                    $vacation->vacation_places,
                    $vacation->personnel?->fullname,
                    $vacation->personnel?->position?->name,
                    $vacation->personnel?->structure?->name,
                ]))),
            ]);
    }

    private function businessTripRows(bool $reviewAll, ?int $reviewerPersonnelId): Collection
    {
        $query = PersonnelBusinessTrip::query()
            ->with([
                'personnel:id,tabel_no,surname,name,patronymic,position_id,structure_id',
                'personnel.position:id,name',
                'personnel.structure:id,name,parent_id',
                'submittedBy:id,name,email',
                'approver:id,surname,name,patronymic',
                'fallbackApprover:id,surname,name,patronymic',
            ])
            ->where('submission_source', 'employee_self_service')
            ->where('approval_status', 'pending')
            ->latest('created_at');

        if (! $reviewAll) {
            if (! $reviewerPersonnelId) {
                return collect();
            }

            $query->where(function ($nested) use ($reviewerPersonnelId): void {
                $nested->where('approver_personnel_id', $reviewerPersonnelId)
                    ->orWhere('fallback_approver_personnel_id', $reviewerPersonnelId);
            });
        }

        return $query
            ->get()
            ->map(fn (PersonnelBusinessTrip $trip): array => [
                'id' => 'business-trip-'.$trip->id,
                'record_id' => $trip->id,
                'request_type' => 'business_trip',
                'request_type_label' => __('personnel::my_hr.requests.types.business_trip'),
                'title' => __('personnel::my_hr.requests.titles.business_trip'),
                'personnel' => $trip->personnel?->fullname ?: $trip->tabel_no,
                'position' => $trip->personnel?->position?->name ?: '—',
                'structure' => $trip->personnel?->structure?->name ?: '—',
                'period' => trim(implode(' – ', array_filter([
                    optional($trip->start_date)->format('d.m.Y'),
                    optional($trip->end_date)->format('d.m.Y'),
                ]))) ?: '—',
                'summary' => $trip->location ?: __('personnel::my_hr.requests.messages.no_summary'),
                'status_label' => __('personnel::my_hr.requests.status.pending'),
                'details' => [
                    ['label' => __('personnel::my_hr.requests.detail.location'), 'value' => $trip->location ?: '—'],
                    ['label' => __('personnel::my_hr.requests.detail.description'), 'value' => $trip->description ?: '—'],
                    ['label' => __('personnel::my_hr.requests.detail.current_approver'), 'value' => $trip->approver?->fullname ?: ($trip->fallbackApprover?->fullname ?: 'HR')],
                    ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($trip->created_at)->format('d.m.Y H:i') ?: '—'],
                    ['label' => __('personnel::my_hr.review.labels.submitted_by'), 'value' => $trip->submittedBy?->email ?: ($trip->submittedBy?->name ?: '—')],
                ],
                'audit' => $this->auditRows(
                    (string) ($trip->approval_route_source ?: 'hierarchy_policy'),
                    $trip->approver?->fullname,
                    $trip->fallbackApprover?->fullname,
                    (bool) $trip->hr_always_included,
                ),
                'sort_at' => optional($trip->created_at)->timestamp ?? 0,
                'search_blob' => Str::lower(implode(' ', array_filter([
                    $trip->location,
                    $trip->description,
                    $trip->personnel?->fullname,
                    $trip->personnel?->position?->name,
                    $trip->personnel?->structure?->name,
                ]))),
            ]);
    }

    private function correctionRows(bool $reviewAll, ?int $reviewerPersonnelId): Collection
    {
        $rows = EmployeeRequestChangeRequest::query()
            ->with([
                'personnel:id,surname,name,patronymic,position_id,structure_id',
                'personnel.position:id,name',
                'personnel.structure:id,name,parent_id',
                'requestedBy:id,name,email',
                'requestable',
            ])
            ->where('status', 'pending')
            ->latest('created_at')
            ->get();

        if (! $reviewAll) {
            if (! $reviewerPersonnelId) {
                return collect();
            }

            $rows = $rows->filter(function (EmployeeRequestChangeRequest $request) use ($reviewerPersonnelId): bool {
                $requestable = $request->requestable;

                return match (true) {
                    $requestable instanceof Leave => in_array($reviewerPersonnelId, array_filter([
                        (int) $requestable->assigned_to,
                        (int) $requestable->fallback_approver_personnel_id,
                    ]), true),
                    $requestable instanceof PersonnelVacation => in_array($reviewerPersonnelId, array_filter([
                        (int) $requestable->approver_personnel_id,
                        (int) $requestable->fallback_approver_personnel_id,
                    ]), true),
                    $requestable instanceof PersonnelBusinessTrip => in_array($reviewerPersonnelId, array_filter([
                        (int) $requestable->approver_personnel_id,
                        (int) $requestable->fallback_approver_personnel_id,
                    ]), true),
                    default => false,
                };
            })->values();
        }

        return $rows->map(fn (EmployeeRequestChangeRequest $request): array => [
            'id' => 'correction-'.$request->id,
            'record_id' => $request->id,
            'request_type' => 'correction',
            'request_type_label' => __('personnel::my_hr.review.types.correction'),
            'title' => __('personnel::my_hr.review.titles.correction_for', ['type' => $this->requestableTypeLabel($request->requestable_type)]),
            'personnel' => $request->personnel?->fullname ?: '—',
            'position' => $request->personnel?->position?->name ?: '—',
            'structure' => $request->personnel?->structure?->name ?: '—',
            'period' => optional($request->created_at)->format('d.m.Y H:i') ?: '—',
            'summary' => $request->reason,
            'status_label' => __('personnel::my_hr.requests.status.pending'),
            'details' => [
                ['label' => __('personnel::my_hr.review.labels.requestable'), 'value' => $this->requestableTypeLabel($request->requestable_type)],
                ['label' => __('personnel::my_hr.review.labels.submitted_by'), 'value' => $request->requestedBy?->email ?: ($request->requestedBy?->name ?: '—')],
                ['label' => __('personnel::my_hr.review.labels.proposed_patch'), 'value' => $this->formatPatch($request->proposed_patch ?? [])],
                ['label' => __('personnel::my_hr.requests.detail.submitted_at'), 'value' => optional($request->created_at)->format('d.m.Y H:i') ?: '—'],
            ],
            'audit' => $this->auditRows(
                'hierarchy_policy',
                $this->changePrimaryApprover($request),
                $this->changeUpperApprover($request),
                $this->changeHrIncluded($request),
            ),
            'sort_at' => optional($request->created_at)->timestamp ?? 0,
            'search_blob' => Str::lower(implode(' ', array_filter([
                $request->reason,
                $request->personnel?->fullname,
                $request->requestedBy?->email,
                $this->requestableTypeLabel($request->requestable_type),
            ]))),
        ]);
    }

    private function requestableTypeLabel(?string $type): string
    {
        return match ($type) {
            Leave::class => __('personnel::my_hr.requests.types.leave'),
            PersonnelVacation::class => __('personnel::my_hr.requests.types.vacation'),
            PersonnelBusinessTrip::class => __('personnel::my_hr.requests.types.business_trip'),
            default => __('personnel::my_hr.review.types.unknown'),
        };
    }

    private function formatPatch(array $patch): string
    {
        if ($patch === []) {
            return '—';
        }

        return collect($patch)
            ->map(fn ($value, $key) => __('personnel::my_hr.review.patch_fields.'.$key, [], app()->getLocale()) === 'personnel::my_hr.review.patch_fields.'.$key
                ? $key.': '.$value
                : __('personnel::my_hr.review.patch_fields.'.$key).': '.$value)
            ->implode(' | ');
    }

    private function auditRows(string $routeSource, ?string $primaryApprover, ?string $upperApprover, bool $hrIncluded): array
    {
        return [
            ['label' => __('personnel::my_hr.review.audit.route_source'), 'value' => __('personnel::my_hr.hierarchy.route_sources.'.$routeSource)],
            ['label' => __('personnel::my_hr.review.audit.primary_approver'), 'value' => $primaryApprover ?: '—'],
            ['label' => __('personnel::my_hr.review.audit.upper_approver'), 'value' => $upperApprover ?: '—'],
            ['label' => __('personnel::my_hr.review.audit.hr_line'), 'value' => $hrIncluded ? __('personnel::my_hr.review.audit.hr_active') : __('personnel::my_hr.review.audit.hr_inactive')],
        ];
    }

    private function changePrimaryApprover(EmployeeRequestChangeRequest $request): ?string
    {
        return match (true) {
            $request->requestable instanceof Leave => $request->requestable->assigned?->fullname,
            $request->requestable instanceof PersonnelVacation => $request->requestable->approver?->fullname,
            $request->requestable instanceof PersonnelBusinessTrip => $request->requestable->approver?->fullname,
            default => null,
        };
    }

    private function changeUpperApprover(EmployeeRequestChangeRequest $request): ?string
    {
        return match (true) {
            $request->requestable instanceof Leave => $request->requestable->fallbackApprover?->fullname,
            $request->requestable instanceof PersonnelVacation => $request->requestable->fallbackApprover?->fullname,
            $request->requestable instanceof PersonnelBusinessTrip => $request->requestable->fallbackApprover?->fullname,
            default => null,
        };
    }

    private function changeHrIncluded(EmployeeRequestChangeRequest $request): bool
    {
        return match (true) {
            $request->requestable instanceof Leave => (bool) $request->requestable->hr_always_included,
            $request->requestable instanceof PersonnelVacation => (bool) $request->requestable->hr_always_included,
            $request->requestable instanceof PersonnelBusinessTrip => (bool) $request->requestable->hr_always_included,
            default => false,
        };
    }
}
