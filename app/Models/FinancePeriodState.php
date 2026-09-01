<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Whether the finance system has closed an accounting month.
 *
 * Read by the month-lock guard. Kept locally on purpose: asking over the network
 * would make the answer depend on reachability, and behind a firewall the safe
 * answer would have to be "assume closed" anyway.
 *
 * @property int $year
 * @property int $month
 * @property bool $closed
 */
class FinancePeriodState extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'closed' => 'boolean',
        'closed_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /** Has the finance system closed this month? */
    public static function isClosed(int $year, int $month): bool
    {
        return static::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('closed', true)
            ->exists();
    }
}
