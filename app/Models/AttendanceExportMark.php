<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A locked month that the finance system has already consumed.
 *
 * Its presence is what makes `unlockMonth()` refuse: reopening a month the other
 * side has already paid from would put the two systems permanently out of step,
 * without anything to signal it.
 *
 * @property int $year
 * @property int $month
 * @property string $consumer
 * @property \Illuminate\Support\Carbon $exported_at
 */
class AttendanceExportMark extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'exported_at' => 'datetime',
    ];

    public static function mark(int $year, int $month, string $consumer = 'finance'): void
    {
        static::query()->updateOrCreate(
            ['year' => $year, 'month' => $month, 'consumer' => $consumer],
            ['exported_at' => now()],
        );
    }

    public static function exported(int $year, int $month): bool
    {
        return static::query()->where('year', $year)->where('month', $month)->exists();
    }
}
