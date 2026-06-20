<?php

namespace App\Modules\Attendance\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Services\StructurePathService;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceShiftManagementService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShiftManagement extends Component
{
    use WithRuntimeMemo;
    use NestedStructureTrait;

    public bool $canManage = false;

    public ?int $editingShiftId = null;

    public ?int $editingAssignmentId = null;

    public string $personnelSearch = '';

    public string $structureSearch = '';

    public ?int $selectedStructureId = null;

    /**
     * @var array<string,string>|null
     */
    public ?array $selectedPersonnel = null;

    /**
     * @var array<string,mixed>
     */
    public array $shiftForm = [
        'name' => '',
        'start_time' => '09:00',
        'end_time' => '18:00',
        'break_minutes' => 60,
        'is_night_shift' => false,
        'in_flex_before_minutes' => 0,
        'in_flex_after_minutes' => 0,
        'out_flex_before_minutes' => 0,
        'out_flex_after_minutes' => 0,
        'is_active' => true,
    ];

    /**
     * @var array<string,mixed>
     */
    public array $assignmentForm = [
        'tabel_no' => '',
        'shift_id' => null,
        'effective_from' => '',
        'effective_to' => '',
        'is_active' => true,
        'assignment_source' => 'manual_ui',
    ];

    protected function shiftRules(): array
    {
        return [
            'shiftForm.name' => ['required', 'string', 'max:120'],
            'shiftForm.start_time' => ['required', 'date_format:H:i'],
            'shiftForm.end_time' => ['required', 'date_format:H:i'],
            'shiftForm.break_minutes' => ['required', 'integer', 'min:0', 'max:720'],
            'shiftForm.in_flex_before_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'shiftForm.in_flex_after_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'shiftForm.out_flex_before_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'shiftForm.out_flex_after_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'shiftForm.is_active' => ['required', 'boolean'],
        ];
    }

    protected function assignmentRules(): array
    {
        return [
            'assignmentForm.tabel_no' => ['required', 'string', Rule::exists('personnels', 'tabel_no')],
            'assignmentForm.shift_id' => ['required', 'integer', Rule::exists('attendance_shifts', 'id')->whereNull('deleted_at')],
            'assignmentForm.effective_from' => ['required', 'date'],
            'assignmentForm.effective_to' => ['nullable', 'date', 'after_or_equal:assignmentForm.effective_from'],
            'assignmentForm.is_active' => ['required', 'boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'shiftForm.name' => __('attendance::shift_management.fields.shift_name'),
            'shiftForm.start_time' => __('attendance::shift_management.fields.start_time'),
            'shiftForm.end_time' => __('attendance::shift_management.fields.end_time'),
            'shiftForm.break_minutes' => __('attendance::shift_management.fields.break_minutes'),
            'shiftForm.in_flex_before_minutes' => __('attendance::shift_management.fields.check_in_flex_before'),
            'shiftForm.in_flex_after_minutes' => __('attendance::shift_management.fields.check_in_flex_after'),
            'shiftForm.out_flex_before_minutes' => __('attendance::shift_management.fields.check_out_flex_before'),
            'shiftForm.out_flex_after_minutes' => __('attendance::shift_management.fields.check_out_flex_after'),
            'assignmentForm.tabel_no' => __('attendance::shift_management.fields.search_personnel'),
            'assignmentForm.shift_id' => __('attendance::shift_management.fields.shift'),
            'assignmentForm.effective_from' => __('attendance::shift_management.fields.effective_from'),
            'assignmentForm.effective_to' => __('attendance::shift_management.fields.effective_to'),
        ];
    }

    public function mount(AttendanceAuthorizationService $authorization): void
    {
        $authorization->authorize('attendance.shifts.manage');
        $this->canManage = $authorization->can('attendance.shifts.manage');
        $this->assignmentForm['effective_from'] = now()->toDateString();
    }

    public function selectPersonnel(string $tabelNo, string $fullname): void
    {
        $this->assignmentForm['tabel_no'] = $tabelNo;
        $this->selectedPersonnel = [
            'tabel_no' => $tabelNo,
            'fullname' => $fullname,
        ];
        $this->personnelSearch = '';
    }

    public function clearPersonnel(): void
    {
        $this->assignmentForm['tabel_no'] = '';
        $this->selectedPersonnel = null;
        $this->personnelSearch = '';
    }

    public function editShift(int $shiftId): void
    {
        $shift = AttendanceShift::query()->findOrFail($shiftId);

        $this->editingShiftId = $shift->id;
        $this->shiftForm = [
            'name' => (string) $shift->name,
            'start_time' => substr((string) $shift->start_time, 0, 5),
            'end_time' => substr((string) $shift->end_time, 0, 5),
            'break_minutes' => (int) $shift->break_minutes,
            'is_night_shift' => (bool) $shift->is_night_shift,
            'in_flex_before_minutes' => (int) $shift->in_flex_before_minutes,
            'in_flex_after_minutes' => (int) $shift->in_flex_after_minutes,
            'out_flex_before_minutes' => (int) $shift->out_flex_before_minutes,
            'out_flex_after_minutes' => (int) $shift->out_flex_after_minutes,
            'is_active' => (bool) $shift->is_active,
        ];
    }

    public function resetShiftForm(): void
    {
        $this->editingShiftId = null;
        $this->shiftForm = [
            'name' => '',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
            'in_flex_before_minutes' => 0,
            'in_flex_after_minutes' => 0,
            'out_flex_before_minutes' => 0,
            'out_flex_after_minutes' => 0,
            'is_active' => true,
        ];
        $this->resetValidation();
        $this->resetRuntimeMemo();
    }

    public function saveShift(AttendanceShiftManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate($this->shiftRules());

        try {
            $service->upsertShift(
                payload: $this->shiftForm,
                userId: (int) Auth::id(),
                shift: $this->editingShiftId ? AttendanceShift::query()->find($this->editingShiftId) : null
            );
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::shift_management.messages.shift_saved'));
        $this->resetShiftForm();
    }

    public function deactivateShift(int $shiftId, AttendanceShiftManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $shift = AttendanceShift::query()->find($shiftId);
        if (! $shift) {
            return;
        }

        try {
            $service->deactivateShift($shift, (int) Auth::id());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
            return;
        }

        if ($this->editingShiftId === $shiftId) {
            $this->resetShiftForm();
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::shift_management.messages.shift_deactivated'));
        $this->resetRuntimeMemo();
    }

    public function editAssignment(int $assignmentId): void
    {
        $assignment = AttendanceShiftAssignment::query()
            ->with(['personnel:tabel_no,surname,name,patronymic'])
            ->findOrFail($assignmentId);

        $this->editingAssignmentId = $assignment->id;
        $this->assignmentForm = [
            'tabel_no' => (string) $assignment->tabel_no,
            'shift_id' => (int) $assignment->shift_id,
            'effective_from' => $assignment->effective_from?->toDateString() ?? now()->toDateString(),
            'effective_to' => $assignment->effective_to?->toDateString() ?? '',
            'is_active' => (bool) $assignment->is_active,
            'assignment_source' => (string) ($assignment->assignment_source ?: 'manual_ui'),
        ];
        $this->selectedPersonnel = [
            'tabel_no' => (string) $assignment->tabel_no,
            'fullname' => (string) ($assignment->personnel?->fullname ?? $assignment->tabel_no),
        ];
    }

    public function resetAssignmentForm(): void
    {
        $this->editingAssignmentId = null;
        $this->assignmentForm = [
            'tabel_no' => '',
            'shift_id' => null,
            'effective_from' => now()->toDateString(),
            'effective_to' => '',
            'is_active' => true,
            'assignment_source' => 'manual_ui',
        ];
        $this->selectedPersonnel = null;
        $this->personnelSearch = '';
        $this->resetValidation();
        $this->resetRuntimeMemo();
    }

    public function updatedSelectedStructureId(): void
    {
        $this->resetRuntimeMemo();
        $this->clearPersonnel();
    }

    public function saveAssignment(AttendanceShiftManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate($this->assignmentRules());

        try {
            $service->upsertAssignment(
                payload: $this->assignmentForm,
                userId: (int) Auth::id(),
                assignment: $this->editingAssignmentId ? AttendanceShiftAssignment::query()->find($this->editingAssignmentId) : null
            );
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());
            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::shift_management.messages.assignment_saved'));
        $this->resetAssignmentForm();
    }

    public function deactivateAssignment(int $assignmentId, AttendanceShiftManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $assignment = AttendanceShiftAssignment::query()->find($assignmentId);
        if (! $assignment) {
            return;
        }

        $service->deactivateAssignment($assignment, (int) Auth::id());

        if ($this->editingAssignmentId === $assignmentId) {
            $this->resetAssignmentForm();
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::shift_management.messages.assignment_deactivated'));
        $this->resetRuntimeMemo();
    }

    #[Computed]
    public function shifts(): Collection
    {
        return $this->rememberRuntime('attendanceShiftManagement.shifts', function () {
            return AttendanceShift::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();
        });
    }

    #[Computed]
    public function assignmentShifts(): Collection
    {
        return $this->rememberRuntime('attendanceShiftManagement.assignmentShifts', function () {
            return AttendanceShift::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        });
    }

    #[Computed]
    public function assignments(): Collection
    {
        $today = now()->toDateString();
        $structurePathService = app(StructurePathService::class);
        $structureIds = $this->currentStructureIds();

        return $this->rememberRuntime('attendanceShiftManagement.assignments.'.md5(json_encode([
            'structure_ids' => $structureIds,
            'today' => $today,
        ]) ?: ''), function () use ($structureIds, $today, $structurePathService) {
            $assignments = AttendanceShiftAssignment::query()
                ->with([
                    'shift:id,name,start_time,end_time',
                    'personnel:tabel_no,surname,name,patronymic,structure_id,position_id',
                    'personnel.position:id,name',
                ])
                ->when($structureIds !== [], function ($query) use ($structureIds) {
                    $query->whereHas('personnel', function ($personnelQuery) use ($structureIds): void {
                        $personnelQuery->whereIn('structure_id', $structureIds);
                    });
                })
                ->latest('effective_from')
                ->latest('id')
                ->limit(20)
                ->get();

            $assignments = $this->decorateAssignments($assignments, $today);

            return $assignments->map(function (AttendanceShiftAssignment $assignment) use ($structurePathService) {
                if ($assignment->personnel) {
                    $assignment->personnel->setAttribute(
                        'structure_path',
                        $structurePathService->resolve((int) $assignment->personnel->structure_id)
                    );
                }

                return $assignment;
            });
        });
    }

    #[Computed]
    public function personnelResults(): Collection
    {
        if (mb_strlen(trim($this->personnelSearch)) < 2) {
            return collect();
        }

        $term = trim($this->personnelSearch);
        $wildcard = '%'.$term.'%';
        $structurePathService = app(StructurePathService::class);
        $structureIds = $this->currentStructureIds();

        return $this->rememberRuntime('attendanceShiftManagement.personnelResults.'.md5(json_encode([
            'term' => $term,
            'structure_ids' => $structureIds,
        ]) ?: ''), function () use ($wildcard, $structureIds, $structurePathService) {
            return Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id', 'position_id')
                ->whereNull('leave_work_date')
                ->when($structureIds !== [], function ($query) use ($structureIds) {
                    $query->whereIn('structure_id', $structureIds);
                })
                ->where(function ($query) use ($wildcard): void {
                    $query->where('tabel_no', 'like', $wildcard)
                        ->orWhere('surname', 'like', $wildcard)
                        ->orWhere('name', 'like', $wildcard)
                        ->orWhere('patronymic', 'like', $wildcard);
                })
                ->with([
                    'position:id,name',
                ])
                ->orderBy('surname')
                ->limit(12)
                ->get()
                ->map(function (Personnel $personnel) use ($structurePathService) {
                    $personnel->setAttribute('structure_path', $structurePathService->resolve((int) $personnel->structure_id));

                    return $personnel;
                });
        });
    }

    #[Computed]
    public function structureOptions(): Collection
    {
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);

        return $structureScopeRead->filterOptions($this->structureSearch);
    }

    #[Computed]
    public function selectedStructureLabel(): ?string
    {
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);

        return $this->rememberRuntime('attendanceShiftManagement.selectedStructureLabel.'.($this->selectedStructureId ?? 'all'), function () use ($structureScopeRead) {
            return $structureScopeRead->label($this->selectedStructureId);
        });
    }

    #[Computed]
    public function selectedPersonnelActiveAssignment(): ?AttendanceShiftAssignment
    {
        if (empty($this->assignmentForm['tabel_no'])) {
            return null;
        }

        $today = now()->toDateString();

        return $this->rememberRuntime('attendanceShiftManagement.activeAssignment.'.md5(json_encode([
            'tabel_no' => $this->assignmentForm['tabel_no'],
            'today' => $today,
        ]) ?: ''), function () use ($today) {
            return AttendanceShiftAssignment::query()
                ->with('shift:id,name,start_time,end_time')
                ->where('tabel_no', $this->assignmentForm['tabel_no'])
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->latest('effective_from')
                ->latest('id')
                ->first();
        });
    }

    #[Computed]
    public function selectedPersonnelRecord(): ?Personnel
    {
        if (empty($this->assignmentForm['tabel_no'])) {
            return null;
        }

        $structurePathService = app(StructurePathService::class);

        $selectedPersonnelRecord = $this->rememberRuntime('attendanceShiftManagement.selectedPersonnelRecord.'.$this->assignmentForm['tabel_no'], function () {
            return Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id', 'position_id')
                ->with('position:id,name')
                ->where('tabel_no', $this->assignmentForm['tabel_no'])
                ->first();
        });

        if ($selectedPersonnelRecord) {
            $selectedPersonnelRecord->setAttribute(
                'structure_path',
                $structurePathService->resolve((int) $selectedPersonnelRecord->structure_id)
            );
        }

        return $selectedPersonnelRecord;
    }

    protected function currentStructureIds(): array
    {
        return $this->rememberRuntime('attendanceShiftManagement.currentStructureIds.'.($this->selectedStructureId ?? 'all'), function () {
            return $this->selectedStructureId
                ? $this->getNestedStructure($this->selectedStructureId)
                : [];
        });
    }

    public function render()
    {
        return view('attendance::livewire.attendance.shift-management');
    }

    /**
     * @param  Collection<int, AttendanceShiftAssignment>  $assignments
     * @return Collection<int, AttendanceShiftAssignment>
     */
    protected function decorateAssignments(Collection $assignments, string $today): Collection
    {
        if ($assignments->isEmpty()) {
            return $assignments;
        }

        $overlapPool = AttendanceShiftAssignment::query()
            ->whereIn('tabel_no', $assignments->pluck('tabel_no')->filter()->unique()->values()->all())
            ->where('is_active', true)
            ->get(['id', 'tabel_no', 'effective_from', 'effective_to']);

        return $assignments->map(function (AttendanceShiftAssignment $assignment) use ($today, $overlapPool) {
            $rangeStart = $this->normalizeDate($assignment->effective_from);
            $rangeEnd = $this->normalizeDate($assignment->effective_to) ?? '2999-12-31';

            $assignment->setAttribute(
                'effective_today',
                $assignment->is_active
                && $rangeStart !== null
                && $rangeStart <= $today
                && $rangeEnd >= $today
            );

            $assignment->setAttribute(
                'starts_in_future',
                $assignment->is_active
                && $rangeStart !== null
                && $rangeStart > $today
            );

            $assignment->setAttribute(
                'is_expired',
                $assignment->is_active
                && $rangeEnd < $today
            );

            $assignment->setAttribute(
                'has_overlap_warning',
                $overlapPool->contains(function (AttendanceShiftAssignment $other) use ($assignment, $rangeStart, $rangeEnd) {
                    if ((int) $other->id === (int) $assignment->id || (string) $other->tabel_no !== (string) $assignment->tabel_no) {
                        return false;
                    }

                    $otherStart = $this->normalizeDate($other->effective_from);
                    $otherEnd = $this->normalizeDate($other->effective_to) ?? '2999-12-31';

                    if ($rangeStart === null || $otherStart === null) {
                        return false;
                    }

                    return $otherStart <= $rangeEnd && $otherEnd >= $rangeStart;
                })
            );

            return $assignment;
        });
    }

    protected function normalizeDate(CarbonInterface|string|null $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        if (blank($value)) {
            return null;
        }

        return (string) $value;
    }
}
