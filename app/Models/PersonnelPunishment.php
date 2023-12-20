<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonnelPunishment extends Model
{
    use HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'punishment_id',
        'reason',
        'given_date',
        'expired_date'
    ];

    protected $dates = [
        'given_date',
        'expired_date'
    ];

    public function punishment() : BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }
}
