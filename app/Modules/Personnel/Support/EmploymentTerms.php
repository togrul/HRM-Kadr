<?php

namespace App\Modules\Personnel\Support;

/**
 * The fixed choice lists behind the contract and working-time fields of the
 * personnel form.
 *
 * They live here rather than in a reference table because the labour code fixes
 * them: an administrator adding a fourth kind of working time would also need
 * the form to grow the panels that go with it, so a lookup table would only
 * offer an editability the screen cannot honour. The validation rules and the
 * form read the same lists from this class.
 */
final class EmploymentTerms
{
    public const CONTRACT_TYPES = ['fixed', 'indefinite'];

    public const PROBATION_UNITS = ['day', 'week', 'month'];

    public const WORKPLACE_TYPES = ['primary', 'secondary'];

    public const WORKING_TIME_TYPES = ['full', 'partial', 'reduced'];

    public const WORK_SCHEDULES = ['five_day', 'six_day', 'shift_1', 'shift_2', 'shift_3', 'shift_4', 'other'];

    /** Schedules built on a working week rather than a shift rota. */
    public const WEEKLY_SCHEDULES = ['five_day', 'six_day'];

    public const REST_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /**
     * Number of shifts a schedule runs, 0 for the ones that have none.
     */
    public static function shiftCount(?string $schedule): int
    {
        return match ($schedule) {
            'shift_1' => 1,
            'shift_2' => 2,
            'shift_3' => 3,
            'shift_4' => 4,
            default => 0,
        };
    }

    public static function isWeekly(?string $schedule): bool
    {
        return in_array($schedule, self::WEEKLY_SCHEDULES, true);
    }

    /**
     * Option list in the `{id, label}` shape the select dropdown expects.
     *
     * @param  array<int, string>  $values
     * @return array<int, array{id: string, label: string}>
     */
    public static function options(array $values, string $translationGroup): array
    {
        return array_map(fn (string $value): array => [
            'id' => $value,
            'label' => __("personnel::common.employment.{$translationGroup}.{$value}"),
        ], $values);
    }
}
