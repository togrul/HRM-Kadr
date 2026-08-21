<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_loan')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
