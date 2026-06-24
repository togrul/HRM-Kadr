<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalentPool extends Model
{
    public const TYPES = ['hipo', 'successor', 'critical_role'];

    protected $fillable = [
        'name',
        'pool_type',
        'description',
        'created_by',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class)->orderBy('id');
    }
}
