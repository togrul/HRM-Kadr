<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $approval_rank
 * @property bool $is_approval_target
 */
class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'rank_category_id',
        'approval_rank',
        'is_approval_target',
        'name',
    ];

    protected $casts = [
        'approval_rank' => 'integer',
        'is_approval_target' => 'boolean',
    ];

    public $timestamps = false;

    public function rankCategory(): BelongsTo
    {
        return $this->belongsTo(RankCategory::class);
    }
}
