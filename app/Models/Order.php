<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'order_category_id',
        'shortname',
        'name_az',
        'name_en',
        'name_ru',
    ];

    public function category() : BelongsTo
    {
        return $this->belongsTo(OrderCategory::class);
    }
}
