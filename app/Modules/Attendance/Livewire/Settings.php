<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceShift;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Settings extends Component
{
    public bool $canManage = false;

    /**
     * @var array<string,mixed>
     */
    public array $form = [
        'timezone' => 'Asia/Baku',
        'default_shift_id' => null,
        'late_grace_minutes' => 0,
        'early_leave_grace_minutes' => 0,
        'rounding_policy' => 'none',
        'rounding_step_minutes' => 5,
        'overtime_policy' => 'by_approval',
    ];

    protected function rules(): array
    {
        return [
            'form.timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'form.default_shift_id' => ['nullable', 'integer', Rule::exists('attendance_shifts', 'id')->whereNull('deleted_at')],
            'form.late_grace_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'form.early_leave_grace_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'form.rounding_policy' => ['required', Rule::in(['none', 'floor', 'ceil', 'nearest'])],
            'form.rounding_step_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'form.overtime_policy' => ['required', Rule::in(['by_approval', 'none', 'all_worked', 'after_shift'])],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.timezone' => __('attendance::settings.fields.timezone'),
            'form.default_shift_id' => __('attendance::settings.fields.default_shift'),
            'form.late_grace_minutes' => __('attendance::settings.fields.late_grace'),
            'form.early_leave_grace_minutes' => __('attendance::settings.fields.early_grace'),
            'form.rounding_policy' => __('attendance::settings.fields.rounding_policy'),
            'form.rounding_step_minutes' => __('attendance::settings.fields.rounding_step'),
            'form.overtime_policy' => __('attendance::settings.fields.overtime_policy'),
        ];
    }

    public function mount(
        AttendanceAuthorizationService $authorization,
        AttendanceSettingsService $settingsService
    ): void {
        $authorization->authorize('attendance.settings.manage');
        $this->canManage = $authorization->can('attendance.settings.manage');

        $this->loadForm($settingsService);
    }

    public function save(AttendanceSettingsService $settingsService): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $this->validate();
        $settingsService->updateGlobal($this->form, (int) Auth::id());

        $this->dispatch('notify', type: 'success', message: __('attendance::settings.messages.saved'));
    }

    public function render()
    {
        $shifts = AttendanceShift::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'start_time', 'end_time', 'break_minutes']);

        return view('attendance::livewire.attendance.settings', [
            'shifts' => $shifts,
            'currentDefaultShift' => $shifts->firstWhere('id', (int) ($this->form['default_shift_id'] ?? 0)),
        ]);
    }

    private function loadForm(AttendanceSettingsService $settingsService): void
    {
        $setting = $settingsService->getGlobal();

        $this->form = [
            'timezone' => (string) $setting->timezone,
            'default_shift_id' => $setting->default_shift_id ? (int) $setting->default_shift_id : null,
            'late_grace_minutes' => (int) $setting->late_grace_minutes,
            'early_leave_grace_minutes' => (int) $setting->early_leave_grace_minutes,
            'rounding_policy' => (string) $setting->rounding_policy,
            'rounding_step_minutes' => (int) $setting->rounding_step_minutes,
            'overtime_policy' => (string) $setting->overtime_policy,
        ];
    }
}
