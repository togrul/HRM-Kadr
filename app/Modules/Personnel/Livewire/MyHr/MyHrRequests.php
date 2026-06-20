<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestCorrectionService;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestsReadService;
use App\Modules\Personnel\Application\Services\MyHr\ApprovalRouteResolverService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MyHrRequests extends Component
{
    use WithFileUploads;

    public int $personnelId;

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeCreateForm = '';

    public array $leaveForm = [];

    public array $vacationForm = [];

    public array $businessTripForm = [];

    public TemporaryUploadedFile|string|null $leaveDocument = null;

    public bool $showCorrectionForm = false;

    public string $correctionRequestType = '';

    public int $correctionRecordId = 0;

    public array $correctionForm = [];

    public function mount(int $personnelId): void
    {
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
        $this->resetCreateForms();
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrRequestsReadService::class)->build($this->personnel(), [
            'search' => $this->search,
            'type' => $this->typeFilter,
            'status' => $this->statusFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    #[Computed(cache: true)]
    public function leaveTypeOptions(): array
    {
        return LeaveType::query()
            ->select('id', 'name', 'requires_document')
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $leaveType) => [
                'id' => (int) $leaveType->id,
                'label' => (string) $leaveType->name,
                'requires_document' => (bool) $leaveType->requires_document,
            ])
            ->all();
    }

    public function openCreateForm(string $type): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        $permission = match ($type) {
            'leave' => 'submit-self-service-leaves',
            'vacation' => 'submit-self-service-vacations',
            'business_trip' => 'submit-self-service-business-trips',
            default => null,
        };

        if (! $permission || ! $access->canAccess(Auth::user(), $permission)) {
            abort(403);
        }

        if ($this->activeCreateForm !== $type) {
            $this->resetCreateForms();
        }

        $this->resetValidation();
        $this->activeCreateForm = $type;
    }

    public function cancelCreateForm(): void
    {
        $this->resetValidation();
        $this->resetCreateForms();
    }

    public function openCorrectionForm(string $requestType, int $recordId): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        abort_unless($access->canAccess(Auth::user(), 'request-own-request-correction'), 403);

        $this->resetValidation();
        $this->showCorrectionForm = true;
        $this->correctionRequestType = $requestType;
        $this->correctionRecordId = $recordId;
        $this->correctionForm = $this->defaultCorrectionForm($requestType, $recordId);
        $this->dispatch('my-hr-correction-form-opened');
    }

    public function cancelCorrectionForm(): void
    {
        $this->resetValidation();
        $this->showCorrectionForm = false;
        $this->correctionRequestType = '';
        $this->correctionRecordId = 0;
        $this->correctionForm = [];
    }

    public function storeCorrectionRequest(): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        abort_unless($access->canAccess(Auth::user(), 'request-own-request-correction'), 403);

        $this->validate($this->correctionRules(), [], $this->correctionValidationAttributes());

        app(MyHrRequestCorrectionService::class)->create(
            $this->personnel(),
            Auth::user(),
            $this->correctionRequestType,
            $this->correctionRecordId,
            (string) data_get($this->correctionForm, 'reason'),
            $this->correctionPatchPayload()
        );

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.requests.messages.correction_created'));
        $this->cancelCorrectionForm();
        unset($this->payload);
    }

    public function storeLeaveRequest(): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        abort_unless($access->canAccess(Auth::user(), 'submit-self-service-leaves'), 403);

        $this->validate($this->leaveRules(), [], $this->leaveValidationAttributes());

        $payload = $this->normalizeLeavePayload();

        if ($this->leaveDocument instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $this->leaveDocument->store('leaves', 'public');
        }

        Leave::query()->create($payload);

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.requests.messages.leave_created'));
        $this->resetCreateForms();
    }

    public function storeVacationRequest(): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        abort_unless($access->canAccess(Auth::user(), 'submit-self-service-vacations'), 403);

        $this->validate($this->vacationRules(), [], $this->vacationValidationAttributes());

        $route = app(ApprovalRouteResolverService::class)->resolve($this->personnel(), 'vacation');
        $start = Carbon::parse((string) data_get($this->vacationForm, 'start_date'))->startOfDay();
        $end = Carbon::parse((string) data_get($this->vacationForm, 'end_date'))->startOfDay();
        $duration = $start->diffInDays($end) + 1;

        PersonnelVacation::query()->create([
            'tabel_no' => $this->personnel()->tabel_no,
            'vacation_places' => trim((string) data_get($this->vacationForm, 'vacation_places')),
            'duration' => $duration,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'return_work_date' => $end->copy()->addDay()->toDateString(),
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'pending',
            'approver_personnel_id' => $route['approver_personnel_id'],
            'fallback_approver_personnel_id' => $route['fallback_approver_personnel_id'],
            'approval_route_source' => $route['approval_route_source'],
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => Auth::id(),
            'added_by' => Auth::id(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.requests.messages.vacation_created'));
        $this->resetCreateForms();
    }

    public function storeBusinessTripRequest(): void
    {
        /** @var MyHrAccess $access */
        $access = app(MyHrAccess::class);

        abort_unless($access->canAccess(Auth::user(), 'submit-self-service-business-trips'), 403);

        $this->validate($this->businessTripRules(), [], $this->businessTripValidationAttributes());

        $route = app(ApprovalRouteResolverService::class)->resolve($this->personnel(), 'business_trip');

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $this->personnel()->tabel_no,
            'location' => trim((string) data_get($this->businessTripForm, 'location')),
            'description' => trim((string) data_get($this->businessTripForm, 'description')),
            'start_date' => data_get($this->businessTripForm, 'start_date'),
            'end_date' => data_get($this->businessTripForm, 'end_date'),
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'approval_status' => 'pending',
            'approver_personnel_id' => $route['approver_personnel_id'],
            'fallback_approver_personnel_id' => $route['fallback_approver_personnel_id'],
            'approval_route_source' => $route['approval_route_source'],
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => Auth::id(),
            'added_by' => Auth::id(),
        ]);

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.requests.messages.business_trip_created'));
        $this->resetCreateForms();
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()
            ->select(['id', 'tabel_no', 'surname', 'name', 'patronymic', 'structure_id', 'position_id'])
            ->findOrFail($this->personnelId);
    }

    protected function resetCreateForms(): void
    {
        $this->activeCreateForm = '';
        $this->leaveDocument = null;
        $this->showCorrectionForm = false;
        $this->correctionRequestType = '';
        $this->correctionRecordId = 0;
        $this->correctionForm = [];
        $this->leaveForm = [
            'leave_type_id' => null,
            'starts_at' => null,
            'ends_at' => null,
            'duration_unit' => 'day',
            'partial_day_part' => null,
            'starts_time' => null,
            'ends_time' => null,
            'reason' => null,
        ];
        $this->vacationForm = [
            'vacation_places' => null,
            'start_date' => null,
            'end_date' => null,
            'reason' => null,
        ];
        $this->businessTripForm = [
            'location' => null,
            'start_date' => null,
            'end_date' => null,
            'description' => null,
        ];
    }

    protected function leaveRules(): array
    {
        $requiresDocument = $this->selectedLeaveTypeRequiresDocument();

        return [
            'leaveForm.leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'leaveForm.starts_at' => ['required', 'date'],
            'leaveForm.duration_unit' => ['required', 'in:day,half_day,hour'],
            'leaveForm.ends_at' => ['required_if:leaveForm.duration_unit,day', 'nullable', 'date', 'after_or_equal:leaveForm.starts_at'],
            'leaveForm.partial_day_part' => ['required_if:leaveForm.duration_unit,half_day', 'nullable', 'in:first_half,second_half'],
            'leaveForm.starts_time' => ['required_if:leaveForm.duration_unit,hour', 'nullable', 'date_format:H:i'],
            'leaveForm.ends_time' => ['required_if:leaveForm.duration_unit,hour', 'nullable', 'date_format:H:i', 'after:leaveForm.starts_time'],
            'leaveForm.reason' => ['nullable', 'string', 'max:2000'],
            'leaveDocument' => [
                $requiresDocument ? 'required' : 'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if ($value instanceof TemporaryUploadedFile || is_string($value)) {
                        return;
                    }

                    $fail(__('validation.file', ['attribute' => __('personnel::my_hr.requests.fields.supporting_document')]));
                },
            ],
        ];
    }

    protected function vacationRules(): array
    {
        return [
            'vacationForm.vacation_places' => ['required', 'string', 'max:2000'],
            'vacationForm.start_date' => ['required', 'date'],
            'vacationForm.end_date' => ['required', 'date', 'after_or_equal:vacationForm.start_date'],
        ];
    }

    protected function businessTripRules(): array
    {
        return [
            'businessTripForm.location' => ['required', 'string', 'max:2000'],
            'businessTripForm.start_date' => ['required', 'date'],
            'businessTripForm.end_date' => ['required', 'date', 'after_or_equal:businessTripForm.start_date'],
            'businessTripForm.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function leaveValidationAttributes(): array
    {
        return [
            'leaveForm.leave_type_id' => __('personnel::my_hr.requests.fields.leave_type'),
            'leaveForm.starts_at' => __('personnel::my_hr.requests.fields.start_date'),
            'leaveForm.ends_at' => __('personnel::my_hr.requests.fields.end_date'),
            'leaveForm.duration_unit' => __('personnel::my_hr.requests.fields.duration_unit'),
            'leaveForm.partial_day_part' => __('personnel::my_hr.requests.fields.partial_day_part'),
            'leaveForm.starts_time' => __('personnel::my_hr.requests.fields.start_time'),
            'leaveForm.ends_time' => __('personnel::my_hr.requests.fields.end_time'),
            'leaveForm.reason' => __('personnel::my_hr.requests.fields.reason'),
            'leaveDocument' => __('personnel::my_hr.requests.fields.supporting_document'),
        ];
    }

    protected function vacationValidationAttributes(): array
    {
        return [
            'vacationForm.vacation_places' => __('personnel::my_hr.requests.fields.destination'),
            'vacationForm.start_date' => __('personnel::my_hr.requests.fields.start_date'),
            'vacationForm.end_date' => __('personnel::my_hr.requests.fields.end_date'),
        ];
    }

    protected function businessTripValidationAttributes(): array
    {
        return [
            'businessTripForm.location' => __('personnel::my_hr.requests.fields.location'),
            'businessTripForm.start_date' => __('personnel::my_hr.requests.fields.start_date'),
            'businessTripForm.end_date' => __('personnel::my_hr.requests.fields.end_date'),
            'businessTripForm.description' => __('personnel::my_hr.requests.fields.description'),
        ];
    }

    protected function selectedLeaveTypeRequiresDocument(): bool
    {
        $leaveTypeId = (int) data_get($this->leaveForm, 'leave_type_id', 0);

        if ($leaveTypeId <= 0) {
            return false;
        }

        foreach ($this->leaveTypeOptions as $option) {
            if ((int) $option['id'] === $leaveTypeId) {
                return (bool) ($option['requires_document'] ?? false);
            }
        }

        return false;
    }

    protected function normalizeLeavePayload(): array
    {
        $route = app(ApprovalRouteResolverService::class)->resolve($this->personnel(), 'leave');

        $durationUnit = in_array(data_get($this->leaveForm, 'duration_unit'), ['day', 'half_day', 'hour'], true)
            ? (string) data_get($this->leaveForm, 'duration_unit')
            : 'day';

        $startsAt = (string) data_get($this->leaveForm, 'starts_at');
        $endsAt = $durationUnit === 'day'
            ? ((string) (data_get($this->leaveForm, 'ends_at') ?: $startsAt))
            : $startsAt;

        $totalDays = 1;
        $totalMinutes = null;

        if ($durationUnit === 'day') {
            $totalDays = Carbon::parse($startsAt)->diffInDays(Carbon::parse($endsAt)) + 1;
        } elseif ($durationUnit === 'hour') {
            $startTime = Carbon::createFromFormat('H:i', (string) data_get($this->leaveForm, 'starts_time'));
            $endTime = Carbon::createFromFormat('H:i', (string) data_get($this->leaveForm, 'ends_time'));
            $totalMinutes = $startTime->diffInMinutes($endTime);
        }

        return [
            'tabel_no' => $this->personnel()->tabel_no,
            'leave_type_id' => (int) data_get($this->leaveForm, 'leave_type_id'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_unit' => $durationUnit,
            'partial_day_part' => $durationUnit === 'half_day' ? data_get($this->leaveForm, 'partial_day_part') : null,
            'starts_time' => $durationUnit === 'hour' ? data_get($this->leaveForm, 'starts_time') : null,
            'ends_time' => $durationUnit === 'hour' ? data_get($this->leaveForm, 'ends_time') : null,
            'total_days' => $totalDays,
            'total_minutes' => $totalMinutes,
            'reason' => trim((string) data_get($this->leaveForm, 'reason')),
            'status_id' => OrderStatusEnum::PENDING->value,
            'assigned_to' => $route['approver_personnel_id'],
            'fallback_approver_personnel_id' => $route['fallback_approver_personnel_id'],
            'approval_route_source' => $route['approval_route_source'],
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => Auth::id(),
            'document_path' => is_string($this->leaveDocument) ? $this->leaveDocument : null,
        ];
    }

    protected function correctionRules(): array
    {
        $rules = [
            'correctionForm.reason' => ['required', 'string', 'max:2000'],
        ];

        if ($this->correctionRequestType === 'leave') {
            $rules['correctionForm.starts_at'] = ['required', 'date'];
            $rules['correctionForm.ends_at'] = ['required', 'date', 'after_or_equal:correctionForm.starts_at'];
        } elseif ($this->correctionRequestType === 'vacation') {
            $rules['correctionForm.vacation_places'] = ['required', 'string', 'max:2000'];
            $rules['correctionForm.start_date'] = ['required', 'date'];
            $rules['correctionForm.end_date'] = ['required', 'date', 'after_or_equal:correctionForm.start_date'];
        } elseif ($this->correctionRequestType === 'business_trip') {
            $rules['correctionForm.location'] = ['required', 'string', 'max:2000'];
            $rules['correctionForm.start_date'] = ['required', 'date'];
            $rules['correctionForm.end_date'] = ['required', 'date', 'after_or_equal:correctionForm.start_date'];
            $rules['correctionForm.description'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    protected function correctionValidationAttributes(): array
    {
        return [
            'correctionForm.reason' => __('personnel::my_hr.requests.fields.reason'),
            'correctionForm.starts_at' => __('personnel::my_hr.requests.fields.start_date'),
            'correctionForm.ends_at' => __('personnel::my_hr.requests.fields.end_date'),
            'correctionForm.vacation_places' => __('personnel::my_hr.requests.fields.destination'),
            'correctionForm.start_date' => __('personnel::my_hr.requests.fields.start_date'),
            'correctionForm.end_date' => __('personnel::my_hr.requests.fields.end_date'),
            'correctionForm.location' => __('personnel::my_hr.requests.fields.location'),
            'correctionForm.description' => __('personnel::my_hr.requests.fields.description'),
        ];
    }

    protected function defaultCorrectionForm(string $requestType, int $recordId): array
    {
        if ($requestType === 'leave') {
            $leave = Leave::query()->findOrFail($recordId);

            return [
                'starts_at' => optional($leave->starts_at)->format('Y-m-d'),
                'ends_at' => optional($leave->ends_at)->format('Y-m-d'),
                'reason' => $leave->reason,
            ];
        }

        if ($requestType === 'vacation') {
            $vacation = PersonnelVacation::query()->findOrFail($recordId);

            return [
                'vacation_places' => $vacation->vacation_places,
                'start_date' => optional($vacation->start_date)->format('Y-m-d'),
                'end_date' => optional($vacation->end_date)->format('Y-m-d'),
                'reason' => null,
            ];
        }

        $trip = PersonnelBusinessTrip::query()->findOrFail($recordId);

        return [
            'location' => $trip->location,
            'description' => $trip->description,
            'start_date' => optional($trip->start_date)->format('Y-m-d'),
            'end_date' => optional($trip->end_date)->format('Y-m-d'),
            'reason' => null,
        ];
    }

    protected function correctionPatchPayload(): array
    {
        return match ($this->correctionRequestType) {
            'leave' => [
                'starts_at' => data_get($this->correctionForm, 'starts_at'),
                'ends_at' => data_get($this->correctionForm, 'ends_at'),
                'reason' => data_get($this->correctionForm, 'reason'),
            ],
            'vacation' => [
                'vacation_places' => data_get($this->correctionForm, 'vacation_places'),
                'start_date' => data_get($this->correctionForm, 'start_date'),
                'end_date' => data_get($this->correctionForm, 'end_date'),
            ],
            'business_trip' => [
                'location' => data_get($this->correctionForm, 'location'),
                'description' => data_get($this->correctionForm, 'description'),
                'start_date' => data_get($this->correctionForm, 'start_date'),
                'end_date' => data_get($this->correctionForm, 'end_date'),
            ],
            default => [],
        };
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.requests');
    }
}
