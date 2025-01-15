<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAward extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'award_id',
        'reason',
        'given_date',
        'is_old',
        'order_no',
        'order_given_by',
        'order_date',
    ];

    protected $dates = [
        'given_date',
        'order_date',
    ];

    protected $casts = [
        'given_date' => self::FORMAT_CAST,
        'order_date' => self::FORMAT_CAST,
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }
}
