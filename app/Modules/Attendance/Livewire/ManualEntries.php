<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceShift;
use App\Models\Personnel;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceManualEntryService;
use App\Modules\Attendance\Application\Services\AttendanceManualMetricsResolverService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ManualEntries extends Component
{
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

    public function mount(AttendanceAuthorizationService $authorization, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->form['date'] = now()->toDateString();
        $this->canWrite = $authorization->can('attendance.manual.write');
        $this->canApprove = $authorization->can('attendance.manual.approve');

        if (! $this->canWrite && ! $this->canApprove) {
            abort(403);
        }
    }

    public function updatedQueueStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStructureId(): void
    {
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
            $message = collect($exception->errors())->flatten()->first() ?: __('Validation failed.');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('Manual attendance entry saved.'));

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
            $message = collect($exception->errors())->flatten()->first() ?: __('Validation failed.');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('Manual entry approved.'));
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
            $message = collect($exception->errors())->flatten()->first() ?: __('Validation failed.');
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        unset($this->rejectNotes[$entryId]);
        $this->dispatch('notify', type: 'success', message: __('Manual entry rejected.'));
    }

    public function getRecentEntriesProperty()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];

        return AttendanceManualEntry::query()
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
            ->paginate($this->perPage);
    }

    public function render()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);

        $availableShifts = AttendanceShift::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'start_time', 'end_time', 'break_minutes']);

        $personnelResults = collect();
        if (mb_strlen(trim($this->personnelSearch)) >= 2) {
            $term = trim($this->personnelSearch);
            $wildcard = '%'.$term.'%';

            $personnelResults = Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id')
                ->where('is_pending', 0)
                ->whereNull('leave_work_date')
                ->when($structureIds !== [], fn ($query) => $query->whereIn('structure_id', $structureIds))
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
                ->get();
        }

        $selectedShiftPreview = null;
        if ($this->form['shift_source_mode'] === 'explicit' && ! empty($this->form['explicit_shift_id'])) {
            $selectedShiftPreview = $availableShifts->firstWhere('id', (int) $this->form['explicit_shift_id']);
        }

        $selectedPersonnelRecord = null;
        if (! empty($this->form['tabel_no'])) {
            $selectedPersonnelRecord = Personnel::query()
                ->select('tabel_no', 'surname', 'name', 'patronymic', 'structure_id')
                ->with('structure:id,name,parent_id')
                ->where('tabel_no', $this->form['tabel_no'])
                ->first();

            if ($selectedPersonnelRecord && ! $this->selectedPersonnel) {
                $this->selectedPersonnel = [
                    'tabel_no' => (string) $selectedPersonnelRecord->tabel_no,
                    'fullname' => (string) $selectedPersonnelRecord->fullname,
                ];
            }
        }

        return view('attendance::livewire.attendance.manual-entries', [
            'recentEntries' => $this->recentEntries,
            'availableShifts' => $availableShifts,
            'selectedShiftPreview' => $selectedShiftPreview,
            'personnelResults' => $personnelResults,
            'selectedPersonnelRecord' => $selectedPersonnelRecord,
            'selectedStructureLabel' => $structureScopeRead->label($this->selectedStructureId),
        ]);
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
}
