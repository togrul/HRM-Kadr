<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalEventRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'registry_key',
        'event_type',
        'title',
        'organizer_name',
        'country_id',
        'first_seen_at',
        'last_seen_at',
        'records_count',
        'last_source_record_id',
    ];

    protected $casts = [
        'first_seen_at' => 'date',
        'last_seen_at' => 'date',
        'records_count' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function lastSourceRecord(): BelongsTo
    {
        return $this->belongsTo(PersonnelEventRecord::class, 'last_source_record_id');
    }
}
