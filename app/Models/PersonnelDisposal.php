<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelDisposal extends Model
{
    use DateCastTrait;
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'disposal_date',
        'disposal_end_date',
        'disposal_reason',
    ];

    protected $dates = ['disposal_date', 'disposal_end_date'];

    protected $casts = [
        'disposal_date' => self::FORMAT_CAST,
        'disposal_end_date' => self::FORMAT_CAST,
    ];
}
