<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    use HasFactory;
    use PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'reserved_date_month',
        'vacation_days_total',
        'remaining_days',
        'year',
    ];

    protected $casts = [
        'remaining_days' => 'integer',
        'year' => 'integer',
        'vacation_days_total' => 'integer',
        'reserved_date_month' => 'integer',
    ];

    public function getUsedDaysAttribute()
    {
        return $this->vacation_days_total - $this->remaining_days;
    }
}
