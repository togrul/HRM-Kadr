<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * İşçinin gəlir vergisi güzəşti hüququ (Vergi Məcəlləsi m.102.1-1, 102.2–102.5). Məbləğ
 * `statutory_exemptions`-dadır (növ `personal`, kod = `category`); m.102.7 — 102.1-1/102.2/102.3/102.4
 * üzrə bir neçə hüquq olduqda yalnız ən böyüyü verilir (StatutoryEngine).
 *
 * @property int $id
 * @property string $tabel_no
 * @property string $category
 * @property string|null $basis_detail
 * @property string $document_no
 * @property \Illuminate\Support\Carbon|null $document_date
 * @property string|null $document_issuer
 * @property string|null $document_path
 * @property string|null $document_disk
 * @property string|null $document_name
 * @property \Illuminate\Support\Carbon $status_from
 * @property \Illuminate\Support\Carbon $submitted_on
 * @property \Illuminate\Support\Carbon|null $valid_to
 * @property \Illuminate\Support\Carbon $legal_from
 * @property \Illuminate\Support\Carbon $payroll_from
 * @property string|null $note
 * @property int|null $created_by
 */
class EmployeeTaxRelief extends Model
{
    use LogsActivity;
    use PersonnelTrait;

    /** Kateqoriya kodu → Vergi Məcəlləsinin bəndi. */
    public const CATEGORIES = [
        'vm_102_1_1' => 'VM 102.1-1',
        'vm_102_2' => 'VM 102.2',
        'vm_102_3' => 'VM 102.3',
        'vm_102_4' => 'VM 102.4',
        'vm_102_5' => 'VM 102.5',
    ];

    /** VM m.102.7: bu bəndlər üzrə bir neçə hüquq olduqda yalnız ən böyüyü verilir. */
    public const LARGEST_ONLY = ['vm_102_1_1', 'vm_102_2', 'vm_102_3', 'vm_102_4'];

    protected $fillable = [
        'tabel_no',
        'category',
        'basis_detail',
        'document_no',
        'document_date',
        'document_issuer',
        'document_path',
        'document_disk',
        'document_name',
        'status_from',
        'submitted_on',
        'valid_to',
        'legal_from',
        'payroll_from',
        'note',
        'created_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'status_from' => 'date',
        'submitted_on' => 'date',
        'valid_to' => 'date',
        'legal_from' => 'date',
        'payroll_from' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('compensation')
            ->logOnly(['tabel_no', 'category', 'basis_detail', 'document_no', 'document_date', 'document_issuer', 'document_name', 'status_from', 'submitted_on', 'valid_to', 'legal_from', 'payroll_from', 'note'])
            ->logOnlyDirty();
    }
}
