<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelMilitaryService extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'attitude_to_military_service',
        'rank_id',
        'given_date',
        'start_date',
        'end_date',
    ];

    protected $dates = [
        'given_date',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'given_date' => 'date:d.m.Y',
        'start_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
