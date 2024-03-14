<?php

namespace App\Models;

use App\Traits\OrderNumberTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLogComponent extends Model
{
    use HasFactory,OrderNumberTrait;

    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'component_id',
        'row_number'
    ];

    public function component() : BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
