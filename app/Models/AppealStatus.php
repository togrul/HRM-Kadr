<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppealStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'locale',
        'name',
    ];

    public $timestamps = false;
}
