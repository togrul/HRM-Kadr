<?php

namespace App\Livewire\Forms;

use App\Models\Leave;
use App\Models\Personnel;
use Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LeaveForm extends Form
{
    public ?array $tabel_no = null;

    public ?int $leave_type_id = null;
    public ?int $status_id = null;
    public ?string $starts_at = null;
    public ?string $ends_at = null;
    public string $duration_unit = 'day';
    public ?string $partial_day_part = null;
    public ?string $starts_time = null;
    public ?string $ends_time = null;
    public ?int $total_days = null;
    public ?int $total_minutes = null;
    public ?string $reason = null;

    public ?array $assigned_to = null;
    public ?array $leave_type_meta = null;
    public string $assignment_mode = 'auto';
    public ?int $fallback_approver_personnel_id = null;
    public ?string $approval_route_source = null;
    public bool $hr_always_included = true;

    public $document_path = null;

    public function rules(): array
    {
        $requiresDocument = (bool) data_get($this->leave_type_meta, 'requires_document', false);

        return [
            'tabel_no.tabel_no' => ['required', 'string', 'exists:personnels,tabel_no'],
            'leave_type_id'     => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'status_id'         => ['required', 'integer', Rule::exists('order_statuses', 'id')],
            'starts_at'         => ['required', 'date'],
            'duration_unit'     => ['required', Rule::in(['day', 'half_day', 'hour'])],
            'ends_at'           => [Rule::requiredIf($this->duration_unit === 'day'), 'nullable', 'date', 'after_or_equal:starts_at'],
            'partial_day_part'  => [Rule::requiredIf($this->duration_unit === 'half_day'), 'nullable', Rule::in(['first_half', 'second_half'])],
            'starts_time'       => [Rule::requiredIf($this->duration_unit === 'hour'), 'nullable', 'date_format:H:i'],
            'ends_time'         => [Rule::requiredIf($this->duration_unit === 'hour'), 'nullable', 'date_format:H:i', 'after:starts_time'],
            'assigned_to.id'    => ['nullable', 'integer', Rule::exists('personnels', 'id')],
            'document_path'     => [
                Rule::requiredIf($requiresDocument),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (is_string($value) || $value instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $fail(__('validation.file', ['attribute' => __('leaves::common.labels.file')]));
                },
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'tabel_no.tabel_no' => __('leaves::common.labels.personnel'),
            'leave_type_id'     => __('leaves::common.labels.leave_type'),
            'starts_at'         => __('leaves::common.labels.start_date'),
            'ends_at'           => __('leaves::common.labels.end_date'),
            'duration_unit'     => __('leaves::common.labels.duration_unit'),
            'partial_day_part'  => __('leaves::common.labels.partial_day_part'),
            'starts_time'       => __('leaves::common.labels.start_time'),
            'ends_time'         => __('leaves::common.labels.end_time'),
            'status_id'         => __('leaves::common.labels.status'),
            'document_path'     => __('leaves::common.labels.file'),
        ];
    }

    public function resetForm(): void
    {
        $this->fill($this->defaults());
    }

    public function fillFromModel(Leave $leave): void
    {
        $this->fill($this->defaults());

        $personnel = null;
        if ($leave->relationLoaded('personnel')) {
            $personnel = $leave->personnel;
        } else {
            $personnel = $leave->personnel()->select('tabel_no', 'surname', 'name', 'patronymic')->first();
        }

        $this->tabel_no = $personnel
            ? [
                'tabel_no' => $personnel->tabel_no,
                'fullname' => $personnel->fullname,
            ]
            : null;

        $assigned = null;
        if ($leave->assigned_to) {
            if ($leave->relationLoaded('assigned') && $leave->assigned) {
                $assigned = $leave->assigned;
            } else {
                $assigned = Personnel::query()
                    ->select('id', 'tabel_no', 'surname', 'name', 'patronymic')
                    ->where('id', $leave->assigned_to)
                    ->first();
            }
        }

        $this->assigned_to = $assigned
            ? [
                'id' => (int) $assigned->id,
                'fullname' => $assigned->fullname,
            ]
            : null;

        $this->leave_type_id = $leave->leave_type_id;
        $this->status_id     = $leave->status_id;
        $this->starts_at     = optional($leave->starts_at)->format('Y-m-d');
        $this->ends_at       = optional($leave->ends_at)->format('Y-m-d');
        $this->duration_unit = $leave->normalizedDurationUnit();
        $this->partial_day_part = $leave->partial_day_part;
        $this->starts_time = filled($leave->starts_time) ? substr((string) $leave->starts_time, 0, 5) : null;
        $this->ends_time = filled($leave->ends_time) ? substr((string) $leave->ends_time, 0, 5) : null;
        $this->total_days    = $leave->total_days;
        $this->total_minutes = $leave->total_minutes;
        $this->reason        = $leave->reason;
        $this->fallback_approver_personnel_id = $leave->fallback_approver_personnel_id ? (int) $leave->fallback_approver_personnel_id : null;
        $this->approval_route_source = $leave->approval_route_source;
        $this->hr_always_included = (bool) ($leave->hr_always_included ?? true);
        $this->assignment_mode = $leave->approval_route_source === 'manual_assignment' ? 'manual' : 'auto';
        $this->document_path = $leave->document_path;
    }

    public function syncLeaveTypeMeta(?array $meta): void
    {
        $this->leave_type_meta = $meta ? [
            'id' => (int) data_get($meta, 'id'),
            'name' => (string) data_get($meta, 'name', ''),
            'attendance_code' => trim((string) data_get($meta, 'attendance_code', '')),
            'max_days' => max(0, (int) data_get($meta, 'max_days', 0)),
            'requires_document' => (bool) data_get($meta, 'requires_document', false),
        ] : null;
    }

    public function toPayload(): array
    {
        $durationUnit = in_array($this->duration_unit, ['day', 'half_day', 'hour'], true)
            ? $this->duration_unit
            : 'day';
        $endsAt = $durationUnit === 'day'
            ? ($this->ends_at ?: $this->starts_at)
            : $this->starts_at;

        return [
            'tabel_no'      => data_get($this->tabel_no, 'tabel_no'),
            'leave_type_id' => $this->leave_type_id !== null ? (int) $this->leave_type_id : null,
            'starts_at'     => $this->starts_at,
            'ends_at'       => $endsAt,
            'duration_unit' => $durationUnit,
            'partial_day_part' => $durationUnit === 'half_day' ? $this->partial_day_part : null,
            'starts_time'   => $durationUnit === 'hour' ? $this->starts_time : null,
            'ends_time'     => $durationUnit === 'hour' ? $this->ends_time : null,
            'total_days'    => $this->total_days,
            'total_minutes' => $this->total_minutes,
            'reason'        => $this->reason,
            'status_id'     => $this->status_id !== null ? (int) $this->status_id : null,
            'assigned_to'   => data_get($this->assigned_to, 'id'),
            'fallback_approver_personnel_id' => $this->fallback_approver_personnel_id,
            'approval_route_source' => $this->approval_route_source,
            'hr_always_included' => $this->hr_always_included,
            'document_path' => is_string($this->document_path) ? $this->document_path : null,
        ];
    }

    private function defaults(): array
    {
        return [
            'tabel_no'      => null,
            'leave_type_id' => null,
            'status_id'     => null,
            'starts_at'     => null,
            'ends_at'       => null,
            'duration_unit' => 'day',
            'partial_day_part' => null,
            'starts_time'   => null,
            'ends_time'     => null,
            'total_days'    => null,
            'total_minutes' => null,
            'reason'        => null,
            'assigned_to'   => null,
            'leave_type_meta' => null,
            'assignment_mode' => 'auto',
            'fallback_approver_personnel_id' => null,
            'approval_route_source' => null,
            'hr_always_included' => true,
            'document_path' => null,
        ];
    }
}
