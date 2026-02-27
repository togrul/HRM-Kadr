<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_template_set_id',
        'version_no',
        'template_name',
        'template_path',
        'checksum',
        'status',
        'is_active',
        'published_at',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    public function templateSet(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateSet::class, 'order_template_set_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(OrderTemplateField::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(OrderTemplateMapping::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(OrderTemplateVersionAudit::class);
    }

    public function generationLogs(): HasMany
    {
        return $this->hasMany(OrderGenerationLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
