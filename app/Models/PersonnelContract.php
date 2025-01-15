<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelContract extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use DateCastTrait;

    protected $fillable = [
        'tabel_no',
        'rank_id',
        'contract_date',
        'contract_refresh_date',
        'contract_duration',
        'contract_ends_at',
    ];

    protected $dates = [
        'contract_date',
        'contract_refresh_date',
        'contract_ends_at',
    ];

    protected $casts = [
        'contract_date' => self::FORMAT_CAST,
        'contract_refresh_date' => self::FORMAT_CAST,
        'contract_ends_at' => self::FORMAT_CAST,
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
