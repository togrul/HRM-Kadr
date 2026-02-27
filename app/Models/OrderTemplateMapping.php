<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTemplateMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_template_version_id',
        'placeholder',
        'field_key',
        'scope',
        'sort_order',
        'mapping_config',
    ];

    protected $casts = [
        'mapping_config' => 'array',
    ];

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateVersion::class, 'order_template_version_id');
    }
}
