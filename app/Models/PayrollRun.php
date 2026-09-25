<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $payroll_period_id
 * @property int|null $regime_id
 * @property string $run_type
 * @property string $status
 * @property float|string $gross_total
 * @property float|string $deduction_total
 * @property float|string $net_total
 * @property float|string $employer_total
 * @property int $employee_count
 * @property \Illuminate\Support\Carbon|null $calculated_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property int|null $created_by
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayrollRun extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'payroll_period_id',
        'regime_id',
        'run_type',
        'status',
        'gross_total',
        'deduction_total',
        'net_total',
        'employer_total',
        'employee_count',
        'calculated_at',
        'approved_at',
        'locked_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'gross_total' => 'decimal:2',
        'deduction_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'employer_total' => 'decimal:2',
        'employee_count' => 'integer',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /** @return BelongsTo<PayrollPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /** @return BelongsTo<CompensationRegime, $this> */
    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }

    /** @return HasMany<Payslip, $this> */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isEditable(): bool
    {
        return ! in_array($this->status, ['locked'], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_run')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
