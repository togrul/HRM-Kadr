<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A persisted block-based order template for the redesigned engine: its ordered
 * blocks (and derived field schema) stored as JSON. Authored by the designer,
 * loaded by the composer.
 */
class OrderBlockTemplate extends Model
{
    protected $fillable = [
        'code',
        'label',
        'blocks',
        'fields',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'blocks' => 'array',
        'fields' => 'array',
        'is_active' => 'boolean',
    ];
}
