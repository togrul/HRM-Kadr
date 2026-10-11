<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * İmtiyaz paketi (tibbi sığorta A/B, yemək kartı, nəqliyyat…): aylıq dəyər, işəgötürən/işçi
 * payı, vergi rejimi, provayder, müqavilə dövrü və uyğunluq qaydaları.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property string|null $provider
 * @property string $currency
 * @property string $monthly_cost
 * @property string $employer_share_percent
 * @property bool $allows_dependents
 * @property string $dependent_monthly_cost
 * @property string $dependent_employer_share_percent
 * @property int|null $max_dependents
 * @property bool $taxable
 * @property bool $affects_social
 * @property string|null $exempt_daily_norm
 * @property bool $norm_is_invoice_based
 * @property string|null $tax_basis
 * @property \Illuminate\Support\Carbon|null $contract_start
 * @property \Illuminate\Support\Carbon|null $contract_end
 * @property int $renewal_window_days
 * @property bool $auto_renew
 * @property bool $self_service
 * @property array<string, mixed>|null $eligibility
 * @property bool $is_active
 */
class BenefitPlan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'provider',
        'currency',
        'monthly_cost',
        'employer_share_percent',
        'allows_dependents',
        'dependent_monthly_cost',
        'dependent_employer_share_percent',
        'max_dependents',
        'taxable',
        'affects_social',
        'exempt_daily_norm',
        'norm_is_invoice_based',
        'tax_basis',
        'contract_start',
        'contract_end',
        'renewal_window_days',
        'auto_renew',
        'self_service',
        'eligibility',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'monthly_cost' => 'decimal:2',
        'employer_share_percent' => 'decimal:2',
        'allows_dependents' => 'boolean',
        'dependent_monthly_cost' => 'decimal:2',
        'dependent_employer_share_percent' => 'decimal:2',
        'max_dependents' => 'integer',
        'taxable' => 'boolean',
        'affects_social' => 'boolean',
        'exempt_daily_norm' => 'decimal:2',
        'norm_is_invoice_based' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'renewal_window_days' => 'integer',
        'auto_renew' => 'boolean',
        'self_service' => 'boolean',
        'eligibility' => 'array',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<BenefitEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(BenefitEnrollment::class);
    }

    /** @return HasMany<BenefitRenewalWindow, $this> */
    public function renewalWindows(): HasMany
    {
        return $this->hasMany(BenefitRenewalWindow::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('benefits')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
