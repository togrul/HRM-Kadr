<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelBusinessTrip extends Model
{
    use HasFactory,SoftDeletes,DateCastTrait,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'location',
        'start_date',
        'end_date',
        'description',
        'order_given_by',
        'order_no',
        'order_date',
        'added_by',
        'deleted_by',
        'deleted_at'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'order_date'
    ];

    protected $casts = [
        'start_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y'
    ];

    public function personDidDelete() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class,'added_by','id');
    }

    public function deletedBy() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(OrderLog::class,'order_no','order_no');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->added_by = auth()->user()->id;
        });
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                $model->deleted_by = auth()->user()->id;
                $model->save();
            }
        });
    }
}
