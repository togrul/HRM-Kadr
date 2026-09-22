<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceNotificationSetting extends Model
{
    protected $fillable = ['user_id', 'email', 'digest'];

    protected $casts = [
        'email' => 'boolean',
        'digest' => 'boolean',
    ];
}
