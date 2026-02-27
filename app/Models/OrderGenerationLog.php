<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderGenerationLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'render_id',
        'order_log_id',
        'order_type_id',
        'order_template_version_id',
        'status',
        'duration_ms',
        'output_path',
        'error_message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'duration_ms' => 'int',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function orderLog(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class);
    }

    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateVersion::class, 'order_template_version_id');
    }
}
