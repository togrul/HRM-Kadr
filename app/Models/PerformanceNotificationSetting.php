<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $email
 * @property bool $digest
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceNotificationSetting extends Model
{
    protected $fillable = ['user_id', 'email', 'digest'];

    protected $casts = [
        'email' => 'boolean',
        'digest' => 'boolean',
    ];
}
