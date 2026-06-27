<?php

namespace App\Modules\Leaves\Livewire\Concerns;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait InteractsWithLeaveForm
{
    use DropdownConstructTrait;

    public string $personnelName = '';

    public string $assignedSearch = '';

    protected ?ApprovalRouteResolver $approvalRouteResolver = null;

    protected ?array $assignmentPreviewSnapshot = null;

    protected ?string $assignmentPreviewKey = null;

    protected ?Personnel $selectedApplicantPersonnelSnapshot = null;

    protected ?string $selectedApplicantPersonnelKey = null;

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

            $this->resetAssignmentPreviewState();
            $this->reset('personnelName', 'assignedSearch');
            $this->syncAutomaticAssignment();

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = [
                'id' => $personnelId,
                'fullname' => $fullname,
            ];
            $this->leave->approval_route_source = 'manual_assignment';

            $this->reset('personnelName', 'assignedSearch');
        }
    }

    public function setAssignmentMode(string $mode): void
    {
        $targetMode = $mode === 'manual' ? 'manual' : 'auto';

        if ($this->leave->assignment_mode === $targetMode) {
            return;
        }

        $this->leave->assignment_mode = $targetMode;

        if ($this->leave->assignment_mode === 'auto') {
            $this->reset('assignedSearch');
            $this->resetAssignmentPreviewState();
            $this->syncAutomaticAssignment();

            return;
        }

        if (! data_get($this->leave->assigned_to, 'id') && data_get($this->assignmentPreview, 'approver.id')) {
            $this->leave->assigned_to = [
                'id' => (int) data_get($this->assignmentPreview, 'approver.id'),
                'fullname' => (string) data_get($this->assignmentPreview, 'approver.fullname'),
            ];
        }

        $this->leave->approval_route_source = 'manual_assignment';
        $this->leave->fallback_approver_personnel_id = null;
    }

    public function removePersonnel(string $key): void
    {
        if ($key === 'tabel_no') {
            $this->leave->tabel_no = null;
            $this->leave->assigned_to = null;
            $this->leave->fallback_approver_personnel_id = null;
            $this->leave->approval_route_source = null;
            $this->leave->hr_always_included = true;

            $this->resetAssignmentPreviewState();
            $this->reset('personnelName');

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = null;
            $this->leave->approval_route_source = $this->leave->assignment_mode === 'manual'
                ? 'manual_assignment'
                : $this->leave->approval_route_source;

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

    #[Computed]
    public function selectedApplicantPersonnel(): ?Personnel
    {
        $tabelNo = data_get($this->leave->tabel_no, 'tabel_no');

        if (! $tabelNo) {
            $this->selectedApplicantPersonnelKey = null;
            $this->selectedApplicantPersonnelSnapshot = null;

            return null;
        }

        if ($this->selectedApplicantPersonnelKey === $tabelNo && $this->selectedApplicantPersonnelSnapshot !== null) {
            return $this->selectedApplicantPersonnelSnapshot;
        }

        $this->selectedApplicantPersonnelKey = $tabelNo;

        return $this->selectedApplicantPersonnelSnapshot = Personnel::query()
            ->active()
            ->where('tabel_no', $tabelNo)
            ->with([
                'position:id,name,approval_rank,is_approval_target',
            ])
            ->first([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'position_id',
                'structure_id',
            ]);
    }

    #[Computed]
    public function assignmentPreview(): array
    {
        $cacheKey = data_get($this->leave->tabel_no, 'tabel_no', 'none');

        if ($this->assignmentPreviewSnapshot !== null && $this->assignmentPreviewKey === $cacheKey) {
            return $this->assignmentPreviewSnapshot;
        }

        $this->assignmentPreviewKey = $cacheKey;

        return $this->assignmentPreviewSnapshot = $this->buildAssignmentPreview();
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
        $cacheKey = 'leaves:form-statuses:'.app()->getLocale();

        $options = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn (): array => OrderStatus::query()
                ->select('id', DB::raw('name as label'))
                ->orderBy('id')
                ->get()
                ->map(fn (OrderStatus $status): array => [
                    'id' => (int) $status->id,
                    'label' => (string) $status->label,
                ])
                ->values()
                ->all()
        );

        if (! $selected) {
            return $options;
        }

        $hasSelected = collect($options)->contains(fn (array $option): bool => (int) $option['id'] === (int) $selected);

        if ($hasSelected) {
            return $options;
        }

        $selectedOption = OrderStatus::query()
            ->select('id', DB::raw('name as label'))
            ->find($selected);

        if (! $selectedOption) {
            return $options;
        }

        return collect($options)
            ->prepend([
                'id' => (int) $selectedOption->id,
                'label' => (string) $selectedOption->label,
            ])
            ->unique('id')
            ->values()
            ->all();
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

    protected function syncAutomaticAssignment(): void
    {
        if ($this->leave->assignment_mode !== 'auto') {
            return;
        }

        $preview = $this->assignmentPreview;
        $route = $preview['route'] ?? null;
        $approver = $preview['approver'] ?? $this->emptyPersonnelCard();

        $this->leave->assigned_to = ($route['approver_personnel_id'] ?? null) && ($approver['id'] ?? null)
            ? [
                'id' => (int) $approver['id'],
                'fullname' => (string) $approver['fullname'],
            ]
            : null;
        $this->leave->fallback_approver_personnel_id = $route['fallback_approver_personnel_id'] ?? null;
        $this->leave->approval_route_source = $route['approval_route_source'] ?? null;
        $this->leave->hr_always_included = (bool) ($route['hr_always_included'] ?? true);
    }

    protected function initializeAssignmentMode(?Leave $record = null): void
    {
        $this->resetAssignmentPreviewState();

        if ($record?->approval_route_source === 'manual_assignment') {
            $this->leave->assignment_mode = 'manual';
            $this->leave->approval_route_source = 'manual_assignment';
            $this->leave->fallback_approver_personnel_id = null;

            return;
        }

        $livePreview = $this->assignmentPreview;
        $liveRoute = $livePreview['route'] ?? null;
        $liveApproverId = data_get($livePreview, 'approver.id');

        if ($record && $liveRoute && $liveApproverId) {
            $this->leave->assignment_mode = 'auto';
            $this->syncAutomaticAssignment();

            return;
        }

        if ($record && data_get($this->leave->assigned_to, 'id')) {
            $this->leave->assignment_mode = 'auto';
            $this->leave->approval_route_source = $record->approval_route_source;
            $this->leave->fallback_approver_personnel_id = $record->fallback_approver_personnel_id
                ? (int) $record->fallback_approver_personnel_id
                : null;
            $this->leave->hr_always_included = (bool) ($record->hr_always_included ?? true);
            $this->seedAssignmentPreviewFromRecord($record);

            return;
        }

        $route = $liveRoute;
        $autoApproverId = $route['approver_personnel_id'] ?? null;
        $currentAssignedId = data_get($this->leave->assigned_to, 'id');

        $shouldUseManual = $currentAssignedId && $autoApproverId && (int) $currentAssignedId !== (int) $autoApproverId;

        if ($shouldUseManual) {
            $this->leave->assignment_mode = 'manual';
            $this->leave->approval_route_source = 'manual_assignment';
            $this->leave->fallback_approver_personnel_id = $route['fallback_approver_personnel_id'] ?? null;
            $this->leave->hr_always_included = (bool) ($route['hr_always_included'] ?? true);

            return;
        }

        if ($record && ! $autoApproverId && $currentAssignedId) {
            $this->leave->assignment_mode = 'auto';
            $this->leave->approval_route_source = $record->approval_route_source;
            $this->leave->fallback_approver_personnel_id = $record->fallback_approver_personnel_id
                ? (int) $record->fallback_approver_personnel_id
                : null;
            $this->leave->hr_always_included = (bool) ($record->hr_always_included ?? true);
            $this->seedAssignmentPreviewFromRecord($record);

            return;
        }

        $this->leave->assignment_mode = 'auto';
        $this->syncAutomaticAssignment();
    }

    protected function syncAssignmentForPersistence(): void
    {
        if ($this->leave->assignment_mode === 'manual') {
            $this->leave->approval_route_source = 'manual_assignment';
            $this->leave->fallback_approver_personnel_id = null;

            return;
        }

        $this->syncAutomaticAssignment();
    }

    protected function rehydrateAssignmentModeAfterSave(Leave $record, ?array $currentPreview = null): void
    {
        $this->resetAssignmentPreviewState();

        if ($record->approval_route_source === 'manual_assignment') {
            $this->leave->assignment_mode = 'manual';
            $this->leave->approval_route_source = 'manual_assignment';
            $this->leave->fallback_approver_personnel_id = null;

            return;
        }

        $this->leave->assignment_mode = 'auto';
        $this->leave->approval_route_source = $record->approval_route_source;
        $this->leave->fallback_approver_personnel_id = $record->fallback_approver_personnel_id
            ? (int) $record->fallback_approver_personnel_id
            : null;
        $this->leave->hr_always_included = (bool) ($record->hr_always_included ?? true);

        if ($currentPreview && data_get($currentPreview, 'route.approver_personnel_id')) {
            $preview = $currentPreview;
            $preview['route'] = [
                'approver_personnel_id' => $record->assigned_to ? (int) $record->assigned_to : null,
                'fallback_approver_personnel_id' => $record->fallback_approver_personnel_id ? (int) $record->fallback_approver_personnel_id : null,
                'approval_route_source' => $record->approval_route_source,
                'hr_always_included' => (bool) ($record->hr_always_included ?? true),
            ];

            $this->assignmentPreviewKey = data_get($this->leave->tabel_no, 'tabel_no', 'none');
            $this->assignmentPreviewSnapshot = $preview;

            return;
        }

        $this->seedAssignmentPreviewFromRecord($record);
    }

    private function findPersonnelCardById(?int $personnelId, array $chain): array
    {
        if (! $personnelId) {
            return $this->emptyPersonnelCard();
        }

        foreach ($chain as $row) {
            if ((int) ($row['id'] ?? 0) === (int) $personnelId) {
                return $row;
            }
        }

        return $this->emptyPersonnelCard();
    }

    private function emptyPersonnelCard(): array
    {
        return [
            'id' => null,
            'fullname' => __('leaves::common.empty.not_assigned'),
            'position' => '—',
            'structure' => '—',
        ];
    }

    private function seedAssignmentPreviewFromRecord(Leave $record): void
    {
        $relationCards = [];

        if ($record->relationLoaded('assigned') && $record->assigned) {
            $relationCards[(int) $record->assigned->id] = $this->approvalRouteResolver()->personnelPreviewCard($record->assigned);
        }

        if ($record->relationLoaded('fallbackApprover') && $record->fallbackApprover) {
            $relationCards[(int) $record->fallbackApprover->id] = $this->approvalRouteResolver()->personnelPreviewCard($record->fallbackApprover);
        }

        $ids = collect([
            $record->assigned_to,
            $record->fallback_approver_personnel_id,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty() && $relationCards === []) {
            return;
        }

        $missingIds = $ids
            ->reject(fn (int $id) => array_key_exists($id, $relationCards))
            ->values();

        $cards = $relationCards;

        if ($missingIds->isNotEmpty()) {
            $cards += Personnel::query()
                ->whereIn('id', $missingIds->all())
                ->with(['position:id,name,approval_rank,is_approval_target'])
                ->get(['id', 'surname', 'name', 'patronymic', 'position_id', 'structure_id'])
                ->mapWithKeys(fn (Personnel $personnel) => [
                    (int) $personnel->id => $this->approvalRouteResolver()->personnelPreviewCard($personnel),
                ])
                ->all();
        }

        $approver = $cards[(int) $record->assigned_to] ?? $this->previewCardFromSelectedAssignee();
        $fallback = $record->fallback_approver_personnel_id
            ? ($cards[(int) $record->fallback_approver_personnel_id] ?? $this->emptyPersonnelCard())
            : $this->emptyPersonnelCard();

        $chain = array_values(array_filter([
            ($approver['id'] ?? null) ? $approver : null,
            ($fallback['id'] ?? null) ? $fallback : null,
        ]));

        $this->assignmentPreviewKey = data_get($this->leave->tabel_no, 'tabel_no', 'none');
        $this->assignmentPreviewSnapshot = [
            'route' => [
                'approver_personnel_id' => $record->assigned_to ? (int) $record->assigned_to : null,
                'fallback_approver_personnel_id' => $record->fallback_approver_personnel_id ? (int) $record->fallback_approver_personnel_id : null,
                'approval_route_source' => $record->approval_route_source,
                'hr_always_included' => (bool) ($record->hr_always_included ?? true),
            ],
            'chain' => $chain,
            'approver' => $approver,
            'fallback' => $fallback,
            'upper_candidate' => $chain[1] ?? $this->emptyPersonnelCard(),
            'upper_enabled' => (bool) ($fallback['id'] ?? null),
        ];
    }

    private function approvalRouteResolver(): ApprovalRouteResolver
    {
        return $this->approvalRouteResolver ??= app(ApprovalRouteResolver::class);
    }

    private function buildAssignmentPreview(): array
    {
        $personnel = $this->selectedApplicantPersonnel;

        if (! $personnel) {
            return [
                'route' => null,
                'chain' => [],
                'approver' => $this->emptyPersonnelCard(),
                'fallback' => $this->emptyPersonnelCard(),
                'upper_candidate' => $this->emptyPersonnelCard(),
                'upper_enabled' => false,
            ];
        }

        $preview = $this->approvalRouteResolver()->preview($personnel, 'leave', 5);
        $route = $preview['route'] ?? null;
        $chain = $preview['chain'] ?? [];

        return [
            'route' => $route,
            'chain' => $chain,
            'approver' => $this->findPersonnelCardById($route['approver_personnel_id'] ?? null, $chain),
            'fallback' => $this->findPersonnelCardById($route['fallback_approver_personnel_id'] ?? null, $chain),
            'upper_candidate' => $chain[1] ?? $this->emptyPersonnelCard(),
            'upper_enabled' => ! empty($route['fallback_approver_personnel_id']),
        ];
    }

    private function resetAssignmentPreviewState(): void
    {
        $this->assignmentPreviewSnapshot = null;
        $this->assignmentPreviewKey = null;
        $this->selectedApplicantPersonnelSnapshot = null;
        $this->selectedApplicantPersonnelKey = null;
        unset($this->assignmentPreview, $this->selectedApplicantPersonnel);
    }

    private function previewCardFromSelectedAssignee(): array
    {
        $assignedId = (int) data_get($this->leave->assigned_to, 'id', 0);

        if ($assignedId <= 0) {
            return $this->emptyPersonnelCard();
        }

        return [
            'id' => $assignedId,
            'fullname' => (string) data_get($this->leave->assigned_to, 'fullname', __('leaves::common.empty.not_assigned')),
            'position' => '—',
            'structure' => '—',
        ];
    }
}
