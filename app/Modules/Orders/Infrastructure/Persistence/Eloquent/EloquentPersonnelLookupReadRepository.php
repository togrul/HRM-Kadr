<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\Candidate;
use App\Models\Personnel;
use App\Modules\Orders\Domain\Contracts\PersonnelLookupReadRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentPersonnelLookupReadRepository implements PersonnelLookupReadRepository
{
    public function activePersonnel(array $excludeIds, array $accessibleStructureIds, ?string $search, int $defaultLimit): Collection
    {
        $normalizedSearch = trim((string) $search);

        return Personnel::query()
            ->when($normalizedSearch !== '', function (Builder $query) use ($normalizedSearch): void {
                $this->applyMultiTermSearch($query, $normalizedSearch, [
                    'name',
                    'surname',
                    'patronymic',
                    'tabel_no',
                ]);
            })
            ->active()
            ->whereIn('structure_id', $accessibleStructureIds)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('position_id')
            ->orderBy('structure_id')
            ->when($normalizedSearch === '', fn (Builder $query) => $query->limit(max(1, $defaultLimit)))
            ->orderBy('surname')
            ->orderBy('name')
            ->get();
    }

    public function candidatePersonnelReady(array $excludeIds, ?string $search): Collection
    {
        $normalizedSearch = trim((string) $search);

        return Candidate::query()
            ->when($normalizedSearch !== '', function (Builder $query) use ($normalizedSearch): void {
                $this->applyMultiTermSearch($query, $normalizedSearch, [
                    'name',
                    'surname',
                    'patronymic',
                ], 'id');
            })
            ->whereNotIn('id', $excludeIds)
            ->where('status_id', 30)
            ->orderBy('surname')
            ->orderBy('name')
            ->get();
    }

    public function findCandidateNameParts(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Candidate::query()
            ->whereKey($id)
            ->first(['name', 'surname']);

        if (! $row) {
            return null;
        }

        return [
            'name' => (string) $row->name,
            'surname' => (string) $row->surname,
        ];
    }

    public function findPersonnelNameParts(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = Personnel::query()
            ->whereKey($id)
            ->first(['name', 'surname']);

        if (! $row) {
            return null;
        }

        return [
            'name' => (string) $row->name,
            'surname' => (string) $row->surname,
        ];
    }

    /**
     * @param  array<int,string>  $columns
     */
    private function applyMultiTermSearch(Builder $query, string $search, array $columns, ?string $numericColumn = null): void
    {
        $terms = collect(preg_split('/\s+/', $search))
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->values();

        foreach ($terms as $term) {
            $query->where(function (Builder $nested) use ($columns, $term, $numericColumn): void {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $nested->where($column, 'LIKE', "%{$term}%");
                        continue;
                    }

                    $nested->orWhere($column, 'LIKE', "%{$term}%");
                }

                if ($numericColumn !== null && ctype_digit($term)) {
                    $nested->orWhere($numericColumn, (int) $term);
                }
            });
        }
    }
}
