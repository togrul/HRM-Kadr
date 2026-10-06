<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_scale_id',
        'code',
        'name',
        'base_amount',
        'rank_category_id',
        'position_id',
        'sort',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'sort' => 'integer',
    ];

    public function payScale(): BelongsTo
    {
        return $this->belongsTo(PayScale::class, 'pay_scale_id');
    }

    public function rankCategory(): BelongsTo
    {
        return $this->belongsTo(RankCategory::class, 'rank_category_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}
