<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelMilitaryService extends Model
{
    use HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'attitude_to_military_service',
        'rank_id',
        'given_date',
        'start_date',
        'end_date'
    ];

    protected $dates = [
        'given_date',
        'start_date',
        'end_date'
    ];

    public function rank() : BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
