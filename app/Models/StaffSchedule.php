<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffSchedule extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'structure_id',
        'position_id',
        'total',
        'filled',
        'vacant'
    ];

    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position() : BelongsTo
    {
        return $this->belongsTo(Position::class,'position_id','id');
    }
}
