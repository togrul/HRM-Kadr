<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query scopes and the dynamic filter machinery for the Personnel model.
 *
 * Extracted from the Personnel model with behavior unchanged.
 */
trait FiltersPersonnel
{
    protected $likeFilterFields = [
        'surname', 'name', 'patronymic', 'tabel_no', 'pin',
    ];

    public function scopeWithStructureTree($query): void
    {
        $query->with([
            'structure' => fn ($q) => $q
                ->select('id', 'parent_id', 'name')
                ->withRecursive('parent', false),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_pending', false)
            ->whereNull('leave_work_date');
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if ($this->filterValueIsEmpty($value)) {
                continue;
            }

            if (is_array($value)) {
                $this->applyRangeFilter($query, $field, $value);

                continue;
            }

            $this->applyExactFilter($query, $field, $value);
        }

        return $query;
    }

    public function scopeNameLike(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where($this->qualifiedColumn('name'), 'like', "%{$term}%")
                ->orWhere($this->qualifiedColumn('surname'), 'like', "%{$term}%");
        });
    }

    protected function applyRangeFilter($query, $field, array $value)
    {
        if ($field === 'age') {
            [$minAge, $maxAge] = $this->normalizeNumericRange($value, 0, 150);

            $query->whereRaw(
                'timestampdiff(year, '.$this->qualifiedColumn('birthdate').', curdate()) between ? and ?',
                [$minAge, $maxAge]
            );

            return;
        }

        if ($field === 'rank') {
            [$start, $end] = $this->normalizeDateRange($value);

            $query->whereHas('ranks', fn ($q) => $q->whereBetween('given_date', [$start, $end]));

            return;
        }

        if (in_array($field, $this->fillable, true)) {
            [$start, $end] = $this->normalizeDateRange($value);

            $query->whereBetween($this->qualifiedColumn($field), [$start, $end]);
        }
    }

    protected function applyExactFilter($query, $field, $value)
    {
        switch ($field) {
            case 'nationality_id':
                $query->whereHas('nationality', fn ($q) => $q->where('nationality_id', $value));
                break;

            case 'born_country_id':
            case 'born_city_id':
            case 'is_married':
                $query->whereHas('idDocuments', fn ($q) => $q->where($field, $value));
                break;

            case 'rank_id':
                $query->whereHas('ranks', fn ($q) => $q->where($field, $value));
                break;

            case 'rank_name':
                $query->whereHas('ranks', fn ($q) => $q->where('name', $value));
                break;

            case 'punishment_reason':
                $query->whereHas('punishments', fn ($q) => $q->where('reason', 'LIKE', "%$value%"));
                break;

            case 'educational_institution_id':
            case 'specialty':
                $query->whereHas('education', fn ($q) => $q->where($field, $value));
                break;

            case 'award_id':
                $query->whereHas('awards', fn ($q) => $q->where($field, $value));
                break;

            case 'punishment_id':
                $query->whereHas('punishments', fn ($q) => $q->where($field, $value));
                break;

            case 'structure_id':
                $this->applyStructureFilter($query, $value);
                break;

            default:
                if (in_array($field, $this->likeFilterFields, true) && $value !== null) {
                    $query->where($this->qualifiedColumn($field), 'LIKE', "%$value%");
                } elseif (in_array($field, $this->fillable, true) && $value !== null) {
                    $query->where($this->qualifiedColumn($field), $value);
                }
                break;
        }
    }

    protected function filterValueIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->filterValueIsEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_bool($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }

    protected function normalizeDateRange(array $value, ?string $defaultMin = null, ?string $defaultMax = null): array
    {
        $defaultMin ??= '1990-01-01';
        $defaultMax ??= Carbon::now()->format('Y-m-d');

        $min = $this->normalizeDateValue($value['min'] ?? null, $defaultMin);
        $max = $this->normalizeDateValue($value['max'] ?? null, $defaultMax);

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    protected function normalizeDateValue(?string $value, string $fallback): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function normalizeNumericRange(array $value, int $defaultMin, int $defaultMax): array
    {
        $min = array_key_exists('min', $value) && $value['min'] !== ''
            ? (int) $value['min']
            : $defaultMin;

        $max = array_key_exists('max', $value) && $value['max'] !== ''
            ? (int) $value['max']
            : $defaultMax;

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    protected function applyStructureFilter($query, $value)
    {
        $structureIds = $this->getNestedStructure($value);
        $query->whereIn($this->qualifiedColumn('structure_id'), $structureIds);
    }

    protected function qualifiedColumn(string $field): string
    {
        return str_contains($field, '.') ? $field : $this->getTable().'.'.$field;
    }
}
