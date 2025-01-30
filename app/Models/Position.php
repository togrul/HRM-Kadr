<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'rank_category_id',
        'name',
    ];

    public $timestamps = false;

    public function rankCategory(): BelongsTo
    {
        return $this->belongsTo(RankCategory::class);
    }
}
