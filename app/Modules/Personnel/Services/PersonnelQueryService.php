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
        bool $withStructureTree = true,
        ?string $search = null
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
            search: $search,
        );

        return $query
            ->orderBy('position_sort.name')
            ->orderBy('structure_sort.name');
    }

    /**
     * Every status tally the listing panel and header show, in one grouped query — the panel
     * renders on each keystroke of the structure tree, so five COUNTs would be five
     * round trips per render.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function statusCounts(
        array $filters,
        array $selectedStructureIds,
        array $accessibleStructureIds,
        ?int $selectedPosition = null,
        ?string $search = null,
    ): array {
        $query = Personnel::query()->withTrashed();

        $query
            ->when(! empty($selectedStructureIds), function (Builder $builder) use ($selectedStructureIds) {
                $builder->whereIn('personnels.structure_id', $selectedStructureIds);
            }, function (Builder $builder) use ($accessibleStructureIds) {
                $builder->whereIn('personnels.structure_id', $accessibleStructureIds);
            })
            ->when(! empty($selectedPosition), function (Builder $builder) use ($selectedPosition) {
                $builder->where('personnels.position_id', $selectedPosition);
            });

        if (! empty($filters)) {
            $query->filter($filters);
        }

        $this->applyQuickSearch($query, $search);

        $live = 'personnels.deleted_at IS NULL';
        $settled = "{$live} AND personnels.is_pending = 0";
        $active = "{$settled} AND personnels.leave_work_date IS NULL";

        // Mirrors the hasActiveVacation relation, inlined so the whole panel is one query.
        $onVacation = 'EXISTS (SELECT 1 FROM personnel_vacations pv'
            .' WHERE pv.tabel_no = personnels.tabel_no'
            .' AND pv.deleted_at IS NULL'
            .' AND pv.start_date <= ? AND pv.return_work_date > ?)';

        $now = now()->toDateTimeString();

        $row = $query->selectRaw(implode(', ', [
            "SUM(CASE WHEN {$live} THEN 1 ELSE 0 END) as all_count",
            "SUM(CASE WHEN {$active} THEN 1 ELSE 0 END) as current_count",
            "SUM(CASE WHEN {$settled} AND personnels.leave_work_date IS NOT NULL THEN 1 ELSE 0 END) as leaves_count",
            "SUM(CASE WHEN {$live} AND personnels.is_pending = 1 THEN 1 ELSE 0 END) as pending_count",
            'SUM(CASE WHEN personnels.deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count',
            "SUM(CASE WHEN {$active} AND {$onVacation} THEN 1 ELSE 0 END) as on_vacation_count",
        ]), [$now, $now])->toBase()->first();

        $current = (int) ($row->current_count ?? 0);
        $onVacationCount = (int) ($row->on_vacation_count ?? 0);

        return [
            'all' => (int) ($row->all_count ?? 0),
            'current' => $current,
            'leaves' => (int) ($row->leaves_count ?? 0),
            'pending' => (int) ($row->pending_count ?? 0),
            'deleted' => (int) ($row->deleted_count ?? 0),
            'on_vacation' => $onVacationCount,
            'at_work' => max(0, $current - $onVacationCount),
        ];
    }

    /**
     * Lightweight export query without list-only eager loads and sort joins.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  array<int, int>  $accessibleStructureIds
     * @return Builder<Personnel>
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
            'hasActiveVacation',
            'latestBusinessTrip',
            'hasActiveBusinessTrip',
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
        ?string $search = null,
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

        $this->applyQuickSearch($query, $search);
    }

    /**
     * Toolbar search: one term matched against name, surname, patronymic,
     * tabel number or PIN.
     */
    protected function applyQuickSearch(Builder $query, ?string $search): void
    {
        $term = trim((string) $search);

        if ($term === '') {
            return;
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        $query->where(function (Builder $nested) use ($escaped): void {
            foreach (['surname', 'name', 'patronymic', 'tabel_no', 'pin'] as $field) {
                $nested->orWhere("personnels.{$field}", 'like', "%{$escaped}%");
            }
        });
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
