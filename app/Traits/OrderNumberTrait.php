<?php

namespace App\Traits;

use App\Models\OrderLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OrderNumberTrait
{
    public function orderNo() : BelongsTo
    {
        return $this->belongsTo(OrderLog::class,'order_no','order_no');
    }
}
