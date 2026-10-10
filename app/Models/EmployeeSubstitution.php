<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * A substitution (əvəzetmə): the employee performs an absent colleague's duties on top
 * of their own for the period, for the extra pay agreed on the order.
 *
 * @property string $tabel_no
 * @property string|null $substituted_tabel_no
 * @property string|null $substituted_name
 * @property int|null $substituted_position_id
 * @property mixed $start_date
 * @property mixed $end_date
 * @property float|string|null $extra_pay_percent
 * @property float|string|null $extra_pay_amount
 * @property string|null $order_no
 * @property string $source_key
 */
class EmployeeSubstitution extends Model
{
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'substituted_tabel_no',
        'substituted_name',
        'substituted_position_id',
        'start_date',
        'end_date',
        'extra_pay_percent',
        'extra_pay_amount',
        'order_no',
        'source_key',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'extra_pay_percent' => 'decimal:2',
        'extra_pay_amount' => 'decimal:2',
    ];
}
