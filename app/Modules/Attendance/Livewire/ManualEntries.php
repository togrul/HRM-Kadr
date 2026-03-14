<?php

namespace App\Modules\Attendance\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\AttendanceManualEntry;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Services\StructurePathService;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceManualEntryService;
use App\Modules\Attendance\Application\Services\AttendanceManualMetricsResolverService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ManualEntries extends Component
{
    use WithRuntimeMemo;
    use WithPagination;
    use NestedStructureTrait;

    public bool $embedded = false;

    public string $queueStatus = 'pending';

    public int $perPage = 15;

    public array $rejectNotes = [];

    public bool $canWrite = false;

    public bool $canApprove = false;

    public bool $autoCalculatedPreview = false;

    public bool $manualMetricOverride = false;

    public ?int $selectedStructureId = null;

    public string $personnelSearch = '';

    /**
     * @var array<string,string>|null
     */
    public ?array $selectedPersonnel = null;

    /**
     * @var array<string,mixed>
     */
    public array $preview = [
        'planned_minutes' => 0,
        'worked_minutes' => 0,
        'late_minutes' => 0,
        'early_leave_minutes' => 0,
        'overtime_minutes' => 0,
        'baseline_source' => 'none',
        'baseline_label' => null,
    ];

    public array $form = [
        'tabel_no' => '',
        'date' => '',
        'check_in_at' => '',
        'check_out_at' => '',
        'shift_source_mode' => 'auto',
        'explicit_shift_id' => null,
        'worked_minutes' => 0,
        'overtime_minutes' => 0,
        'late_minutes' => 0,
        'early_leave_minutes' => 0,
        'manual_metric_override' => false,
        'absence_code' => '',
        'reason' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.tabel_no' => ['required', 'string', 'exists:personnels,tabel_no'],
            'form.date' => ['required', 'date'],
            'form.check_in_at' => ['nullable', 'date_format:H:i', 'required_with:form.check_out_at'],
            'form.check_out_at' => ['nullable', 'date_format:H:i', 'required_with:form.check_in_at'],
            'form.shift_source_mode' => ['required', Rule::in(['auto', 'explicit'])],
            'form.explicit_shift_id' => ['nullable', 'integer', 'required_if:form.shift_source_mode,explicit', Rule::exists('attendance_shifts', 'id')->whereNull('deleted_at')],
            'form.worked_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'form.overtime_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'form.late_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'form.early_leave_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'form.absence_code' => ['nullable', 'string', 'max:32'],
            'form.reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.tabel_no' => __('attendance::manual_entries.labels.personnel'),
            'form.date' => __('attendance::manual_entries.labels.date'),
            'form.check_in_at' => __('attendance::manual_entries.labels.check_in_time'),
            'form.check_out_at' => __('attendance::manual_entries.labels.check_out_time'),
            'form.shift_source_mode' => __('attendance::manual_entries.labels.shift_source'),
            'form.explicit_shift_id' => __('attendance::manual_entries.labels.calculation_shift'),
            'form.worked_minutes' => __('attendance::manual_entries.labels.worked_minutes'),
            'form.overtime_minutes' => __('attendance::manual_entries.labels.overtime_minutes'),
            'form.late_minutes' => __('attendance::manual_entries.labels.late_minutes'),
            'form.early_leave_minutes' => __('attendance::manual_entries.labels.early_leave_minutes'),
            'form.absence_code' => __('attendance::manual_entries.labels.absence_code'),
            'form.reason' => __('attendance::manual_entries.labels.reason'),
        ];
    }

    public function mount(AttendanceAuthorizationService $authorization, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->form['date'] = now()->toDateString();
        $canView = $authorization->can('attendance.manual.view');
        $this->canWrite = $authorization->can('attendance.manual.write');
        $this->canApprove = $authorization->can('attendance.manual.approve');

        if (! $canView && ! $this->canWrite && ! $this->canApprove) {
            abort(403);
        }
    }

    public function updatedQueueStatus(): void
    {
        $this->resetRuntimeMemo();
        $this->resetPage();
    }

    public function updatedSelectedStructureId(): void
    {
        $this->resetRuntimeMemo();
        $this->clearPersonnel();
        $this->resetPage();
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'manualMetricOverride') {
            $this->updatedManualMetricOverride((bool) $value);
            return;
        }

        if ($property === 'form.shift_source_mode' && $value !== 'explicit') {
            $this->form['explicit_shift_id'] = null;
        }

        if (in_array($property, ['form.tabel_no', 'form.date', 'form.check_in_at', 'form.check_out_at', 'form.shift_source_mode', 'form.explicit_shift_id'], true)) {
            $this->refreshCalculatedFields();
        }
    }

    public function selectPersonnel(string $tabelNo, string $fullname): void
    {
        $this->form['tabel_no'] = $tabelNo;
        $this->selectedPersonnel = [
            'tabel_no' => $tabelNo,
            'fullname' => $fullname,
        ];
        $this->personnelSearch = '';
        $this->refreshCalculatedFields();
    }

    public function clearPersonnel(): void
    {
        $this->form['tabel_no'] = '';
        $this->selectedPersonnel = null;
        $this->personnelSearch = '';
        $this->refreshCalculatedFields();
    }

    public function updatedManualMetricOverride(bool $value): void
    {
        $this->form['manual_metric_override'] = $value;

        if (! $value) {
            $this->refreshCalculatedFields();
            return;
        }

        $this->autoCalculatedPreview = false;
    }

    public function save(AttendanceManualEntryService $service): void
    {
        if (! $this->canWrite) {
            abort(403);
        }

        $this->validate();

        try {
            $service->upsert(
                tabelNo: (string) $this->form['tabel_no'],
                date: (string) $this->form['date'],
                payload: $this->form,
                enteredBy: (int) Auth::id(),
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: __('attendance::manual_entries.messages.validation_failed');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::manual_entries.messages.saved'));

        $this->form['check_in_at'] = '';
        $this->form['check_out_at'] = '';
        $this->form['shift_source_mode'] = 'auto';
        $this->form['explicit_shift_id'] = null;
        $this->form['worked_minutes'] = 0;
        $this->form['overtime_minutes'] = 0;
        $this->form['late_minutes'] = 0;
        $this->form['early_leave_minutes'] = 0;
        $this->form['manual_metric_override'] = false;
        $this->form['absence_code'] = '';
        $this->form['reason'] = '';
        $this->autoCalculatedPreview = false;
        $this->manualMetricOverride = false;
        $this->selectedPersonnel = null;
        $this->personnelSearch = '';
        $this->resetPreview();
    }

    public function approve(int $entryId, AttendanceManualEntryService $service): void
    {
        if (! $this->canApprove) {
            abort(403);
        }

        $entry = AttendanceManualEntry::query()->find($entryId);
        if (! $entry) {
            return;
        }

        try {
            $service->approve($entry, (int) Auth::id());
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: __('attendance::manual_entries.messages.validation_failed');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::manual_entries.messages.approved'));
    }

    public function reject(int $entryId, AttendanceManualEntryService $service): void
    {
        if (! $this->canApprove) {
            abort(403);
        }

        $entry = AttendanceManualEntry::query()->find($entryId);
        if (! $entry) {
            return;
        }

        try {
            $service->reject($entry, (int) Auth::id(), (string) ($this->rejectNotes[$entryId] ?? ''));
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: __('attendance::manual_entries.messages.validation_failed');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        unset($this->rejectNotes[$entryId]);
        $this->dispatch('notify', type: 'success', message: __('attendance::manual_entries.messages.rejected'));
    }

    public function getRecentEntriesProperty()
    {
        return $this->recentEntries();
    }

    #[Computed]
    public function recentEntries(): LengthAwarePaginator
    {
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);
        $page = (int) ($this->paginators['page'] ?? 1);
        $structureIds = $this->currentStructureIds();

        return $this->rememberRuntime('attendanceManualEntries.recentEntries.'.md5(json_encode([
            'queue_status' => $this->queueStatus,
            'page' => $page,
            'per_page' => $this->perPage,
            'structure_ids' => $structureIds,
        ]) ?: ''), function () use ($page, $structureIds, $structurePathService) {
            $entries = AttendanceManualEntry::query()
                ->with([
                    'personnel:tabel_no,surname,name,patronymic,structure_id',
                    'personnel.structure:id,name,parent_id',
                    'enteredBy:id,name',
                    'approvedBy:id,name',
                ])
                ->when(
                    $this->queueStatus !== 'all',
                    fn ($query) => $query->where('approval_status', $this->queueStatus)
                )
                ->when($structureIds !== [], function ($query) use ($structureIds): void {
                    $query->whereHas('personnel', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $structureIds));
                })
                ->latest('date')
                ->latest('id')
                ->paginate($this->perPage, page: $page);

            $entries->setCollection(
                $entries->getCollection()->map(function (AttendanceManualEntry $entry) use ($structurePathService) {
                    if ($entry->personnel) {
                        $entry->personnel->setAttribute(
                            'structure_path',
                            $structurePathService->resolve((int) $entry->personnel->structure_id)
                        );
                    }

                    return $entry;
                })
            );

            return $entries;
        });
    }

    #[Computed]
    public function availableShifts(): Collection
    {
        return $this->rememberRuntime('attendanceManualEntries.availableShifts', function () {
            return AttendanceShift::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'start_time',
                    'end_time',
                    'break_minutes',
                    'is_night_shift',
                    'in_flex_before_minutes',
                    'in_flex_after_minutes',
                    'out_flex_before_minutes',
                    'out_flex_after_minutes',
                ]);
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
        $structureIds = $this->currentStructureIds();
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        return $this->rememberRuntime('attendanceManualEntries.personnelResults.'.md5(json_encode([
            'term' => $term,
            'structure_ids' => $structureIds,
        ]) ?: ''), function () use ($wildcard, $structureIds, $structurePathService) {
            return Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id')
                ->where('is_pending', 0)
                ->whereNull('leave_work_date')
                ->when($structureIds !== [], function ($query) use ($structureIds): void {
                    $query->whereIn('structure_id', $structureIds);
                })
                ->where(function ($query) use ($wildcard): void {
                    $query->where('tabel_no', 'like', $wildcard)
                        ->orWhere('surname', 'like', $wildcard)
                        ->orWhere('name', 'like', $wildcard)
                        ->orWhere('patronymic', 'like', $wildcard);
                })
                ->with('structure:id,name,parent_id')
                ->orderBy('surname')
                ->orderBy('name')
                ->limit(12)
                ->get()
                ->map(function (Personnel $personnel) use ($structurePathService) {
                    $personnel->setAttribute('structure_path', $structurePathService->resolve((int) $personnel->structure_id));

                    return $personnel;
                });
        });
    }

    #[Computed]
    public function selectedShiftPreview(): ?AttendanceShift
    {
        if ($this->form['shift_source_mode'] !== 'explicit' || empty($this->form['explicit_shift_id'])) {
            return null;
        }

        return $this->availableShifts->firstWhere('id', (int) $this->form['explicit_shift_id']);
    }

    #[Computed]
    public function currentDefaultShift(): ?AttendanceShift
    {
        return $this->rememberRuntime('attendanceManualEntries.currentDefaultShift', function () {
            return app(AttendanceManualMetricsResolverService::class)->globalDefaultShift();
        });
    }

    #[Computed]
    public function selectedPersonnelActiveAssignment(): ?AttendanceShiftAssignment
    {
        if (empty($this->form['tabel_no'])) {
            return null;
        }

        $assignmentDate = filled($this->form['date']) ? (string) $this->form['date'] : now()->toDateString();

        return $this->rememberRuntime('attendanceManualEntries.activeAssignment.'.md5(json_encode([
            'tabel_no' => $this->form['tabel_no'],
            'date' => $assignmentDate,
        ]) ?: ''), function () use ($assignmentDate) {
            return AttendanceShiftAssignment::query()
                ->with('shift:id,name,start_time,end_time,break_minutes,is_night_shift,in_flex_before_minutes,in_flex_after_minutes,out_flex_before_minutes,out_flex_after_minutes')
                ->where('tabel_no', $this->form['tabel_no'])
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $assignmentDate)
                ->where(function ($query) use ($assignmentDate): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $assignmentDate);
                })
                ->latest('effective_from')
                ->latest('id')
                ->first();
        });
    }

    #[Computed]
    public function baselineContext(): array
    {
        $tabelNo = ! empty($this->form['tabel_no']) ? (string) $this->form['tabel_no'] : null;
        $date = filled($this->form['date']) ? (string) $this->form['date'] : now()->toDateString();

        return $this->rememberRuntime('attendanceManualEntries.baselineContext.'.md5(json_encode([
            'tabel_no' => $tabelNo,
            'date' => $date,
            'shift_source_mode' => $this->form['shift_source_mode'] ?? null,
            'explicit_shift_id' => $this->form['explicit_shift_id'] ?? null,
            'check_in_at' => $this->form['check_in_at'] ?? null,
            'check_out_at' => $this->form['check_out_at'] ?? null,
        ]) ?: ''), function () use ($tabelNo, $date) {
            return app(AttendanceManualMetricsResolverService::class)->resolveBaselineContext(
                $tabelNo,
                $date,
                $this->form
            );
        });
    }

    #[Computed]
    public function selectedPersonnelRecord(): ?Personnel
    {
        if (empty($this->form['tabel_no'])) {
            return null;
        }

        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        $selectedPersonnelRecord = $this->rememberRuntime('attendanceManualEntries.selectedPersonnelRecord.'.$this->form['tabel_no'], function () {
            return Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id')
                ->with('structure:id,name,parent_id')
                ->where('tabel_no', $this->form['tabel_no'])
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

    #[Computed]
    public function selectedStructureLabel(): ?string
    {
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);

        return $this->rememberRuntime('attendanceManualEntries.selectedStructureLabel.'.($this->selectedStructureId ?? 'all'), function () use ($structureScopeRead) {
            return $structureScopeRead->label($this->selectedStructureId);
        });
    }

    public function render()
    {
        if ($this->selectedPersonnelRecord && ! $this->selectedPersonnel) {
            $this->selectedPersonnel = [
                'tabel_no' => (string) $this->selectedPersonnelRecord->tabel_no,
                'fullname' => (string) $this->selectedPersonnelRecord->fullname,
            ];
        }

        return view('attendance::livewire.attendance.manual-entries');
    }

    /**
     * @return array<int,int>
     */
    private function currentStructureIds(): array
    {
        return $this->rememberRuntime('attendanceManualEntries.currentStructureIds.'.($this->selectedStructureId ?? 'all'), function () {
            return $this->selectedStructureId
                ? $this->getNestedStructure($this->selectedStructureId)
                : [];
        });
    }

    private function refreshCalculatedFields(): void
    {
        $this->autoCalculatedPreview = false;

        $tabelNo = trim((string) ($this->form['tabel_no'] ?? ''));
        $date = trim((string) ($this->form['date'] ?? ''));
        $checkInAt = trim((string) ($this->form['check_in_at'] ?? ''));
        $checkOutAt = trim((string) ($this->form['check_out_at'] ?? ''));

        if ($this->manualMetricOverride) {
            $this->preview['worked_minutes'] = (int) ($this->form['worked_minutes'] ?? 0);
            $this->preview['overtime_minutes'] = (int) ($this->form['overtime_minutes'] ?? 0);
            $this->preview['late_minutes'] = (int) ($this->form['late_minutes'] ?? 0);
            $this->preview['early_leave_minutes'] = (int) ($this->form['early_leave_minutes'] ?? 0);
            $this->preview['baseline_source'] = 'manual_override';
            return;
        }

        if ($date === '') {
            $this->resetPreview();
            return;
        }

        if ($checkInAt === '' || $checkOutAt === '') {
            $this->resetPreview();
            return;
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $checkInAt) || ! preg_match('/^\d{2}:\d{2}$/', $checkOutAt)) {
            $this->resetPreview();
            return;
        }

        try {
            $computed = app(AttendanceManualMetricsResolverService::class)->resolve($tabelNo !== '' ? $tabelNo : null, $date, $this->form);
        } catch (\Throwable) {
            $this->resetPreview();
            return;
        }

        $this->form['worked_minutes'] = (int) $computed['worked_minutes'];
        $this->form['overtime_minutes'] = (int) $computed['overtime_minutes'];
        $this->form['late_minutes'] = (int) $computed['late_minutes'];
        $this->form['early_leave_minutes'] = (int) $computed['early_leave_minutes'];
        $this->preview = [
            'planned_minutes' => (int) ($computed['planned_minutes'] ?? 0),
            'worked_minutes' => (int) $computed['worked_minutes'],
            'late_minutes' => (int) $computed['late_minutes'],
            'early_leave_minutes' => (int) $computed['early_leave_minutes'],
            'overtime_minutes' => (int) $computed['overtime_minutes'],
            'baseline_source' => (string) ($computed['baseline_source'] ?? 'none'),
            'baseline_label' => $computed['baseline_label'] ?? null,
        ];
        $this->autoCalculatedPreview = (bool) ($computed['auto_calculated'] ?? false);
    }

    private function resetPreview(): void
    {
        $this->preview = [
            'planned_minutes' => 0,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'baseline_source' => 'none',
            'baseline_label' => null,
        ];
    }

    public function approvalStatusLabel(string $status): string
    {
        $normalizedStatus = str($status)->lower()->toString();
        $label = __('attendance::manual_entries.statuses.'.$normalizedStatus);

        return $label === 'attendance::manual_entries.statuses.'.$normalizedStatus
            ? str($normalizedStatus)->replace('_', ' ')->headline()->toString()
            : $label;
    }
}
