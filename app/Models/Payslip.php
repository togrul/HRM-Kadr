<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;
    use PersonnelTrait;

    public const MASK = '•••';

    protected $fillable = [
        'payroll_run_id',
        'tabel_no',
        'regime_id',
        'gross',
        'total_deductions',
        'net',
        'employer_cost',
        'proration_factor',
        'currency',
        'status',
        'snapshot',
    ];

    protected $casts = [
        'gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net' => 'decimal:2',
        'employer_cost' => 'decimal:2',
        'proration_factor' => 'decimal:4',
        'snapshot' => 'array',
    ];

    /** @return BelongsTo<PayrollRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /** @return BelongsTo<CompensationRegime, $this> */
    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }

    /** @return HasMany<PayslipLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class, 'payslip_id');
    }

    /**
     * Amount masked for users without the `view-compensation-amounts` permission.
     */
    public function mask(float|string|null $amount): string
    {
        if (auth()->user()?->can('view-compensation-amounts')) {
            return number_format((float) $amount, 2);
        }

        return self::MASK;
    }
}
