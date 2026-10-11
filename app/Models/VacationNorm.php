<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Bir məzuniyyət norması sətri (bax: App\Modules\Vacation\Application\Services\VacationNormEvaluator).
 *
 * @property int $id
 * @property string $group base|seniority|children|conditions
 * @property string $scope all|vtisk_category|position|personnel|age_under_16|age_16_18|disability
 * @property int|null $position_id
 * @property string|null $tabel_no
 * @property string|null $condition
 * @property int|null $min_value
 * @property int|null $max_value
 * @property bool $min_inclusive staj pilləsində aşağı hədd daxildirmi («X il və daha çox»)
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_to
 * @property bool $not_in_conditions
 * @property bool $women_only
 * @property bool $exclusive
 * @property int $days
 * @property bool $is_active
 * @property bool $is_statutory
 * @property string|null $statutory_key qanuni staj pilləsinin açarı (seniority_5 / _10 / _15)
 * @property string|null $legal_basis
 * @property string|null $note
 */
class VacationNorm extends Model
{
    use LogsActivity;

    public const GROUP_BASE = 'base';

    public const GROUP_SENIORITY = 'seniority';

    public const GROUP_CHILDREN = 'children';

    public const GROUP_CONDITIONS = 'conditions';

    public const GROUPS = [self::GROUP_BASE, self::GROUP_SENIORITY, self::GROUP_CHILDREN, self::GROUP_CONDITIONS];

    public const SCOPE_ALL = 'all';

    public const SCOPE_POSITION = 'position';

    public const SCOPE_PERSONNEL = 'personnel';

    public const SCOPE_AGE_UNDER_16 = 'age_under_16';

    public const SCOPE_AGE_16_18 = 'age_16_18';

    public const SCOPE_DISABILITY = 'disability';

    /** Vəzifənin VTİSK kateqoriyası (`condition` = App\Support\VtiskCategory), ƏM m.114.3 "b". */
    public const SCOPE_VTISK_CATEGORY = 'vtisk_category';

    public const CONDITION_CHILDREN_UNDER_14 = 'children_under_14';

    public const CONDITION_DISABLED_CHILD = 'disabled_child';

    protected $fillable = [
        'group',
        'scope',
        'position_id',
        'tabel_no',
        'condition',
        'min_value',
        'max_value',
        'min_inclusive',
        'women_only',
        'exclusive',
        'days',
        'is_active',
        'is_statutory',
        'statutory_key',
        'legal_basis',
        'note',
        'valid_from',
        'valid_to',
        'not_in_conditions',
    ];

    protected $casts = [
        'position_id' => 'integer',
        'min_value' => 'integer',
        'max_value' => 'integer',
        'min_inclusive' => 'boolean',
        'women_only' => 'boolean',
        'exclusive' => 'boolean',
        'days' => 'integer',
        'is_active' => 'boolean',
        'is_statutory' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'not_in_conditions' => 'boolean',
    ];

    /** Sətir verilən gündə qüvvədədirmi (dövr göstərilməyibsə həmişə). */
    public function coversDate(string $date): bool
    {
        return ($this->valid_from === null || $this->valid_from->toDateString() <= $date)
            && ($this->valid_to === null || $date <= $this->valid_to->toDateString());
    }

    /**
     * Staj pilləsi verilən staja şamil olunurmu. `$compare(N)` — staj düz N ildən azdırsa -1,
     * düz N ildirsə 0, çoxdursa 1 (LeaveEntitlementFacts::compareSeniorityTo). Aşağı hədd
     * `min_inclusive`-ə görə daxil / xaric, yuxarı hədd həmişə daxildir («-dək»).
     *
     * @param  callable(int): int  $compare
     */
    public function coversSeniority(callable $compare): bool
    {
        $from = $compare((int) ($this->min_value ?? 0));

        if ($this->min_inclusive !== false ? $from < 0 : $from <= 0) {
            return false;
        }

        return $this->max_value === null || $compare((int) $this->max_value) <= 0;
    }

    /** Dəyişikliklər audit jurnalına düşür (Audit → «Məzuniyyət normaları»). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vacation_norm')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The scopes each group may use.
     *
     * @return array<string, list<string>>
     */
    public static function scopesByGroup(): array
    {
        return [
            self::GROUP_BASE => [self::SCOPE_ALL, self::SCOPE_VTISK_CATEGORY, self::SCOPE_POSITION, self::SCOPE_PERSONNEL, self::SCOPE_AGE_UNDER_16, self::SCOPE_AGE_16_18, self::SCOPE_DISABILITY],
            self::GROUP_SENIORITY => [self::SCOPE_ALL],
            self::GROUP_CHILDREN => [self::SCOPE_ALL, self::SCOPE_PERSONNEL],
            self::GROUP_CONDITIONS => [self::SCOPE_POSITION, self::SCOPE_PERSONNEL],
        ];
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'tabel_no', 'tabel_no');
    }
}
