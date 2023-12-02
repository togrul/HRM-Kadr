<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelRank extends Model
{
    use HasFactory,PersonnelTrait;

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

    public function rank() : BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
