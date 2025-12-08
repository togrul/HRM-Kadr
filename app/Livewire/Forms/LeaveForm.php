<?php

namespace App\Livewire\Forms;

use App\Models\Leave;
use App\Models\Personnel;
use Livewire\Form;
use Illuminate\Validation\Rule;

class LeaveForm extends Form
{
    public ?array $tabel_no = null;

    public ?int $leave_type_id = null;
    public ?int $status_id = null;
    public ?string $starts_at = null;
    public ?string $ends_at = null;
    public ?int $total_days = null;
    public ?string $reason = null;

    public ?array $assigned_to = null;

    public $document_path = null;

    public function rules(): array
    {
        return [
            'tabel_no.tabel_no' => ['required', 'string', 'exists:personnels,tabel_no'],
            'leave_type_id'     => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'status_id'         => ['required', 'integer', Rule::exists('order_statuses', 'id')],
            'starts_at'         => ['required', 'date'],
            'ends_at'           => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'tabel_no.tabel_no' => __('Personnel'),
            'leave_type_id'     => __('Leave type'),
            'starts_at'         => __('Start date'),
            'status_id'         => __('Status'),
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
                    ->select('tabel_no', 'surname', 'name', 'patronymic')
                    ->where('tabel_no', $leave->assigned_to)
                    ->first();
            }
        }

        $this->assigned_to = $assigned
            ? [
                'tabel_no' => $assigned->tabel_no,
                'fullname' => $assigned->fullname,
            ]
            : null;

        $this->leave_type_id = $leave->leave_type_id;
        $this->status_id     = $leave->status_id;
        $this->starts_at     = optional($leave->starts_at)->format('Y-m-d');
        $this->ends_at       = optional($leave->ends_at)->format('Y-m-d');
        $this->total_days    = $leave->total_days;
        $this->reason        = $leave->reason;
        $this->document_path = $leave->document_path;
    }

    public function toPayload(): array
    {
        return [
            'tabel_no'      => data_get($this->tabel_no, 'tabel_no'),
            'leave_type_id' => $this->leave_type_id !== null ? (int) $this->leave_type_id : null,
            'starts_at'     => $this->starts_at,
            'ends_at'       => $this->ends_at,
            'total_days'    => $this->total_days,
            'reason'        => $this->reason,
            'status_id'     => $this->status_id !== null ? (int) $this->status_id : null,
            'assigned_to'   => data_get($this->assigned_to, 'tabel_no'),
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
            'total_days'    => null,
            'reason'        => null,
            'assigned_to'   => null,
            'document_path' => null,
        ];
    }
}
