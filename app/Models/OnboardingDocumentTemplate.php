<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class OnboardingDocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'document_type',
        'version',
        'version_family_key',
        'previous_version_id',
        'file_path',
        'disk',
        'mime_type',
        'is_required',
        'requires_acknowledgement',
        'is_active',
        'auto_assign_new_hires',
        'effective_from',
        'effective_to',
        'archived_at',
        'archived_by',
        'created_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'requires_acknowledgement' => 'boolean',
        'is_active' => 'boolean',
        'auto_assign_new_hires' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'archived_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(OnboardingDocumentAssignment::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function fileUrl(): ?string
    {
        if (! filled($this->file_path)) {
            return null;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->file_path);
    }
}
