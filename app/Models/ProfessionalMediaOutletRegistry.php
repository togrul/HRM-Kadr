<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalMediaOutletRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'registry_key',
        'publisher_name',
        'publisher_type',
        'first_seen_at',
        'last_seen_at',
        'mentions_count',
        'last_source_record_id',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'mentions_count' => 'integer',
    ];

    public function lastSourceRecord(): BelongsTo
    {
        return $this->belongsTo(PersonnelMediaMention::class, 'last_source_record_id');
    }
}
