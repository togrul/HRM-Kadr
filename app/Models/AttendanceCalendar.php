<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date
 * @property string|null $name
 * @property bool $is_paid
 * @property string|null $scope_type
 * @property int|null $scope_id
 */
class AttendanceCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'day_type',
        'name',
        'is_paid',
        'scope_type',
        'scope_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_paid' => 'boolean',
    ];
}
