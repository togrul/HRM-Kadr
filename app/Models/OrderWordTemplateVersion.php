<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An archived past version of a Word template's master (.docx) + variable mapping,
 * captured when the designer re-uploads a new file for an existing order type.
 *
 * @property int $version
 * @property string $docx_path
 * @property array<int,array<string,mixed>> $variables
 */
class OrderWordTemplateVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_word_template_id',
        'version',
        'label',
        'effect',
        'docx_path',
        'variables',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'created_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OrderWordTemplate::class, 'order_word_template_id');
    }
}
