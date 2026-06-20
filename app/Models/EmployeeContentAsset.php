<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class EmployeeContentAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content_type',
        'description',
        'version',
        'version_family_key',
        'previous_version_id',
        'storage_disk',
        'storage_path',
        'external_url',
        'thumbnail_path',
        'visibility',
        'is_active',
        'auto_assign_new_hires',
        'is_required',
        'estimated_minutes',
        'archived_at',
        'archived_by',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_assign_new_hires' => 'boolean',
        'is_required' => 'boolean',
        'estimated_minutes' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeContentAssignment::class, 'asset_id');
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

    public function contentUrl(): ?string
    {
        if (filled($this->external_url)) {
            return $this->external_url;
        }

        if (! filled($this->storage_path)) {
            return null;
        }

        return Storage::disk($this->storage_disk ?: 'employee_content')->url($this->storage_path);
    }
}
