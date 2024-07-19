<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory,SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'order_category_id',
        'name',
        'content',
        'order_model',
        'blade'
    ];

    const BLADE_VACATION = 'vacation';
    const BLADE_DEFAULT = 'default';

    // ishe girme emrine id verilir ve her yerde rahat yoxlamaq ucun manual olaraq modele daxil edilir.
    const IG_EMR = 1010;

    public function category() : BelongsTo
    {
        return $this->belongsTo(OrderCategory::class,'order_category_id');
    }

    public function orderLogs() : HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    public function types() : HasMany
    {
        return $this->hasMany(OrderType::class);
    }
}
