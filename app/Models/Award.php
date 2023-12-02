<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'award_type_id',
        'name'
    ];

    public $timestamps = false;

    public function type() : BelongsTo
    {
        return $this->belongsTo(AwardType::class);
    }
}
