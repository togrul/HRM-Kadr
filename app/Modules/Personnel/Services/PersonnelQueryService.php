<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\PersonnelPresenceResolver;
use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;
use App\Services\StructureScope;
use App\Support\PositionLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PersonnelQueryService
{
    public const SORT_POSITION = 'position';

    public const SORT_STRUCTURE = 'structure';

    /**
     * Build personnel listing query with eager loads, filters and ordering.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $presence  today's resolved statuses to keep (empty = any)
     */
    public function build(
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        StructureScope|array $accessibleStructureIds,
        ?int $selectedPosition = null,
        ?string $search = null,
        string $sort = self::SORT_POSITION,
        array $presence = [],
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
            ->with($this->listingRelations($status));

        $this->applySharedScopes(
            query: $query,
            status: $status,
            filters: $filters,
            selectedStructureIds: $selectedStructureIds,
            accessibleStructureIds: $accessibleStructureIds,
            selectedPosition: $selectedPosition,
            search: $search,
        );

        if ($presence !== []) {
            app(PersonnelPresenceResolver::class)->constrainToStatuses($query, $presence);
        }

        // Senior posts first by the hidden positions.level band; unclassified posts last.
        $seniority = fn (Builder $q): Builder => $q
            ->orderByRaw('COALESCE(position_sort.level, ?)', [PositionLevel::UNKNOWN])
            ->orderByDesc('position_sort.approval_rank');

        // ponytail: units order by tree depth then code, not a full depth-first walk;
        // fine for flat org charts, use StructurePathResolver paths if trees get deep.
        $unit = fn (Builder $q): Builder => $q
            ->orderBy('structure_sort.level')
            ->orderBy('structure_sort.code')
            ->orderBy('structure_sort.id');

        $sort === self::SORT_STRUCTURE
            ? $seniority($unit($query))
            : $unit($seniority($query));

        return $query->orderBy('personnels.surname')->orderBy('personnels.name');
    }

    /**
     * Every status tally the listing panel and header show, in one grouped query — the panel
     * renders on each keystroke of the structure tree, so five COUNTs would be five
     * round trips per render.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function statusCounts(
        array $filters,
        array $selectedStructureIds,
        StructureScope|array $accessibleStructureIds,
        ?int $selectedPosition = null,
        ?string $search = null,
    ): array {
        $query = Personnel::query()->withTrashed();

        $this->applyStructureScope($query, $selectedStructureIds, $accessibleStructureIds);

        $query
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

        // Today's presence of the active employees comes from the shared resolver, folded
        // into this same aggregate so the whole panel stays one query.
        [$presenceColumns, $presenceBindings] = app(PersonnelPresenceResolver::class)->countColumns(
            [PersonnelPresenceStatus::AtWork, PersonnelPresenceStatus::Vacation],
            scopeSql: $active,
        );

        $row = $query->selectRaw(implode(', ', [
            "SUM(CASE WHEN {$live} THEN 1 ELSE 0 END) as all_count",
            "SUM(CASE WHEN {$active} THEN 1 ELSE 0 END) as current_count",
            "SUM(CASE WHEN {$settled} AND personnels.leave_work_date IS NOT NULL THEN 1 ELSE 0 END) as leaves_count",
            "SUM(CASE WHEN {$live} AND personnels.is_pending = 1 THEN 1 ELSE 0 END) as pending_count",
            'SUM(CASE WHEN personnels.deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count',
            ...$presenceColumns,
        ]), $presenceBindings)->toBase()->first();

        return [
            'all' => (int) ($row->all_count ?? 0),
            'current' => (int) ($row->current_count ?? 0),
            'leaves' => (int) ($row->leaves_count ?? 0),
            'pending' => (int) ($row->pending_count ?? 0),
            'deleted' => (int) ($row->deleted_count ?? 0),
            'on_vacation' => (int) ($row->presence_vacation ?? 0),
            'at_work' => (int) ($row->presence_at_work ?? 0),
        ];
    }

    /**
     * Lightweight export query without list-only eager loads and sort joins.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     * @return Builder<Personnel>
     */
    public function buildExport(
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        StructureScope|array $accessibleStructureIds,
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
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     * @param  array<string, mixed>  $filters
     */
    protected function applySharedScopes(
        Builder $query,
        ?string $status,
        array $filters,
        array $selectedStructureIds,
        StructureScope|array $accessibleStructureIds,
        ?int $selectedPosition = null,
        ?string $search = null,
    ): void {
        $this->applyStructureScope($query, $selectedStructureIds, $accessibleStructureIds);

        $query
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
     * Command-palette lookup: every word must match one of the quick-search columns, so
     * "Məmmədov Elçin" finds the person; current employees come before those who left.
     *
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     * @return Collection<int, Personnel>
     */
    public function quickFind(string $term, StructureScope|array $accessibleStructureIds, int $limit = 8): Collection
    {
        $words = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $scope = $this->toScope($accessibleStructureIds);

        if ($words === [] || $scope->isNone()) {
            return collect();
        }

        $query = Personnel::query()
            ->select(['personnels.id', 'personnels.tabel_no', 'personnels.surname', 'personnels.name', 'personnels.patronymic', 'personnels.position_id', 'personnels.leave_work_date'])
            ->with('position:id,name')
            ->where('personnels.is_pending', false);

        $scope->constrain($query, 'personnels.structure_id');

        foreach (array_slice($words, 0, 4) as $word) {
            $this->applyQuickSearch($query, $word);
        }

        return $query
            ->orderByRaw('personnels.leave_work_date is not null')
            ->orderBy('personnels.surname')
            ->orderBy('personnels.name')
            ->limit($limit)
            ->get();
    }

    /**
     * Müştərinin seçdiyi struktur filtri HƏMİŞƏ görünürlüklə kəsişdirilir: seçilmiş, amma
     * görünməyən struktur heç nə qaytarmır; boş görünürlük də heç nə qaytarmır.
     *
     * @param  array<int, int>  $selectedStructureIds
     * @param  StructureScope|array<int, int>  $accessibleStructureIds
     */
    protected function applyStructureScope(Builder $query, array $selectedStructureIds, StructureScope|array $accessibleStructureIds): void
    {
        $scope = $this->toScope($accessibleStructureIds);

        if ($selectedStructureIds !== []) {
            $selected = array_map('intval', $selectedStructureIds);
            $scope = StructureScope::of($scope->isAll() ? $selected : array_intersect($selected, $scope->ids()));
        }

        $scope->constrain($query, 'personnels.structure_id');
    }

    /**
     * @param  StructureScope|array<int, int>  $accessible
     */
    private function toScope(StructureScope|array $accessible): StructureScope
    {
        return $accessible instanceof StructureScope ? $accessible : StructureScope::of($accessible);
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
