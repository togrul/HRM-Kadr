<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Modules\Personnel\Contracts\WorkingTimeNormProvider;
use App\Modules\Personnel\Contracts\WorkingTimeProfile;
use App\Modules\Personnel\Support\EmploymentTerms;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * Builds each employee's WorkingTimeProfile from the personnel record in one query: the
 * birthdate (ƏM m.91.2 age categories), the disability and its date, the explicit weekly
 * norm, whether the week is part-time (m.94) and the working days per week.
 *
 * Disability: m.91.2 reduces the week for a disability "for a 61–100% loss of body
 * function" — the former I and II groups. The disability catalogue is a free-named
 * reference list with no degree column, so the degree is read from the entry's name:
 * "I qrup", "II qrup", "1-ci/2-ci qrup", "I/II dərəcə", or a percentage band starting at
 * 61 or more ("61-80%", "81–100 %"). Anything else (III group, "sağlamlıq imkanları
 * məhdud", unnamed) is not treated as reducing; the explicit norm covers such cases.
 */
class WorkingTimeNormService implements WorkingTimeNormProvider
{
    public function profiles(array $tabelNos): array
    {
        $tabelNos = array_values(array_unique(array_filter(array_map('strval', $tabelNos), fn (string $tabelNo): bool => $tabelNo !== '')));

        if ($tabelNos === []) {
            return [];
        }

        $columns = [
            'personnels.tabel_no',
            'personnels.birthdate',
            'personnels.disability_given_date',
            'personnels.working_time_type',
            'personnels.work_schedule',
            'disabilities.name as disability_name',
        ];
        $query = fn (array $columns) => Personnel::query()
            ->leftJoin('disabilities', 'disabilities.id', '=', 'personnels.disability_id')
            ->whereIn('personnels.tabel_no', $tabelNos)
            ->toBase()
            ->get($columns);

        try {
            $rows = $query([...$columns, 'personnels.weekly_hours_norm']);
        } catch (QueryException) {
            // A database the weekly-norm migration has not reached yet: age and disability
            // still apply, without a schema probe on every timesheet render.
            $rows = $query($columns);
        }

        $profiles = [];

        foreach ($rows as $row) {
            $weeklyHours = $row->weekly_hours_norm ?? null;
            $override = is_numeric($weeklyHours) ? (int) round((float) $weeklyHours * 60) : null;

            $profiles[(string) $row->tabel_no] = new WorkingTimeProfile(
                birthdate: $this->date($row->birthdate),
                reducingDisability: self::reducesWorkingTime($row->disability_name),
                disabilitySince: $this->date($row->disability_given_date),
                overrideWeeklyMinutes: $override !== null && $override > 0 ? $override : null,
                partTime: $row->working_time_type === EmploymentTerms::WORKING_TIME_PARTIAL,
                workingDaysPerWeek: $row->work_schedule === 'six_day' ? 6 : 5,
            );
        }

        return $profiles;
    }

    /**
     * Whether a disability catalogue entry is a 61–100% loss of function (I or II group).
     */
    public static function reducesWorkingTime(?string $name): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return false;
        }

        if (preg_match('/(?<![\p{L}\d])(?:I{1,2}|[12])(?:\s*-?\s*(?:ci|cı|nci|ncı))?\s+(?:qrup|dərəcə)/iu', $name) === 1) {
            return true;
        }

        if (preg_match('/(\d{1,3})\s*[-–]\s*\d{1,3}\s*%/u', $name, $match) === 1) {
            return (int) $match[1] >= 61;
        }

        return false;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
