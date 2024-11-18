<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Punishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'punishment_type_id',
        'name',
    ];

    public const PUNISHMENT_TYPES = [
        'criminal' => 10,
        'other' => 90,
    ];

    public $timestamps = false;

    public function type(): BelongsTo
    {
        return $this->belongsTo(PunishmentType::class, 'punishment_type_id', 'id');
    }

    public function scopeCriminalType($query, $value)
    {
        return $query->where('punishment_type_id', self::PUNISHMENT_TYPES[$value]);
    }
}
