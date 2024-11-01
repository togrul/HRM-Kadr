<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelCard extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use DateCastTrait;

    protected $fillable = [
        'tabel_no',
        'card_number',
        'valid_date',
    ];

    protected $dates = ['valid_date'];
}
