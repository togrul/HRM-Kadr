<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name_az',
        'name_en',
        'name_ru',
    ];

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }
}
