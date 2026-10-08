<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $locale
 * @property string|null $name
 */
class OrderStatus extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'locale',
        'name',
    ];
}
