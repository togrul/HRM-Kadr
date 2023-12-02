<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PersonnelTrait
{
    public function personnel() : BelongsTo
    {
        return $this->belongsTo(Personnel::class,'tabel_no','tabel_no');
    }
}
