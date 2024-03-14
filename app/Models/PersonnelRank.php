<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelRank extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'rank_id',
        'name',
        'given_date'
    ];

    protected $dates = [
        'given_date'
    ];

    protected $casts = [
        'given_date' => 'date:d.m.Y'
    ];

    public function rank() : BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
