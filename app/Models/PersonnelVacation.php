<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelVacation extends Model
{
    use HasFactory,PersonnelTrait,SoftDeletes,DateCastTrait;

    protected $fillable = [
        'tabel_no',
        'vacation_places',
        'duration',
        'start_date',
        'end_date',
        'return_work_date',
        'order_given_by',
        'order_no',
        'order_date',
        'added_by',
        'deleted_by'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'return_work_date',
        'order_date'
    ];

    protected $casts = [
        'start_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
        'return_work_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y'
    ];

    public function personDidDelete() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

}
