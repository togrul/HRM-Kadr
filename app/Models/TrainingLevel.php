<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'score',
        'description',
        'sort_order',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function requirements(): HasMany
    {
        return $this->hasMany(RoleCompetencyRequirement::class, 'required_level_id');
    }
}
