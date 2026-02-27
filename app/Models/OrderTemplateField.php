<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_template_version_id',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'sort_order',
        'default_value',
        'data_source',
        'ui_config',
        'transform_config',
        'validation_config',
    ];

    protected $casts = [
        'is_required' => 'bool',
        'data_source' => 'array',
        'ui_config' => 'array',
        'transform_config' => 'array',
        'validation_config' => 'array',
    ];

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateVersion::class, 'order_template_version_id');
    }
}
