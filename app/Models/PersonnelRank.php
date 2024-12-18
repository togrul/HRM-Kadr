<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelRank extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'rank_id',
        'rank_reason_id',
        'name',
        'given_date',
        'order_no',
        'order_given_by',
        'order_date',
    ];

    protected $dates = [
        'given_date',
        'order_date',
    ];

    protected $casts = [
        'given_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y',
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function rankReason(): BelongsTo
    {
        return $this->belongsTo(RankReason::class, 'rank_reason_id', 'id');
    }
}
