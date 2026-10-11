<?php

namespace App\Modules\Reports\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Planlı hesabat.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $tab
 * @property array<string,mixed> $filters
 * @property string $period
 * @property string $frequency
 * @property int|null $weekday
 * @property int|null $month_day
 * @property string $send_time
 * @property string $format
 * @property bool $is_active
 * @property \Carbon\CarbonInterface|null $next_run_at
 * @property \Carbon\CarbonInterface|null $last_run_at
 * @property string|null $last_status
 * @property string|null $last_error
 * @property-read User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int,User> $recipients
 */
class ReportSchedule extends Model
{
    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    /**
     * @var list<string>
     */
    public const FREQUENCIES = [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY, self::FREQUENCY_MONTHLY];

    public const PERIOD_CURRENT_MONTH = 'current_month';

    public const PERIOD_PREVIOUS_MONTH = 'previous_month';

    /**
     * @var list<string>
     */
    public const PERIODS = [self::PERIOD_CURRENT_MONTH, self::PERIOD_PREVIOUS_MONTH];

    public const FORMAT_XLSX = 'xlsx';

    public const FORMAT_CSV = 'csv';

    public const FORMAT_PDF = 'pdf';

    /**
     * @var list<string>
     */
    public const FORMATS = [self::FORMAT_XLSX, self::FORMAT_CSV, self::FORMAT_PDF];

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'report_schedules';

    protected $fillable = [
        'user_id',
        'name',
        'tab',
        'filters',
        'period',
        'frequency',
        'weekday',
        'month_day',
        'send_time',
        'format',
        'is_active',
        'next_run_at',
        'last_run_at',
        'last_status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_active' => 'boolean',
            'weekday' => 'integer',
            'month_day' => 'integer',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User,$this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<User,$this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_schedule_recipients', 'report_schedule_id', 'user_id');
    }

    /**
     * `$after`-dən sonrakı ilk göndəriş anı (tətbiqin saat qurşağında).
     */
    public function nextRunAfter(CarbonInterface $after): CarbonImmutable
    {
        $after = CarbonImmutable::instance($after);
        [$hour, $minute] = array_map('intval', explode(':', $this->send_time) + [1 => 0]);

        $candidate = match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $this->weeklyCandidate($after, $hour, $minute),
            self::FREQUENCY_MONTHLY => $this->monthlyCandidate($after, $hour, $minute),
            default => $after->setTime($hour, $minute),
        };

        if ($candidate->lessThanOrEqualTo($after)) {
            $candidate = match ($this->frequency) {
                self::FREQUENCY_WEEKLY => $candidate->addWeek(),
                self::FREQUENCY_MONTHLY => $candidate->addMonthNoOverflow()->setDay($this->monthDay()),
                default => $candidate->addDay(),
            };
        }

        return $candidate;
    }

    /**
     * Hesabat dövrü (il, ay) icra anına görə.
     *
     * @return array{year:int,month:int}
     */
    public function reportPeriod(CarbonInterface $at): array
    {
        $date = $this->period === self::PERIOD_PREVIOUS_MONTH
            ? CarbonImmutable::instance($at)->startOfMonth()->subMonth()
            : CarbonImmutable::instance($at);

        return ['year' => (int) $date->year, 'month' => (int) $date->month];
    }

    private function weeklyCandidate(CarbonImmutable $after, int $hour, int $minute): CarbonImmutable
    {
        $weekday = max(1, min(7, (int) ($this->weekday ?? 1)));
        $diff = ($weekday - $after->dayOfWeekIso + 7) % 7;

        return $after->addDays($diff)->setTime($hour, $minute);
    }

    private function monthlyCandidate(CarbonImmutable $after, int $hour, int $minute): CarbonImmutable
    {
        return $after->setDay($this->monthDay())->setTime($hour, $minute);
    }

    private function monthDay(): int
    {
        return max(1, min(28, (int) ($this->month_day ?? 1)));
    }
}
