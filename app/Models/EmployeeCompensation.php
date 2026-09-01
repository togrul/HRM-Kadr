<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $tabel_no
 * @property float|string $base_amount
 * @property string|null $currency
 * @property mixed $effective_from
 * @property mixed $effective_to
 */
class EmployeeCompensation extends Model
{
    use HasFactory;
    use LogsActivity;
    use PersonnelTrait;

    public const MASK = '•••';

    // "compensation" is treated as uncountable by the inflector — pin the table name.
    protected $table = 'employee_compensations';

    protected $fillable = [
        'tabel_no',
        'regime_id',
        'pay_grade_id',
        'base_amount',
        'currency',
        'effective_from',
        'effective_to',
        'status',
        'order_no',
        'note',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }

    public function payGrade(): BelongsTo
    {
        return $this->belongsTo(PayGrade::class, 'pay_grade_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EmployeeCompensationLine::class, 'employee_compensation_id');
    }

    /**
     * Base amount masked for users without the `view-compensation-amounts` permission.
     */
    public function maskedBaseAmount(): string
    {
        if (auth()->user()?->can('view-compensation-amounts')) {
            return number_format((float) $this->base_amount, 2);
        }

        return self::MASK;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_compensation')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
