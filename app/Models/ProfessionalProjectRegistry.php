<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalProjectRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'registry_key',
        'project_name',
        'project_code',
        'project_type',
        'sponsor_unit_id',
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

    public function sponsorUnit(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'sponsor_unit_id');
    }

    public function lastSourceRecord(): BelongsTo
    {
        return $this->belongsTo(PersonnelProjectRecord::class, 'last_source_record_id');
    }
}
