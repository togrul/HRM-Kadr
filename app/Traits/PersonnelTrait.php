<?php

namespace App\Traits;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PersonnelTrait
{
    public function personnel() : BelongsTo
    {
        return $this->belongsTo(Personnel::class,'tabel_no','tabel_no');
    }
}
