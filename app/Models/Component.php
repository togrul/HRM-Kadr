<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_type_id',
        'rank_id',
        'name',
        'content',
        'dynamic_fields'
    ];

    protected $casts = [
        'dynamic_fields' => 'array'
    ];

    public function orderType() : BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function rank() : BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function orders() : BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function attributes() : HasMany
    {
        return $this->hasMany(OrderLogComponentAttributes::class,'component_id','id');
    }
}
