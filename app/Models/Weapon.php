<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weapon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_number',
        'capacity',
        'production_year',
    ];

    public $timestamps = false;

    public function personnels(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Personnel::class, 'weapon_id', 'id');
    }
}
