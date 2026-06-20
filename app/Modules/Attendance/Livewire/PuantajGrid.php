<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Modules\Attendance\Support\LeaveLegendPresenter;
use App\Services\StructurePathService;
use App\Support\Translations\ModuleTranslation;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PuantajGrid extends Component
{
    use NestedStructureTrait;
    use WithPagination;

    public int $year;

    public int $month;

    public string $search = '';

    public int $perPage = 20;

    public ?int $selectedStructureId = null;

    private ?LeaveLegendPresenter $leaveLegendPresenter = null;

    /**
     * Lazily resolve the (stateless) leave-legend presenter. Not a public Livewire
     * property — it carries no serializable state and is rebuilt per request.
     */
    private function leaveLegend(): LeaveLegendPresenter
    {
        return $this->leaveLegendPresenter ??= new LeaveLegendPresenter;
    }

    public function mount(int $year, int $month, AttendanceAuthorizationService $authorization): void
    {
        if (! $authorization->can('attendance.puantaj.view')) {
            abort(403);
        }

        $this->year = $year;
        $this->month = $month;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStructureId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $from = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $days = range(1, (int) $from->daysInMonth);
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];
        /** @var AttendancePuantajReadService $readService */
        $readService = app(AttendancePuantajReadService::class);
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        $personnels = $readService->paginatePersonnels(trim($this->search), $this->perPage, $structureIds, $from, $to);
        $tabelNos = $personnels->getCollection()->pluck('tabel_no')->filter()->values()->all();

        $ledgerByTabelAndDate = $readService->loadLedgerMap($tabelNos, $from, $to);
        $calendarDayTypeByDate = $readService->globalCalendarDayTypeByDate($from, $to);
        $calendarOverrides = $readService->calendarOverrides($from, $to, $structureIds);

        $rows = $personnels->getCollection()->map(function ($personnel) use (
            $days,
            $from,
            $ledgerByTabelAndDate,
            $structurePathService
        ): array {
            $rowCells = [];
            $totalWorkedMinutes = 0;
            $totalPresentDays = 0;

            foreach ($days as $day) {
                $date = $from->copy()->day($day)->toDateString();
                $ledger = $ledgerByTabelAndDate[$personnel->tabel_no][$date] ?? null;

                $rowCells[$day] = $this->buildCellData($ledger);

                $totalWorkedMinutes += (int) $rowCells[$day]['worked_minutes'];
                if ((int) $rowCells[$day]['worked_minutes'] > 0 || in_array($rowCells[$day]['status'], ['present', 'manual_present', 'holiday_worked', 'weekend_worked'], true)) {
                    $totalPresentDays++;
                }
            }

            return [
                'personnel' => $personnel,
                'structure_path' => $structurePathService->resolve((int) $personnel->structure_id),
                'cells' => $rowCells,
                'total_hours' => $this->formatHours($totalWorkedMinutes),
                'total_days' => $totalPresentDays,
            ];
        })->values();

        return view('attendance::livewire.attendance.puantaj-grid', [
            'days' => $days,
            'headers' => $this->buildHeaders($days, $from, $calendarOverrides),
            'rows' => $rows,
            'personnels' => $personnels,
            'calendarDayTypeByDate' => $calendarDayTypeByDate,
            'calendarOverrides' => $this->buildCalendarLegend($calendarOverrides),
            'statusLegend' => $this->buildStatusLegend(),
            'leaveLegend' => $this->buildLeaveLegend($rows->all()),
            'monthStart' => $from,
            'selectedStructureLabel' => $structureScopeRead->label($this->selectedStructureId),
        ]);
    }

    /**
     * @param  array<int,int>  $days
     * @param  array<int,array<string,mixed>>  $calendarOverrides
     * @return array<int,string|array<string,mixed>>
     */
    private function buildHeaders(array $days, Carbon $from, array $calendarOverrides): array
    {
        $headers = [__('attendance::puantaj.headers.personnel')];

        foreach ($days as $day) {
            $date = $from->copy()->day($day);
            $dateKey = $date->toDateString();
            $dayType = $this->resolveHeaderDayType($date, $calendarOverrides);

            $header = [
                'label' => (string) $day,
                'day' => $day,
                'is_day' => true,
                'day_type' => $dayType,
                'title' => __('attendance::puantaj.statuses.'.$dayType),
            ];

            if ($dayType === 'weekend') {
                $header['icon'] = 'icons.calendar-icon';
                $header['th_classes'] = '!bg-zinc-200/70 text-zinc-600';
                $header['content_classes'] = 'text-zinc-600';
            } elseif ($dayType === 'holiday') {
                $header['icon'] = 'icons.holiday-icon';
                $header['th_classes'] = '!bg-fuchsia-100/80 text-fuchsia-700';
                $header['content_classes'] = 'text-fuchsia-700';
            } elseif ($dayType === 'workday' && $this->hasExplicitWorkdayOverride($dateKey, $calendarOverrides)) {
                $header['icon'] = 'icons.calendar-icon';
                $header['th_classes'] = '!bg-emerald-100/80 text-emerald-700';
                $header['content_classes'] = 'text-emerald-700';
                $header['title'] = __('attendance::puantaj.headers.workday_override');
            }

            $headers[] = $header;
        }

        $headers[] = __('attendance::puantaj.headers.total_hours');
        $headers[] = __('attendance::puantaj.headers.total_days');

        return $headers;
    }

    /**
     * @param  array<string,mixed>|null  $ledger
     * @return array<string,mixed>
     */
    private function buildCellData(?array $ledger): array
    {
        $workedMinutes = (int) ($ledger['worked_minutes'] ?? 0);
        $status = (string) ($ledger['attendance_status'] ?? 'none');
        $absenceCode = strtoupper((string) ($ledger['absence_code'] ?? ''));
        $leaveTypeName = trim((string) ($ledger['leave_type_name'] ?? ''));
        $leaveTypeCode = strtoupper(trim((string) ($ledger['leave_type_code'] ?? '')));
        $leaveTypeId = is_numeric($ledger['leave_type_id'] ?? null) ? (int) $ledger['leave_type_id'] : null;
        $calendarDayType = (string) ($ledger['calendar_day_type'] ?? '');
        $durationUnit = (string) ($ledger['duration_unit'] ?? 'day');
        $legendIcon = $this->leaveLegend()->resolveLeaveLegendIcon($leaveTypeCode, $absenceCode);
        $partialDayPart = $ledger['partial_day_part'] ?? null;
        $startsTime = $ledger['starts_time'] ?? null;
        $endsTime = $ledger['ends_time'] ?? null;
        $totalMinutes = is_numeric($ledger['total_minutes'] ?? null) ? (int) $ledger['total_minutes'] : null;
        $coveredLeaveMinutes = (int) ($ledger['covered_leave_minutes'] ?? 0);
        $isPartialLeave = $leaveTypeName !== '' && in_array($durationUnit, ['half_day', 'hour'], true);
        $durationSummary = $isPartialLeave
            ? $this->buildLeaveDurationSummary($durationUnit, $totalMinutes)
            : '';
        $durationWindow = $isPartialLeave
            ? $this->buildLeaveDurationWindow($durationUnit, $partialDayPart, $startsTime, $endsTime)
            : '';
        $detailLines = $this->buildCellDetailLines(
            workedMinutes: $workedMinutes,
            status: $status,
            absenceCode: $absenceCode,
            leaveTypeName: $leaveTypeName,
            calendarDayType: $calendarDayType,
            durationSummary: $durationSummary,
            durationWindow: $durationWindow,
            coveredLeaveMinutes: $coveredLeaveMinutes
        );

        if ($status === 'none') {
            return [
                'display' => '',
                'status' => 'none',
                'worked_minutes' => 0,
                'title' => '',
                'detail_lines' => [],
                'cell_classes' => 'text-zinc-400 bg-white',
                'icon' => null,
                'icon_color' => 'text-zinc-400',
            ];
        }

        if ($workedMinutes > 0) {
            $tone = $this->leaveLegend()->resolveLeaveTone($leaveTypeId, $leaveTypeName, $absenceCode);
            $legendFamilyKey = $this->leaveLegend()->resolveLeaveLegendFamilyKey($leaveTypeId, $leaveTypeCode, $leaveTypeName, $absenceCode);
            $legendLabel = $this->leaveLegend()->buildLeaveLegendFamilyLabel($leaveTypeName, $absenceCode);

            return [
                'display' => $this->formatHours($workedMinutes),
                'status' => $status,
                'worked_minutes' => $workedMinutes,
                'title' => $this->joinCellDetailLines($detailLines),
                'detail_lines' => $detailLines,
                'cell_classes' => $this->resolveWorkedMinuteClasses($workedMinutes, $status, $isPartialLeave),
                'icon' => null,
                'icon_color' => 'text-zinc-500',
                'legend_key' => $isPartialLeave ? $legendFamilyKey : null,
                'legend_label' => $isPartialLeave ? $legendLabel : null,
                'legend_code' => $isPartialLeave && $legendIcon === null ? $this->leaveLegend()->resolveLeaveLegendCode($leaveTypeCode, $leaveTypeName, $absenceCode) : null,
                'legend_icon' => $isPartialLeave ? $legendIcon : null,
                'legend_icon_color' => $isPartialLeave && $legendIcon !== null ? $this->leaveLegend()->resolveLeaveToneIconColor($tone) : null,
                'legend_mode' => $isPartialLeave ? $this->leaveLegend()->resolveLeaveToneBadgeMode($tone) : null,
                'legend_code_classes' => $isPartialLeave ? $this->leaveLegend()->resolveLeaveToneCodeClasses($tone) : null,
                'legend_description' => $isPartialLeave ? __('attendance::puantaj.legend.leave_code_hint') : null,
            ];
        }

        if ($status === 'leave' || ($isPartialLeave && $status === 'absent')) {
            $legendCode = $this->leaveLegend()->resolveLeaveLegendCode($leaveTypeCode, $leaveTypeName, $absenceCode);
            $tone = $this->leaveLegend()->resolveLeaveTone($leaveTypeId, $leaveTypeName, $absenceCode);
            $legendFamilyKey = $this->leaveLegend()->resolveLeaveLegendFamilyKey($leaveTypeId, $leaveTypeCode, $leaveTypeName, $absenceCode);
            $legendLabel = $this->leaveLegend()->buildLeaveLegendFamilyLabel($leaveTypeName, $absenceCode);

            return [
                'display' => $legendIcon === null
                    ? ($legendCode !== '' ? $legendCode : __('attendance::puantaj.short_labels.leave'))
                    : '',
                'status' => $status,
                'worked_minutes' => 0,
                'title' => $this->joinCellDetailLines($detailLines),
                'detail_lines' => $detailLines,
                'cell_classes' => $this->leaveLegend()->resolveLeaveToneClasses($tone),
                'icon' => $legendIcon,
                'icon_color' => $legendIcon !== null ? $this->leaveLegend()->resolveLeaveToneIconColor($tone) : $this->leaveLegend()->resolveLeaveToneIconColor($tone),
                'legend_key' => $legendFamilyKey,
                'legend_label' => $legendLabel,
                'legend_code' => $legendIcon === null ? $legendCode : null,
                'legend_icon' => $legendIcon,
                'legend_icon_color' => $legendIcon !== null ? $this->leaveLegend()->resolveLeaveToneIconColor($tone) : null,
                'legend_mode' => $this->leaveLegend()->resolveLeaveToneBadgeMode($tone),
                'legend_code_classes' => $this->leaveLegend()->resolveLeaveToneCodeClasses($tone),
                'legend_description' => __('attendance::puantaj.legend.leave_code_hint'),
            ];
        }

        if ($status === 'vacation') {
            return [
                'display' => __('attendance::puantaj.short_labels.vacation'),
                'status' => $status,
                'worked_minutes' => 0,
                'title' => $this->joinCellDetailLines($detailLines),
                'detail_lines' => $detailLines,
                'cell_classes' => 'text-violet-700 bg-violet-50/80',
                'icon' => 'icons.vacation-icon',
                'icon_color' => 'text-violet-600',
            ];
        }

        if ($status === 'business_trip') {
            return [
                'display' => __('attendance::puantaj.short_labels.business_trip'),
                'status' => $status,
                'worked_minutes' => 0,
                'title' => $this->joinCellDetailLines($detailLines),
                'detail_lines' => $detailLines,
                'cell_classes' => 'text-sky-700 bg-sky-50/80',
                'icon' => 'icons.briefcase-icon',
                'icon_color' => 'text-sky-600',
            ];
        }

        if (in_array($status, ['holiday', 'weekend'], true)) {
            return [
                'display' => $status === 'holiday' ? '' : '-',
                'status' => $status,
                'worked_minutes' => 0,
                'title' => $this->joinCellDetailLines($detailLines),
                'detail_lines' => $detailLines,
                'cell_classes' => $status === 'holiday'
                    ? 'text-fuchsia-700 bg-fuchsia-50/80'
                    : 'text-zinc-400 bg-zinc-50/80',
                'icon' => $status === 'holiday' ? 'icons.calendar-icon' : null,
                'icon_color' => $status === 'holiday' ? 'text-fuchsia-600' : 'text-zinc-400',
            ];
        }

        return [
            'display' => $absenceCode !== '' ? $absenceCode : '0',
            'status' => $status,
            'worked_minutes' => 0,
            'title' => $this->joinCellDetailLines($detailLines),
            'detail_lines' => $detailLines,
            'cell_classes' => 'text-rose-600 bg-rose-50/70',
            'icon' => null,
            'icon_color' => 'text-rose-500',
        ];
    }

    private function buildCellDetailLines(
        int $workedMinutes,
        string $status,
        string $absenceCode,
        string $leaveTypeName = '',
        string $calendarDayType = '',
        string $durationSummary = '',
        string $durationWindow = '',
        int $coveredLeaveMinutes = 0
    ): array {
        $parts = [];
        $statusLabels = [
            'present' => __('attendance::puantaj.statuses.present'),
            'manual_present' => __('attendance::puantaj.statuses.manual_present'),
            'holiday_worked' => __('attendance::puantaj.statuses.holiday_worked'),
            'weekend_worked' => __('attendance::puantaj.statuses.weekend_worked'),
            'absent' => __('attendance::puantaj.statuses.absent'),
            'manual_absence' => __('attendance::puantaj.statuses.manual_absence'),
            'weekend' => __('attendance::puantaj.statuses.weekend'),
            'holiday' => __('attendance::puantaj.statuses.holiday'),
            'none' => __('attendance::puantaj.statuses.none'),
        ];

        if ($workedMinutes > 0) {
            $parts[] = __('attendance::puantaj.tooltips.worked', ['hours' => $this->formatHours($workedMinutes)]);
        }

        if ($status !== 'none') {
            $parts[] = __('attendance::puantaj.tooltips.status', ['status' => $statusLabels[$status] ?? $status]);
        }

        if ($leaveTypeName !== '') {
            $parts[] = __('attendance::puantaj.tooltips.leave_type', ['type' => $leaveTypeName]);
        }

        if ($durationSummary !== '') {
            $parts[] = __('attendance::puantaj.tooltips.duration', ['duration' => $durationSummary]);
        }

        if ($durationWindow !== '') {
            $parts[] = __('attendance::puantaj.tooltips.leave_window', ['window' => $durationWindow]);
        }

        if ($coveredLeaveMinutes > 0) {
            $parts[] = __('attendance::puantaj.tooltips.covered_leave', [
                'hours' => $this->formatHours($coveredLeaveMinutes),
            ]);
        }

        if ($absenceCode !== '') {
            $parts[] = __('attendance::puantaj.tooltips.absence', [
                'code' => $this->leaveLegend()->resolveAbsenceDisplayLabel($absenceCode),
            ]);
        }

        if ($calendarDayType !== '') {
            $parts[] = __('attendance::puantaj.tooltips.calendar', [
                'type' => __('attendance::puantaj.statuses.'.$calendarDayType),
            ]);
        }

        return $parts;
    }

    /**
     * @param  array<int,string>  $parts
     */
    private function joinCellDetailLines(array $parts): string
    {
        return implode(' | ', $parts);
    }

    private function resolveWorkedMinuteClasses(int $workedMinutes, string $status, bool $isPartialLeave = false): string
    {
        if ($isPartialLeave) {
            return 'text-sky-700 bg-sky-50/80 font-semibold';
        }

        if (in_array($status, ['holiday_worked', 'weekend_worked'], true)) {
            return 'text-emerald-700 bg-emerald-50/70';
        }

        $fullDayMinutes = 9 * 60;

        return match (true) {
            $workedMinutes > $fullDayMinutes => 'text-emerald-600 bg-emerald-50',
            $workedMinutes === $fullDayMinutes => 'text-zinc-900 bg-white',
            $workedMinutes > 0 => 'text-amber-500 bg-amber-50',
            default => 'text-zinc-900 bg-white',
        };
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildLeaveLegend(array $rows): array
    {
        return collect($rows)
            ->flatMap(fn (array $row) => array_values($row['cells'] ?? []))
            ->filter(fn (array $cell) => isset($cell['legend_key'], $cell['legend_label'], $cell['legend_mode']))
            ->unique('legend_key')
            ->map(fn (array $cell) => [
                'label' => (string) $cell['legend_label'],
                'code' => (string) ($cell['legend_code'] ?? ''),
                'icon' => $cell['legend_icon'] ?? null,
                'icon_color' => (string) ($cell['legend_icon_color'] ?? 'text-zinc-600'),
                'mode' => (string) $cell['legend_mode'],
                'code_classes' => (string) ($cell['legend_code_classes'] ?? 'border-zinc-200 bg-zinc-50 text-zinc-700'),
            ])
            ->sortBy(fn (array $item) => mb_strtolower($item['label']))
            ->values()
            ->all();
    }

    private function buildLeaveDurationSummary(string $durationUnit, ?int $totalMinutes): string
    {
        if ($durationUnit === 'hour' && $totalMinutes !== null && $totalMinutes > 0) {
            return __('leaves::common.labels.duration_summary_hour', [
                'hours' => number_format($totalMinutes / 60, 1),
            ]);
        }

        if ($durationUnit === 'half_day') {
            return __('leaves::common.labels.duration_summary_half_day');
        }

        return '';
    }

    private function buildLeaveDurationWindow(string $durationUnit, mixed $partialDayPart, mixed $startsTime, mixed $endsTime): string
    {
        if ($durationUnit === 'half_day' && is_string($partialDayPart) && $partialDayPart !== '') {
            return __('leaves::common.labels.partial_day_parts.'.$partialDayPart);
        }

        if ($durationUnit === 'hour' && is_string($startsTime) && is_string($endsTime) && $startsTime !== '' && $endsTime !== '') {
            return substr($startsTime, 0, 5).' - '.substr($endsTime, 0, 5);
        }

        return '';
    }

    /**
     * @param  array<int,array<string,mixed>>  $calendarOverrides
     */
    private function resolveHeaderDayType(Carbon $date, array $calendarOverrides): string
    {
        $dateKey = $date->toDateString();
        $structureOverride = collect($calendarOverrides)
            ->first(fn (array $item) => (string) ($item['date'] ?? '') === $dateKey && (string) ($item['scope_type'] ?? '') === 'structure');

        if (is_array($structureOverride) && isset($structureOverride['day_type'])) {
            return (string) $structureOverride['day_type'];
        }

        $globalOverride = collect($calendarOverrides)
            ->first(fn (array $item) => (string) ($item['date'] ?? '') === $dateKey && (string) ($item['scope_type'] ?? '') === 'global');

        if (is_array($globalOverride) && isset($globalOverride['day_type'])) {
            return (string) $globalOverride['day_type'];
        }

        return $date->isWeekend() ? 'weekend' : 'workday';
    }

    /**
     * @param  array<int,array<string,mixed>>  $calendarOverrides
     */
    private function hasExplicitWorkdayOverride(string $dateKey, array $calendarOverrides): bool
    {
        return collect($calendarOverrides)
            ->contains(fn (array $item) => (string) ($item['date'] ?? '') === $dateKey && (string) ($item['day_type'] ?? '') === 'workday');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildStatusLegend(): array
    {
        return [
            [
                'label' => __('attendance::puantaj.legend.items.full_day'),
                'mode' => 'green',
                'icon' => null,
                'description' => __('attendance::puantaj.legend.descriptions.full_day'),
            ],
            [
                'label' => __('attendance::puantaj.legend.items.partial_day'),
                'mode' => 'secondary',
                'icon' => null,
                'description' => __('attendance::puantaj.legend.descriptions.partial_day'),
            ],
            [
                'label' => __('attendance::puantaj.legend.items.absence'),
                'mode' => 'red',
                'icon' => null,
                'description' => __('attendance::puantaj.legend.descriptions.absence'),
            ],
            [
                'label' => __('attendance::puantaj.legend.items.weekend'),
                'mode' => 'secondary',
                'icon' => 'icons.calendar-icon',
                'description' => __('attendance::puantaj.legend.descriptions.weekend'),
            ],
            [
                'label' => __('attendance::puantaj.legend.items.holiday'),
                'mode' => 'purple',
                'icon' => 'icons.holiday-icon',
                'description' => __('attendance::puantaj.legend.descriptions.holiday'),
            ],
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $calendarOverrides
     * @return array<int,array<string,mixed>>
     */
    private function buildCalendarLegend(array $calendarOverrides): array
    {
        return collect($calendarOverrides)
            ->reject(fn (array $item) => (string) ($item['day_type'] ?? '') === 'weekend')
            ->map(function (array $item): array {
                $mode = match ($item['day_type']) {
                    'workday' => 'green',
                    'holiday' => 'red',
                    default => 'secondary',
                };

                $resolvedName = trim(ModuleTranslation::resolveStoredText((string) ($item['name'] ?? '')));
                $label = trim(implode(' - ', array_filter([
                    Carbon::parse((string) $item['date'])->format('d.m'),
                    $resolvedName !== '' ? $resolvedName : __('attendance::puantaj.statuses.'.$item['day_type']),
                ])));

                return [
                    'label' => $label,
                    'mode' => $mode,
                    'icon' => $item['day_type'] === 'holiday' ? 'icons.holiday-icon' : 'icons.calendar-icon',
                    'description' => __('attendance::puantaj.legend.calendar_description', [
                        'type' => __('attendance::puantaj.statuses.'.$item['day_type']),
                        'scope' => $item['scope_label'],
                        'paid' => $item['is_paid']
                            ? __('attendance::puantaj.calendar.paid')
                            : __('attendance::puantaj.calendar.unpaid'),
                    ]),
                ];
            })
            ->values()
            ->all();
    }

    private function formatHours(int $workedMinutes): string
    {
        return (string) round($workedMinutes / 60);
    }
}
