<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name_az',
        'name_en',
        'name_ru',
        'duration',
        'is_active'
    ];

    public $timestamps = false;

    public function getNameAttribute($value)
    {
        $localeColumn = 'name_' . config('app.locale');
        return $this->attributes[$localeColumn] ?? null;
    }
}
