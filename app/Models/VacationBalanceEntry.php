<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş ili balansının bir hərəkəti. days: müsbət — balansa əlavə (açılış qalığı, geri çağırma),
 * mənfi — balansdan çıxma (istifadə, kompensasiya).
 *
 * @property int $id
 * @property int $work_year_id
 * @property string $tabel_no
 * @property string $kind
 * @property int $days
 * @property string|null $source
 * @property string|null $note
 * @property int|null $created_by
 */
class VacationBalanceEntry extends Model
{
    public const KIND_OPENING = 'opening';

    public const KIND_USAGE = 'usage';

    public const KIND_RECALL = 'recall';

    public const KIND_COMPENSATION = 'compensation';

    public const KIND_ADJUSTMENT = 'adjustment';

    public const KIND_LEGACY_USAGE = 'legacy_usage';

    protected $fillable = [
        'work_year_id',
        'tabel_no',
        'kind',
        'days',
        'source',
        'note',
        'created_by',
    ];

    protected $casts = [
        'work_year_id' => 'integer',
        'days' => 'integer',
        'created_by' => 'integer',
    ];

    public function workYear(): BelongsTo
    {
        return $this->belongsTo(VacationWorkYear::class, 'work_year_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
