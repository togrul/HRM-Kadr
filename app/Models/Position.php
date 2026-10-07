<?php

namespace App\Models;

use App\Support\PositionLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $approval_rank
 * @property bool $is_approval_target
 * @property int|null $level
 */
class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'rank_category_id',
        'approval_rank',
        'is_approval_target',
        'level',
        'name',
    ];

    protected $casts = [
        'approval_rank' => 'integer',
        'is_approval_target' => 'boolean',
        'level' => 'integer',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        // A post saved without a level gets one from its name, so sorting never sees a gap.
        static::saving(function (Position $position): void {
            if ($position->level === null) {
                $position->level = PositionLevel::guess((string) $position->name);
            }
        });
    }

    public function rankCategory(): BelongsTo
    {
        return $this->belongsTo(RankCategory::class);
    }
}
