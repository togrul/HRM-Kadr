<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceCalendar;
use App\Models\Structure;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceCalendarManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CalendarRegimes extends Component
{
    use WithPagination;

    public int $year;

    public int $month;

    public bool $canManage = false;

    public ?int $editingId = null;

    public int $perPage = 20;

    /**
     * @var array<string,mixed>
     */
    public array $form = [
        'date' => '',
        'day_type' => 'workday',
        'name' => '',
        'is_paid' => true,
        'scope_type' => 'global',
        'scope_id' => null,
    ];

    protected function rules(): array
    {
        return [
            'form.date' => ['required', 'date'],
            'form.day_type' => ['required', Rule::in(['workday', 'weekend', 'holiday'])],
            'form.name' => ['nullable', 'string', 'max:255'],
            'form.is_paid' => ['boolean'],
            'form.scope_type' => ['required', Rule::in(['global', 'structure'])],
            'form.scope_id' => ['nullable', 'integer', 'required_if:form.scope_type,structure', Rule::exists('structures', 'id')],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.date' => __('attendance::calendar_regimes.fields.date'),
            'form.day_type' => __('attendance::calendar_regimes.fields.day_type'),
            'form.name' => __('attendance::calendar_regimes.fields.name'),
            'form.scope_type' => __('attendance::calendar_regimes.fields.scope_type'),
            'form.scope_id' => __('attendance::calendar_regimes.fields.structure'),
        ];
    }

    public function mount(AttendanceAuthorizationService $authorization, ?int $year = null, ?int $month = null): void
    {
        $authorization->authorize('attendance.calendars.manage');
        $this->canManage = $authorization->can('attendance.calendars.manage');
        $this->year = $year ?: (int) now()->year;
        $this->month = $month ?: (int) now()->month;
        $this->resetForm();
    }

    public function save(AttendanceCalendarManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate();

        $calendar = $this->editingId
            ? AttendanceCalendar::query()->findOrFail($this->editingId)
            : null;

        $service->upsert($this->form, (int) Auth::id(), $calendar);

        $this->dispatch('notify', type: 'success', message: __('attendance::calendar_regimes.messages.saved'));
        $this->resetForm();
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $calendar = AttendanceCalendar::query()->findOrFail($id);

        $this->editingId = (int) $calendar->id;
        $this->form = [
            'date' => $calendar->date?->toDateString() ?? now()->toDateString(),
            'day_type' => (string) $calendar->day_type,
            'name' => (string) ($calendar->name ?? ''),
            'is_paid' => (bool) $calendar->is_paid,
            'scope_type' => (string) $calendar->scope_type,
            'scope_id' => $calendar->scope_id ? (int) $calendar->scope_id : null,
        ];
    }

    public function remove(int $id, AttendanceCalendarManagementService $service): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $service->delete(AttendanceCalendar::query()->findOrFail($id), (int) Auth::id());

        $this->dispatch('notify', type: 'success', message: __('attendance::calendar_regimes.messages.deleted'));

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $structures = Structure::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $structureNames = $structures
            ->pluck('name', 'id')
            ->all();

        $calendars = AttendanceCalendar::query()
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->orderByDesc('date')
            ->orderBy('scope_type')
            ->paginate($this->perPage);

        return view('attendance::livewire.attendance.calendar-regimes', [
            'structures' => $structures,
            'structureNames' => $structureNames,
            'calendars' => $calendars,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'date' => now()->setDate($this->year, $this->month, 1)->toDateString(),
            'day_type' => 'workday',
            'name' => '',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ];
    }
}
