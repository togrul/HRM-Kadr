<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * İş vaxtı normasının tək mənbəyi.
 *
 * Gündəlik norma növbədən hesablanır (növbə müddəti − nahar fasiləsi). Növbə
 * yoxdursa, Əmək Məcəlləsinin həftəlik 40 saat həddinə uyğun 5 günlük həftə
 * üçün gündə 8 saat götürülür. Bayram gününə bitişik (bayramqabağı) iş günündə
 * iş vaxtı 1 saat qısaldılır.
 */
class AttendanceWorkNormService
{
    /** Növbə təyin edilməyəndə tətbiq olunan gündəlik norma (dəqiqə). */
    public const DEFAULT_DAILY_MINUTES = 480;

    /** Bayramqabağı iş günündə iş vaxtının qısaldılması (dəqiqə). */
    public const PRE_HOLIDAY_REDUCTION_MINUTES = 60;

    /** @var array<int,string> */
    public const SHIFT_COLUMNS = ['id', 'name', 'start_time', 'end_time', 'break_minutes', 'is_night_shift', 'in_flex_before_minutes', 'in_flex_after_minutes', 'out_flex_before_minutes', 'out_flex_after_minutes'];

    private ?int $defaultDailyMinutes = null;

    /**
     * Növbənin xalis iş müddəti: başlanğıc ilə son arası, fasilə çıxılmaqla.
     */
    public function shiftDailyMinutes(?AttendanceShift $shift): int
    {
        if ($shift === null || ! filled($shift->start_time) || ! filled($shift->end_time)) {
            return $this->defaultDailyMinutes();
        }

        $window = app(AttendanceShiftWindowService::class)->resolve(Carbon::today(), $shift);
        $minutes = (int) $window['shift_start']->diffInMinutes($window['shift_end']) - max(0, (int) $shift->break_minutes);

        return $minutes > 0 ? $minutes : $this->defaultDailyMinutes();
    }

    /**
     * Qlobal tənzimləmədəki defolt növbənin norması, o yoxdursa 8 saat. Sorğu bir dəfə edilir.
     */
    public function defaultDailyMinutes(): int
    {
        if ($this->defaultDailyMinutes !== null) {
            return $this->defaultDailyMinutes;
        }

        $shift = $this->defaultShift();
        $minutes = self::DEFAULT_DAILY_MINUTES;

        if ($shift !== null && filled($shift->start_time) && filled($shift->end_time)) {
            $window = app(AttendanceShiftWindowService::class)->resolve(Carbon::today(), $shift);
            $shiftMinutes = (int) $window['shift_start']->diffInMinutes($window['shift_end']) - max(0, (int) $shift->break_minutes);
            $minutes = $shiftMinutes > 0 ? $shiftMinutes : self::DEFAULT_DAILY_MINUTES;
        }

        return $this->defaultDailyMinutes = $minutes;
    }

    /**
     * Qlobal tənzimləmədə seçilmiş defolt növbə.
     */
    public function defaultShift(): ?AttendanceShift
    {
        return $this->loadShiftsWithDefault([])['default'];
    }

    /**
     * Verilən növbələri və qlobal defolt növbəni tək sorğu ilə yükləyir.
     *
     * @param  array<int,int>  $shiftIds
     * @return array{shifts:array<int,AttendanceShift>,default:?AttendanceShift}
     */
    public function loadShiftsWithDefault(array $shiftIds): array
    {
        $defaultShiftId = fn (): Builder => DB::table('attendance_settings')
            ->select('default_shift_id')
            ->where('is_active', true)
            ->where('scope_type', 'global')
            ->orderByDesc('id')
            ->limit(1);

        $shifts = AttendanceShift::query()
            ->select(self::SHIFT_COLUMNS)
            ->addSelect(['configured_default_shift_id' => $defaultShiftId()])
            ->where(function ($query) use ($shiftIds, $defaultShiftId): void {
                $query->where('id', '=', $defaultShiftId());

                if ($shiftIds !== []) {
                    $query->orWhereIn('id', $shiftIds);
                }
            })
            ->get();

        $default = $shifts->first(
            fn (AttendanceShift $shift): bool => (int) $shift->id === (int) $shift->getAttribute('configured_default_shift_id')
        );

        return [
            'shifts' => $shifts->keyBy('id')->all(),
            'default' => $default instanceof AttendanceShift ? $default : null,
        ];
    }

    /**
     * Günün planlaşdırılmış dəqiqələri: iş günü deyilsə 0, bayramqabağıdırsa 1 saat az.
     */
    public function plannedMinutesForDay(int $dailyMinutes, string $dayType, ?string $nextDayType): int
    {
        if ($dayType !== 'workday') {
            return 0;
        }

        if ($nextDayType === 'holiday') {
            return max(0, $dailyMinutes - self::PRE_HOLIDAY_REDUCTION_MINUTES);
        }

        return max(0, $dailyMinutes);
    }

    /**
     * Təqvim istisnaları əsasında günün tipi (struktur istisnası qlobaldan üstündür).
     *
     * @param  array<string,string>  $globalMap
     * @param  array<string,string>  $structureMap  açar: "structureId|Y-m-d"
     */
    public function resolveDayType(CarbonInterface $date, ?int $structureId, array $globalMap, array $structureMap = []): string
    {
        $dateKey = $date->toDateString();

        if ($structureId !== null && isset($structureMap[$structureId.'|'.$dateKey])) {
            return $structureMap[$structureId.'|'.$dateKey];
        }

        return $globalMap[$dateKey] ?? ($date->isWeekend() ? 'weekend' : 'workday');
    }

    /**
     * Təqvim istisnaları [from, to+1 gün] aralığında — sonuncu gün ayın son gününün bayramqabağı olub-olmadığını bilmək üçündür.
     *
     * @param  array<int,int>  $structureIds
     * @return array{global:array<string,string>,structure:array<string,string>}
     */
    public function calendarMaps(Carbon $from, Carbon $to, array $structureIds = []): array
    {
        $rows = AttendanceCalendar::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->copy()->addDay()->toDateString())
            ->where(function ($query) use ($structureIds): void {
                $query->where('scope_type', 'global');

                if ($structureIds !== []) {
                    $query->orWhere(function ($q) use ($structureIds): void {
                        $q->where('scope_type', 'structure')
                            ->whereIn('scope_id', $structureIds);
                    });
                }
            })
            ->get(['date', 'day_type', 'scope_type', 'scope_id']);

        $global = [];
        $structure = [];

        foreach ($rows as $row) {
            $dateKey = $row->date?->toDateString();
            if ($dateKey === null) {
                continue;
            }

            if ($row->scope_type === 'structure' && $row->scope_id !== null) {
                $structure[(int) $row->scope_id.'|'.$dateKey] = (string) $row->day_type;

                continue;
            }

            $global[$dateKey] = (string) $row->day_type;
        }

        return ['global' => $global, 'structure' => $structure];
    }

    /**
     * Ayın iş vaxtı norması bir işçi üçün.
     *
     * @return array{workdays:int,non_workdays:int,pre_holidays:int,daily_minutes:int,minutes:int}
     */
    public function monthNorm(int $year, int $month, ?int $structureId = null, ?int $dailyMinutes = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $maps = $this->calendarMaps($start, $end, $structureId !== null ? [$structureId] : []);
        $daily = $dailyMinutes ?? $this->defaultDailyMinutes();

        $workdays = 0;
        $nonWorkdays = 0;
        $preHolidays = 0;
        $minutes = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dayType = $this->resolveDayType($cursor, $structureId, $maps['global'], $maps['structure']);
            $nextDayType = $this->resolveDayType($cursor->copy()->addDay(), $structureId, $maps['global'], $maps['structure']);

            if ($dayType === 'workday') {
                $workdays++;
                if ($nextDayType === 'holiday') {
                    $preHolidays++;
                }
            } else {
                $nonWorkdays++;
            }

            $minutes += $this->plannedMinutesForDay($daily, $dayType, $nextDayType);
            $cursor->addDay();
        }

        return [
            'workdays' => $workdays,
            'non_workdays' => $nonWorkdays,
            'pre_holidays' => $preHolidays,
            'daily_minutes' => $daily,
            'minutes' => $minutes,
        ];
    }
}
