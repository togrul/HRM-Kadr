<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Builder;

class PersonnelQueryService
{
    /**
     * Build personnel listing query with eager loads, filters and ordering.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     */
    public function build(
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        array $accessibleStructureIds,
        ?int $selectedPosition = null,
        bool $withStructureTree = true
    ): Builder {
        $query = Personnel::query()
            ->select([
                'personnels.id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'personnels.photo',
                'personnels.gender',
                'personnels.structure_id',
                'personnels.position_id',
                'personnels.join_work_date',
                'personnels.leave_work_date',
                'personnels.is_pending',
                'personnels.deleted_at',
                'personnels.deleted_by',
            ])
            ->leftJoin('positions as position_sort', 'position_sort.id', '=', 'personnels.position_id')
            ->leftJoin('structures as structure_sort', 'structure_sort.id', '=', 'personnels.structure_id')
            ->with($this->listingRelations($status))
            ->when($withStructureTree, fn (Builder $builder) => $builder->withStructureTree());

        $this->applySharedScopes(
            query: $query,
            status: $status,
            filters: $filters,
            selectedStructureIds: $selectedStructureIds,
            accessibleStructureIds: $accessibleStructureIds,
            selectedPosition: $selectedPosition,
        );

        return $query
            ->orderBy('position_sort.name')
            ->orderBy('structure_sort.name');
    }

    /**
     * Lightweight export query without list-only eager loads and sort joins.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  array<int, int>  $accessibleStructureIds
     */
    public function buildExport(
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        array $accessibleStructureIds,
        ?int $selectedPosition = null
    ): Builder {
        $query = Personnel::query()
            ->select([
                'personnels.id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'personnels.structure_id',
                'personnels.position_id',
                'personnels.leave_work_date',
                'personnels.is_pending',
                'personnels.deleted_at',
            ]);

        $this->applySharedScopes(
            query: $query,
            status: $status,
            filters: $filters,
            selectedStructureIds: $selectedStructureIds,
            accessibleStructureIds: $accessibleStructureIds,
            selectedPosition: $selectedPosition,
        );

        return $query
            ->orderBy('personnels.surname')
            ->orderBy('personnels.name')
            ->orderBy('personnels.patronymic')
            ->orderBy('personnels.tabel_no');
    }

    /**
     * @return array<int, string>
     */
    protected function listingRelations(?string $status): array
    {
        $locale = app()->getLocale();

        $relations = [
            'latestRank',
            "latestRank.rank:id,name_{$locale}",
            'latestVacation',
            'latestBusinessTrip',
            'position:id,name',
            'currentWork',
            'latestDisposal',
        ];

        if ($status === 'deleted') {
            $relations[] = 'personDidDelete:id,name';
        }

        return $relations;
    }

    /**
     * @param  array<int, int>  $selectedStructureIds
     * @param  array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     */
    protected function applySharedScopes(
        Builder $query,
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        array $accessibleStructureIds,
        ?int $selectedPosition = null,
    ): void {
        $query
            ->when(! empty($selectedStructureIds), function (Builder $builder) use ($selectedStructureIds) {
                $builder->whereIn('personnels.structure_id', $selectedStructureIds);
            }, function (Builder $builder) use ($accessibleStructureIds) {
                $builder->whereIn('personnels.structure_id', $accessibleStructureIds);
            })
            ->when(! empty($selectedPosition), function (Builder $builder) use ($selectedPosition) {
                $builder->where('personnels.position_id', $selectedPosition);
            });

        $this->applyStatusScope($query, $status);

        if (! empty($filters)) {
            $query->filter($filters);
        }
    }

    protected function applyStatusScope(Builder $query, ?string $status): void
    {
        switch ($status) {
            case 'current':
                $query
                    ->whereNull('personnels.leave_work_date')
                    ->where('personnels.is_pending', false);
                break;
            case 'leaves':
                $query
                    ->whereNotNull('personnels.leave_work_date')
                    ->where('personnels.is_pending', false);
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
            case 'pending':
                $query->where('personnels.is_pending', true);
                break;
            case 'all':
            case null:
            case '':
                break;
            default:
                $query->where('personnels.is_pending', false);
        }
    }
}
