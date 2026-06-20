<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Word-upload order template: the author's MS Word document (normalized to a
 * ${token} master at $docx_path) plus the mapping of each detected [bracket]
 * placeholder to a data source. Authored by the designer, filled by the composer.
 *
 * @property string $code
 * @property string $label
 * @property string $effect
 * @property string $docx_path
 * @property array<int,array{token:string,label:string,source:string,auto_key:?string,field:?array{key:string,type:string},effect_role:?string}> $variables
 * @property bool $is_active
 */
class OrderWordTemplate extends Model
{
    protected $fillable = [
        'code',
        'label',
        'effect',
        'docx_path',
        'variables',
        'is_active',
        'created_by',
    ];

    protected $attributes = [
        'effect' => 'none',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /** A hire order operates on a candidate (not an existing employee). */
    public function isHire(): bool
    {
        return $this->effect === 'hire';
    }

    /** @return HasMany<OrderWordTemplateVersion> */
    public function versions(): HasMany
    {
        return $this->hasMany(OrderWordTemplateVersion::class)->orderByDesc('version');
    }

    /**
     * The placeholders the order author fills in per-order (source = manual),
     * shaped like the composer's existing field defs ({key,label,type}).
     *
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function manualFields(): array
    {
        $fields = [];
        foreach ($this->variables ?? [] as $variable) {
            if (($variable['source'] ?? 'manual') !== 'manual') {
                continue;
            }
            $field = $variable['field'] ?? null;
            if (! is_array($field) || empty($field['key'])) {
                continue;
            }
            $fields[$field['key']] = [
                'key' => $field['key'],
                'label' => $variable['label'] ?? $field['key'],
                'type' => $field['type'] ?? 'text',
            ];
        }

        // De-duplicate by field key (two placeholders may share one input).
        return array_values($fields);
    }
}
