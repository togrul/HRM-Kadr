<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'award_type_id',
        'name',
        'is_foreign',
    ];

    public $timestamps = false;

    public function type(): BelongsTo
    {
        return $this->belongsTo(AwardType::class, 'award_type_id', 'id');
    }
}
