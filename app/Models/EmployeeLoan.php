<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $tabel_no
 * @property string $type
 * @property string $principal
 * @property string $monthly_installment
 * @property string $remaining
 * @property string $currency
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_on
 * @property string|null $note
 */
class EmployeeLoan extends Model
{
    use HasFactory;
    use LogsActivity;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'type',
        'principal',
        'monthly_installment',
        'remaining',
        'currency',
        'status',
        'start_on',
        'note',
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'remaining' => 'decimal:2',
        'start_on' => 'date',
    ];

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class, 'employee_loan_id');
    }

    /** @return HasOne<LoanRepayment, $this> */
    public function latestRepayment(): HasOne
    {
        return $this->hasOne(LoanRepayment::class, 'employee_loan_id')->latestOfMany();
    }

    /** Qalan borcun indiki taksitlə neçə aya bağlanacağı (təxmini; hədd səbəbindən uzana bilər). */
    public function monthsLeft(): ?int
    {
        $installment = (float) $this->monthly_installment;

        return $this->status === 'active' && $installment > 0 && (float) $this->remaining > 0
            ? (int) ceil(round((float) $this->remaining / $installment, 6))
            : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_loan')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
