<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * İşçinin əmək haqqından tutulma üçün icra sənədi (ƏM m.174–176, «İcra haqqında» Qanun;
 * docs/payroll-legal-basis.md §10).
 *
 * @property int $id
 * @property string $tabel_no
 * @property string $kind bax KINDS
 * @property int $priority
 * @property string $issuer_type
 * @property string $issuer_name
 * @property string|null $officer_name
 * @property string $document_no
 * @property \Illuminate\Support\Carbon|null $document_date
 * @property \Illuminate\Support\Carbon $received_on
 * @property string $method
 * @property string|null $fixed_amount
 * @property string|null $percent
 * @property string $recipient_name
 * @property string|null $recipient_account
 * @property string|null $recipient_details
 * @property string|null $total_debt
 * @property string|null $remaining
 * @property string $currency
 * @property \Illuminate\Support\Carbon $starts_on
 * @property \Illuminate\Support\Carbon|null $ends_on
 * @property string $status
 * @property string|null $note
 * @property int|null $created_by
 */
class EnforcementDocument extends Model
{
    use LogsActivity;
    use PersonnelTrait;

    /**
     * Tələbin növü və «İcra haqqında» Qanunun m.75 üzrə növbəsi: 1 — aliment, sağlamlığa vurulmuş ziyan,
     * ailəni dolandıranın itkisi (75.2); 2 — əmək hüququ münasibətlərindən əmələ gələn tələblər (75.3);
     * 3 — sosial sığorta, işsizlikdən və icbari tibbi sığorta haqları (75.4); 4 — dövlət büdcəsinə və
     * büdcədənkənar fondlara ödənişlər (75.5); 5 — qalan bütün tələblər, o cümlədən cinayətlə vurulmuş
     * ziyan (75.6 — daxil olma ardıcıllığı ilə).
     */
    public const KINDS = ['alimony', 'health_damage', 'breadwinner_loss', 'labour_claim', 'social_insurance', 'state_budget', 'crime_damage', 'other'];

    /** ƏM m.176.3 / «İcra haqqında» m.65.3 — tutulma həddi 70 % olan tələblər (növbədən asılı deyil). */
    public const SEVENTY_PERCENT_KINDS = ['alimony', 'health_damage', 'breadwinner_loss', 'crime_damage'];

    public const METHODS = ['fixed', 'percent'];

    public const ISSUER_TYPES = ['court', 'enforcement_officer', 'notary', 'other'];

    public const STATUSES = ['active', 'suspended', 'closed'];

    protected $fillable = [
        'tabel_no', 'kind', 'priority', 'issuer_type', 'issuer_name', 'officer_name', 'document_no',
        'document_date', 'received_on', 'method', 'fixed_amount', 'percent', 'recipient_name',
        'recipient_account', 'recipient_details', 'total_debt', 'remaining', 'currency', 'starts_on',
        'ends_on', 'status', 'note', 'created_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'document_date' => 'date',
        'received_on' => 'date',
        'fixed_amount' => 'decimal:2',
        'percent' => 'decimal:2',
        'total_debt' => 'decimal:2',
        'remaining' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    /** @return HasMany<EnforcementDeduction, $this> */
    public function deductions(): HasMany
    {
        return $this->hasMany(EnforcementDeduction::class, 'enforcement_document_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
