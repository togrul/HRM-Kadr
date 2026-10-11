<?php

namespace App\Modules\EmployeeRelations\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * İş növü (redaktə edilə bilən soraqça).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int|null $investigation_sla_days
 * @property bool $is_disciplinary
 * @property bool $is_active
 * @property int $sort_order
 */
class RelationCaseType extends Model
{
    protected $table = 'employee_relation_case_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'investigation_sla_days',
        'is_disciplinary',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'investigation_sla_days' => 'integer',
            'is_disciplinary' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<RelationCase, $this>
     */
    public function cases(): HasMany
    {
        return $this->hasMany(RelationCase::class, 'case_type_id');
    }
}
