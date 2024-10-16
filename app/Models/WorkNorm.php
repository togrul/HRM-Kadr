<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkNorm extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name_az',
        'name_en',
        'name_ru',
    ];

    public $timestamps = false;
}
