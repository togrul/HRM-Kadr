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

        return Personnel::query()
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
            ->with($relations)
            ->when($withStructureTree, fn (Builder $query) => $query->withStructureTree())
            ->when(! empty($selectedStructureIds), function (Builder $query) use ($selectedStructureIds) {
                $query->whereIn('personnels.structure_id', $selectedStructureIds);
            }, function (Builder $query) use ($accessibleStructureIds) {
                $query->whereIn('personnels.structure_id', $accessibleStructureIds);
            })
            ->when(! empty($selectedPosition), function (Builder $query) use ($selectedPosition) {
                $query->where('personnels.position_id', $selectedPosition);
            })
            ->when($status, function (Builder $query) use ($status) {
                switch ($status) {
                    case 'current':
                        $query->whereNull('personnels.leave_work_date');
                        break;
                    case 'leaves':
                        $query->whereNotNull('personnels.leave_work_date');
                        break;
                    case 'deleted':
                        $query->onlyTrashed();
                        break;
                    case 'pending':
                        $query->where('personnels.is_pending', true);
                        break;
                    default:
                        $query->where('personnels.is_pending', false);
                }
            })
            ->when(! empty($filters), fn (Builder $q) => $q->filter($filters))
            ->orderBy('position_sort.name')
            ->orderBy('structure_sort.name');
    }
}
