<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceManualMetricsResolverService
{
    protected static bool $globalSettingLoaded = false;

    protected static ?AttendanceSetting $globalSetting = null;

    /**
     * @param  array<string,mixed>  $payload
     * @return array{
     *   shift:?AttendanceShift,
     *   planned_minutes:int,
     *   baseline_source:string,
     *   baseline_label:?string
     * }
     */
    public function resolveBaselineContext(?string $tabelNo, string $date, array $payload): array
    {
        $workDate = Carbon::parse($date)->startOfDay();
        $shiftSourceMode = (string) ($payload['shift_source_mode'] ?? 'auto');
        $explicitShiftId = is_numeric($payload['explicit_shift_id'] ?? null)
            ? (int) $payload['explicit_shift_id']
            : null;

        $setting = $this->globalActiveSetting();
        $defaultShift = $setting?->defaultShift;

        $explicitShift = null;
        if ($shiftSourceMode === 'explicit' && $explicitShiftId !== null) {
            $explicitShift = AttendanceShift::query()
                ->where('is_active', true)
                ->find($explicitShiftId);
        }

        $assignments = collect();
        if (filled($tabelNo)) {
            $assignments = AttendanceShiftAssignment::query()
                ->with('shift:id,name,start_time,end_time,break_minutes,is_night_shift,in_flex_before_minutes,in_flex_after_minutes,out_flex_before_minutes,out_flex_after_minutes')
                ->where('tabel_no', $tabelNo)
                ->where('is_active', true)
                ->where('effective_from', '<=', $workDate->toDateString())
                ->where(function ($query) use ($workDate): void {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $workDate->toDateString());
                })
                ->orderByDesc('effective_from')
                ->get();
        }

        [$shift, $baselineSource] = $this->resolveBaselineShift(
            assignmentsForTabel: $assignments,
            date: $workDate,
            defaultShift: $defaultShift,
            explicitShift: $explicitShift
        );

        $plannedMinutes = 0;
        if ($shift !== null) {
            $window = app(AttendanceShiftWindowService::class)->resolve($workDate, $shift);
            $plannedMinutes = max(0, $window['shift_start']->diffInMinutes($window['shift_end']) - (int) $shift->break_minutes);
        }

        return [
            'shift' => $shift,
            'planned_minutes' => $plannedMinutes,
            'baseline_source' => $baselineSource,
            'baseline_label' => $shift?->name,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{
     *   check_in_at:?string,
     *   check_out_at:?string,
     *   worked_minutes:int,
     *   overtime_minutes:int,
     *   late_minutes:int,
     *   early_leave_minutes:int,
     *   planned_minutes:int,
     *   baseline_source:string,
     *   baseline_label:?string,
     *   auto_calculated:bool
     * }
     */
    public function resolve(?string $tabelNo, string $date, array $payload): array
    {
        $checkInAt = $this->normalizeTimeValue($payload['check_in_at'] ?? null);
        $checkOutAt = $this->normalizeTimeValue($payload['check_out_at'] ?? null);
        $manualOverride = (bool) ($payload['manual_metric_override'] ?? false);

        if ($checkInAt === null || $checkOutAt === null) {
            return [
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'worked_minutes' => (int) ($payload['worked_minutes'] ?? 0),
                'overtime_minutes' => (int) ($payload['overtime_minutes'] ?? 0),
                'late_minutes' => (int) ($payload['late_minutes'] ?? 0),
                'early_leave_minutes' => (int) ($payload['early_leave_minutes'] ?? 0),
                'planned_minutes' => 0,
                'baseline_source' => 'none',
                'baseline_label' => null,
                'auto_calculated' => false,
            ];
        }

        if ($manualOverride) {
            return [
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'worked_minutes' => (int) ($payload['worked_minutes'] ?? 0),
                'overtime_minutes' => (int) ($payload['overtime_minutes'] ?? 0),
                'late_minutes' => (int) ($payload['late_minutes'] ?? 0),
                'early_leave_minutes' => (int) ($payload['early_leave_minutes'] ?? 0),
                'planned_minutes' => 0,
                'baseline_source' => 'manual_override',
                'baseline_label' => null,
                'auto_calculated' => false,
            ];
        }

        $workDate = Carbon::parse($date)->startOfDay();
        $context = $this->resolveBaselineContext($tabelNo, $date, $payload);
        /** @var ?AttendanceShift $shift */
        $shift = $context['shift'];
        $baselineSource = (string) $context['baseline_source'];
        $baselineLabel = $context['baseline_label'];
        $plannedMinutes = (int) $context['planned_minutes'];
        $setting = $this->globalActiveSetting();

        $checkIn = Carbon::parse($workDate->toDateString().' '.$checkInAt);
        $checkOut = Carbon::parse($workDate->toDateString().' '.$checkOutAt);

        if ($shift !== null) {
            $window = app(AttendanceShiftWindowService::class)->resolve($workDate, $shift);
            if ($checkOut->lte($checkIn) || $window['shift_end']->lt($window['shift_start'])) {
                $checkOut->addDay();
            }

            $policy = app(AttendanceRulePolicyService::class);
            $scheduledMinutes = $plannedMinutes;
            $workedMinutes = max(0, $checkIn->diffInMinutes($checkOut) - (int) $shift->break_minutes);
            $lateMinutes = $policy->applyGrace(
                max(0, $window['shift_start']->diffInMinutes($checkIn, false)),
                max(0, (int) ($setting?->late_grace_minutes ?? 0))
            );
            $earlyLeaveMinutes = $policy->applyGrace(
                max(0, $checkOut->diffInMinutes($window['shift_end'], false)),
                max(0, (int) ($setting?->early_leave_grace_minutes ?? 0))
            );
            $overtimeMinutes = $policy->resolveOvertimeMinutes(
                workedMinutes: $workedMinutes,
                scheduledMinutes: $scheduledMinutes,
                calendarDayType: 'workday',
                setting: $setting,
                approvedOvertimeMinutes: null,
            );

            return [
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'worked_minutes' => $workedMinutes,
                'overtime_minutes' => max(0, $overtimeMinutes),
                'late_minutes' => (int) $lateMinutes,
                'early_leave_minutes' => (int) $earlyLeaveMinutes,
                'planned_minutes' => $scheduledMinutes,
                'baseline_source' => $baselineSource,
                'baseline_label' => $baselineLabel,
                'auto_calculated' => true,
            ];
        }

        if ($checkOut->lte($checkIn)) {
            $checkOut->addDay();
        }

        return [
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'worked_minutes' => max(0, $checkIn->diffInMinutes($checkOut)),
            'overtime_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'planned_minutes' => 0,
            'baseline_source' => 'none',
            'baseline_label' => null,
            'auto_calculated' => true,
        ];
    }

    private function normalizeTimeValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function globalDefaultShift(): ?AttendanceShift
    {
        return $this->globalActiveSetting()?->defaultShift;
    }

    private function globalActiveSetting(): ?AttendanceSetting
    {
        if (self::$globalSettingLoaded) {
            return self::$globalSetting;
        }

        self::$globalSettingLoaded = true;

        return self::$globalSetting = AttendanceSetting::query()
            ->with('defaultShift:id,name,start_time,end_time,break_minutes,is_night_shift,in_flex_before_minutes,in_flex_after_minutes,out_flex_before_minutes,out_flex_after_minutes,is_active')
            ->where('scope_type', 'global')
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    /**
     * @param  Collection<int,AttendanceShiftAssignment>  $assignmentsForTabel
     */
    private function resolveBaselineShift(
        Collection $assignmentsForTabel,
        Carbon $date,
        ?AttendanceShift $defaultShift = null,
        ?AttendanceShift $explicitShift = null
    ): array
    {
        if ($explicitShift !== null) {
            return [$explicitShift, 'explicit_shift'];
        }

        foreach ($assignmentsForTabel as $assignment) {
            $from = $assignment->effective_from?->copy()->startOfDay();
            $to = $assignment->effective_to?->copy()->endOfDay();

            if ($from === null) {
                continue;
            }

            if ($date->lt($from)) {
                continue;
            }

            if ($to !== null && $date->gt($to)) {
                continue;
            }

            return [$assignment->shift, 'assignment_shift'];
        }

        if ($defaultShift !== null) {
            return [$defaultShift, 'default_shift'];
        }

        return [null, 'none'];
    }
}
