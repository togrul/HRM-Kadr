<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CreateDeleteTrait
{
    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class,'creator_id','id');
    }

    public function personDidDelete() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->creator_id = auth()->user()->id ?? 1;
        });
        static::deleting(function ($model) {
            $model->deleted_by = auth()->user()->id;
            $model->save();
        });
    }
}
