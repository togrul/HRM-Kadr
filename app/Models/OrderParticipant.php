<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee of a multi-participant (çoxşəxsli) Word-engine order, in document order.
 * `fields` holds this person's own values for the template's per-participant fields (and
 * any overrides of shared ones); `effect_state` what the approval effect did for them, so
 * the reversal undoes exactly that.
 *
 * @property int $order_log_id
 * @property int $personnel_id
 * @property int $position
 * @property array<string,mixed>|null $fields
 * @property array<string,mixed>|null $effect_state
 */
class OrderParticipant extends Model
{
    protected $fillable = [
        'order_log_id',
        'personnel_id',
        'position',
        'fields',
        'effect_state',
    ];

    protected $casts = [
        'fields' => 'array',
        'effect_state' => 'array',
        'position' => 'integer',
        'personnel_id' => 'integer',
    ];

    /** @return BelongsTo<OrderLog, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'order_log_id');
    }

    /** @return BelongsTo<Personnel, $this> */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
