<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAward extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'award_id',
        'reason',
        'given_date',
        'is_old'
    ];

    protected $dates = [
        'given_date',
    ];

    protected $casts = [
        'given_date' => 'date:d.m.Y',
    ];

    public function award() : BelongsTo
    {
        return $this->belongsTo(Award::class);
    }
}
