<?php

namespace App\Traits;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PersonnelTrait
{
    /** @return BelongsTo<Personnel, $this> */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'tabel_no', 'tabel_no');
    }
}
