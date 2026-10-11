<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Şahid: işçi kartına bağlı və ya kənar şəxs (yalnız ad).
 *
 * @property int $id
 * @property int $case_id
 * @property int|null $personnel_id
 * @property string|null $external_name
 * @property string|null $note
 * @property int|null $added_by
 * @property-read Personnel|null $personnel
 */
class RelationCaseWitness extends Model
{
    protected $table = 'employee_relation_case_witnesses';

    protected $fillable = ['case_id', 'personnel_id', 'external_name', 'note', 'added_by'];

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id')->withTrashed();
    }

    public function displayName(): string
    {
        return $this->personnel_id !== null ? trim($this->personnel->fullname) : (string) $this->external_name;
    }
}
