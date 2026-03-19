<?php

namespace App\Modules\Leaves\Livewire\Concerns;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait InteractsWithLeaveForm
{
    use DropdownConstructTrait;

    public string $personnelName = '';
    public string $assignedSearch = '';

    public function updatedLeave($value, $name): void
    {
        if (str_ends_with((string) $name, 'leave_type_id')) {
            $this->syncSelectedLeaveTypeMeta();
        }

        $this->recalculateLeaveDuration();
    }

    public function selectPersonnel(string $tabelNo, string $fullname, string $key, ?int $personnelId = null): void
    {
        if ($key === 'tabel_no') {
            $this->leave->tabel_no = [
                'tabel_no' => $tabelNo,
                'fullname' => $fullname,
            ];

            $this->reset('personnelName', 'assignedSearch');

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = [
                'id' => $personnelId,
                'fullname' => $fullname,
            ];

            $this->reset('personnelName', 'assignedSearch');
        }
    }

    public function removePersonnel(string $key): void
    {
        if ($key === 'tabel_no') {
            $this->leave->tabel_no = null;

            $this->reset('personnelName');

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = null;

            $this->reset('assignedSearch');
        }
    }

    #[Computed]
    public function applicantPersonnelList()
    {
        return $this->searchPersonnelOptions($this->personnelName);
    }

    #[Computed]
    public function assignedPersonnelList()
    {
        return $this->searchPersonnelOptions($this->assignedSearch);
    }

    #[Computed(cache: true)]
    public function leaveTypeMetaMap(): array
    {
        return LeaveType::query()
            ->select('id', 'name', 'attendance_code', 'max_days', 'requires_document')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (LeaveType $leaveType) => [
                (int) $leaveType->id => [
                    'id' => (int) $leaveType->id,
                    'name' => (string) $leaveType->name,
                    'attendance_code' => trim((string) ($leaveType->attendance_code ?? '')),
                    'max_days' => max(0, (int) $leaveType->max_days),
                    'requires_document' => (bool) $leaveType->requires_document,
                ],
            ])
            ->all();
    }

    #[Computed(cache: true)]
    public function leaveTypes(): array
    {
        return collect($this->leaveTypeMetaMap)
            ->map(fn (array $meta) => [
                'id' => (int) $meta['id'],
                'label' => (string) $meta['name'],
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    #[Computed(cache: true)]
    public function durationUnits(): array
    {
        return collect([
            ['id' => 'day', 'label' => __('leaves::common.labels.duration_units.day')],
            ['id' => 'half_day', 'label' => __('leaves::common.labels.duration_units.half_day')],
            ['id' => 'hour', 'label' => __('leaves::common.labels.duration_units.hour')],
        ])->all();
    }

    #[Computed(cache: true)]
    public function partialDayParts(): array
    {
        return collect([
            ['id' => 'first_half', 'label' => __('leaves::common.labels.partial_day_parts.first_half')],
            ['id' => 'second_half', 'label' => __('leaves::common.labels.partial_day_parts.second_half')],
        ])->all();
    }

    #[Computed]
    public function selectedLeaveTypeMeta(): ?array
    {
        $leaveTypeId = $this->leave->leave_type_id;

        if (! $leaveTypeId) {
            return null;
        }

        return $this->leaveTypeMetaMap[(int) $leaveTypeId] ?? null;
    }

    #[Computed]
    public function leaveDurationNotice(): ?array
    {
        $meta = $this->selectedLeaveTypeMeta;
        $totalDays = (int) ($this->leave->total_days ?? 0);

        if (! $meta || $totalDays <= 0) {
            return null;
        }

        $maxDays = (int) data_get($meta, 'max_days', 0);

        if ($maxDays <= 0 || $totalDays <= $maxDays) {
            return null;
        }

        return [
            'type_name' => (string) data_get($meta, 'name', ''),
            'max_days' => $maxDays,
            'selected_days' => $totalDays,
        ];
    }

    #[Computed]
    public function leaveDurationSummary(): ?string
    {
        $durationUnit = in_array($this->leave->duration_unit, ['day', 'half_day', 'hour'], true)
            ? $this->leave->duration_unit
            : 'day';

        if ($durationUnit === 'hour') {
            $minutes = (int) ($this->leave->total_minutes ?? 0);

            if ($minutes <= 0) {
                return null;
            }

            return __('leaves::common.labels.duration_summary_hour', ['hours' => number_format($minutes / 60, 1)]);
        }

        if ($durationUnit === 'half_day') {
            return __('leaves::common.labels.duration_summary_half_day');
        }

        $days = (int) ($this->leave->total_days ?? 0);

        return $days > 0
            ? __('leaves::common.labels.duration_summary_day', ['days' => $days])
            : null;
    }

    #[Computed(cache: true)]
    public function statuses(): array
    {
        $selected = $this->leave->status_id;

        $base = OrderStatus::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: '',
            selectedId: $selected,
            limit: 50
        );
    }

    protected function recalculateLeaveDuration(): void
    {
        if (! $this->leave->starts_at) {
            $this->leave->total_days = null;
            $this->leave->total_minutes = null;

            return;
        }

        $durationUnit = in_array($this->leave->duration_unit, ['day', 'half_day', 'hour'], true)
            ? $this->leave->duration_unit
            : 'day';

        if ($durationUnit === 'day') {
            $start = Carbon::parse($this->leave->starts_at);
            $end = Carbon::parse($this->leave->ends_at ?: $this->leave->starts_at);

            $this->leave->total_days = $start->diffInDays($end) + 1;
            $this->leave->total_minutes = null;

            return;
        }

        $this->leave->ends_at = $this->leave->starts_at;
        $this->leave->total_days = 1;

        if ($durationUnit === 'half_day') {
            $this->leave->total_minutes = null;

            return;
        }

        if ($this->leave->starts_time && $this->leave->ends_time) {
            $start = Carbon::createFromFormat('H:i', $this->leave->starts_time);
            $end = Carbon::createFromFormat('H:i', $this->leave->ends_time);
            $this->leave->total_minutes = $end->greaterThan($start)
                ? $start->diffInMinutes($end)
                : null;

            return;
        }

        $this->leave->total_minutes = null;
    }

    protected function searchPersonnelOptions(string $term)
    {
        if (mb_strlen(trim($term)) <= 2) {
            return collect();
        }

        return Personnel::query()
            ->nameLike($term)
            ->active()
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();
    }

    protected function syncSelectedLeaveTypeMeta(): void
    {
        $this->leave->syncLeaveTypeMeta($this->selectedLeaveTypeMeta);
    }
}
