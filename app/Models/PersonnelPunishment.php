<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelPunishment extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'punishment_id',
        'reason',
        'given_date',
        'expired_date',
    ];

    protected $dates = [
        'given_date',
        'expired_date',
    ];

    protected $casts = [
        'given_date' => self::FORMAT_CAST,
        'expired_date' => self::FORMAT_CAST,
    ];

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }
}
