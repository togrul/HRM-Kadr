<?php

namespace App\Modules\Staff\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Position;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Modules\Staff\Application\Services\StaffHeadcountService;
use App\Modules\Staff\Exports\VacancyExport;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[On(['staffAdded', 'staffWasDeleted'])]
class Staffs extends Component
{
    use AuthorizesRequests;
    use NestedStructureTrait;
    use SideModalAction;
    use WithPagination;

    public $structure;

    public ?int $selectedStructureId = null;

    #[Url]
    public $selectedPage;

    #[Locked]
    public array $accessibleStructureIds = [];

    /**
     * Structure ids whose children are rendered. `null` until the first render seeds it
     * with the shallow levels — the whole chart used to ship on every render, and that
     * cost grows with the org, not with the page.
     *
     * @var list<int>|null
     */
    public ?array $openNodes = null;

    /** Unit / position name filter for the tree; a match opens every branch that leads to it. */
    public string $search = '';

    /** "Yalnız vakant olanlar": keep only branches that still have an open slot. */
    public bool $onlyVacant = false;

    /** Nodes this deep (and shallower) come with the page; anything below opens on demand. */
    private const TREE_EAGER_DEPTH = 2;

    protected array $structureTitleCache = [];

    protected ?array $structureMap = null;

    protected function queryString(): array
    {
        return [
            'structure' => [
                'compact' => ',',
            ],
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('export', StaffSchedule::class);

        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        $prefix = $this->selectedPage === 'vacancies' ? 'vakansiyalar' : 'stat-cedveli';

        return Excel::download(new VacancyExport($report), "{$prefix}-{$name}.xlsx");
    }

    public function showPage($page): void
    {
        $this->selectedPage = $page;
    }

    #[On('selectStructure')]
    public function selectStructure(mixed $payload = null): void
    {
        $id = $this->resolveSelectStructureId($payload);

        if ($id === null) {
            return;
        }

        $this->selectedStructureId = $id;
        $this->structure = $this->getNestedStructure($id);
        $this->resetPage();
    }

    /**
     * Drop the structure scope. Without this the panel tree, which only renders the
     * selected branch, would be a one-way trip.
     */
    public function clearStructure(): void
    {
        $this->selectedStructureId = null;
        $this->structure = null;
        $this->resetPage();
    }

    protected function resolveSelectStructureId(mixed $payload): ?int
    {
        if (is_array($payload)) {
            if (array_key_exists('id', $payload)) {
                $payload = $payload['id'];
            } elseif (! empty($payload) && array_is_list($payload)) {
                $payload = $payload[0];
            }
        }

        if (! is_numeric($payload)) {
            return null;
        }

        $id = (int) $payload;

        return $id > 0 ? $id : null;
    }

    public function setDeleteStaff($staffId): void
    {
        $this->dispatch('setDeleteStaff', $staffId);
    }

    /**
     * Open the add-staff modal pre-targeted at a specific structure (the tree's
     * "Vəzifə əlavə et" per-node action).
     */
    public function addStaffFor(int $structureId): void
    {
        $this->authorize('add-staff', StaffSchedule::class);
        $this->selectedStructureId = $structureId;
        $this->openSideMenu('add-staff');
    }

    public function mount(StructureService $structureService): void
    {
        $this->authorize('viewAny', StaffSchedule::class);
        $this->selectedPage = request()->query('selectedPage', 'all');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    protected function returnData($type = 'normal'): array|Collection
    {
        if ($type === 'normal') {
            return Cache::remember($this->staffListCacheKey(), now()->addSeconds(10), fn () => $this->buildStaffRows());
        }

        $result = $this->buildStaffRows(raw: true);

        return $result->toArray();
    }

    protected function buildStaffRows(bool $raw = false): Collection
    {
        $result = $this->staffSnapshot()['rows'];

        if ($this->selectedPage === 'vacancies') {
            $result = $result
                ->filter(fn ($row) => (int) ($row->vacant ?? 0) > 0)
                ->values();
        }

        if ($raw) {
            return $result;
        }

        return $this->selectedPage === 'all'
            ? $this->buildStructureGroups($result)
            : $result;
    }

    /**
     * Every ştat row in scope with its live Dolu / Vakant / Artıq, plus the people who work
     * in a (structure, position) the ştat has no row for. Two queries for the figures: the
     * rows and one grouped headcount.
     *
     * @return array{rows: Collection<int, StaffSchedule>, offStaff: array<int, array<int, int>>}
     */
    protected function staffSnapshot(): array
    {
        // URL-dən gələn struktur filtri görünürlüklə kəsişdirilir — görünməyən struktur heç nə vermir.
        $accessible = array_map('intval', $this->accessibleStructureIds);
        $scopeIds = ! empty($this->structure)
            ? array_values(array_intersect(array_unique(array_map('intval', (array) $this->structure)), $accessible))
            : array_values(array_unique($accessible));

        $rows = StaffSchedule::with([
            'position:id,name',
            'structure:id,parent_id,name',
        ])
            ->whereIn('structure_id', $scopeIds)
            ->orderBy('structure_id')
            ->orderBy('id')
            ->get();

        $headcount = app(StaffHeadcountService::class);
        $counts = $headcount->activeCounts($scopeIds);
        $headcount->hydrate($rows, $counts);

        return ['rows' => $rows, 'offStaff' => $headcount->offStaff($rows, $counts)];
    }

    protected function staffListCacheKey(): string
    {
        return 'staff:list:'.md5(json_encode([
            'selected_page' => $this->selectedPage,
            'structure' => $this->structure,
            'accessible' => $this->accessibleStructureIds,
        ]));
    }

    protected function buildStructureGroups($rows): Collection
    {
        return $rows
            ->groupBy('structure_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();
                $structure = $first?->structure;
                $structureId = (int) ($first?->structure_id ?? 0);
                $structureMap = $this->resolveStructureMap();
                $parentId = $structure?->parent_id ?? ($structureMap[$structureId]['parent_id'] ?? null);

                return [
                    'title' => $this->resolveStructureTitle($structureId),
                    'structure_id' => $structureId,
                    'has_parent' => ! empty($parentId),
                    'total_sum' => $groupRows->sum('total'),
                    'total_filled' => $groupRows->sum('filled'),
                    'total_vacant' => $groupRows->sum('vacant'),
                    'items' => $groupRows,
                ];
            })
            ->values();
    }

    protected function resolveStructureTitle(int $structureId): string
    {
        if ($structureId <= 0) {
            return '';
        }

        $cacheKey = $structureId;

        if (array_key_exists($cacheKey, $this->structureTitleCache)) {
            return $this->structureTitleCache[$cacheKey];
        }

        $structureMap = $this->resolveStructureMap();
        $cursor = $structureId;
        $segments = [];

        while ($cursor > 0 && isset($structureMap[$cursor])) {
            $meta = $structureMap[$cursor];

            // Hide only structures whose own parent_id is null (root node).
            if ($meta['parent_id'] !== null) {
                $segments[] = (string) $meta['name'];
            }

            $cursor = (int) ($meta['parent_id'] ?? 0);
        }

        if (empty($segments)) {
            return $this->structureTitleCache[$cacheKey] = '';
        }

        return $this->structureTitleCache[$cacheKey] = implode(' / ', array_reverse($segments));
    }

    protected function resolveStructureMap(): array
    {
        if ($this->structureMap !== null) {
            return $this->structureMap;
        }

        $this->structureMap = Structure::query()
            ->select('id', 'parent_id', 'name', 'level')
            ->get()
            ->reduce(function (array $carry, Structure $structure) {
                $carry[(int) $structure->id] = [
                    'parent_id' => $structure->parent_id ? (int) $structure->parent_id : null,
                    'name' => (string) $structure->name,
                    'level' => (int) ($structure->level ?? 0),
                ];

                return $carry;
            }, []);

        return $this->structureMap;
    }

    /**
     * Build the nested structure → position tree for the "all" view: every structure that
     * has ştat rows or off-staff people (or a descendant with either) becomes a node,
     * parented per the structure map. Each node's aggregate is its own rows plus all
     * descendants; vacancies and over-staffing are summed per row, never netted.
     * Display roots are the top of the accessible/selected scope.
     *
     * @return array{tree: array<int,array<string,mixed>>, ids: array<int,int>}
     */
    protected function buildStructureTree(): array
    {
        ['rows' => $rows, 'offStaff' => $offStaff] = $this->staffSnapshot();
        if ($rows->isEmpty() && $offStaff === []) {
            return ['tree' => [], 'ids' => []];
        }

        $map = $this->resolveStructureMap();
        $positionsByStructure = $rows->groupBy(fn ($row) => (int) $row->structure_id);
        $offStaffPositionNames = $this->offStaffPositionNames($offStaff);

        // Included = every structure with rows or off-staff people plus all of its ancestors.
        $included = [];
        foreach ([...$positionsByStructure->keys()->all(), ...array_keys($offStaff)] as $structureId) {
            $cursor = (int) $structureId;
            while ($cursor > 0 && isset($map[$cursor]) && ! isset($included[$cursor])) {
                $included[$cursor] = true;
                $cursor = (int) ($map[$cursor]['parent_id'] ?? 0);
            }
        }

        // Children index (name-sorted) among included structures.
        $childrenByParent = [];
        foreach (array_keys($included) as $structureId) {
            $parentId = (int) ($map[$structureId]['parent_id'] ?? 0);
            $childrenByParent[$parentId][] = $structureId;
        }
        foreach ($childrenByParent as &$siblings) {
            usort($siblings, fn ($a, $b) => strcmp($map[$a]['name'] ?? '', $map[$b]['name'] ?? ''));
        }
        unset($siblings);

        $ids = [];
        $unassignedTitle = __('staff::common.fields.position_unassigned');
        $noPositionTitle = __('staff::common.fields.no_position');

        $build = function (int $structureId) use (&$build, $map, $positionsByStructure, $offStaff, $offStaffPositionNames, $childrenByParent, &$ids, $unassignedTitle, $noPositionTitle) {
            $ids[] = $structureId;
            $meta = $map[$structureId];

            $positions = collect($positionsByStructure->get($structureId, []))
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'kind' => $row->unassigned ? 'unassigned' : 'row',
                    'title' => $row->unassigned ? $unassignedTitle : (string) ($row->position?->name ?? $unassignedTitle),
                    'structure_id' => (int) $row->structure_id,
                    'position_id' => (int) ($row->position_id ?? 0),
                    'total' => (int) ($row->total ?? 0),
                    'filled' => (int) ($row->filled ?? 0),
                    'vacant' => (int) ($row->vacant ?? 0),
                    'over' => (int) ($row->over ?? 0),
                ])
                ->values()
                ->all();

            $offStaffEntries = [];
            foreach ($offStaff[$structureId] ?? [] as $positionId => $count) {
                $offStaffEntries[] = [
                    'id' => 0,
                    'kind' => 'off_staff',
                    'title' => $positionId > 0 ? (string) ($offStaffPositionNames[$positionId] ?? $noPositionTitle) : $noPositionTitle,
                    'structure_id' => $structureId,
                    'position_id' => (int) $positionId,
                    'total' => 0,
                    'filled' => (int) $count,
                    'vacant' => 0,
                    'over' => 0,
                ];
            }

            $children = [];
            foreach ($childrenByParent[$structureId] ?? [] as $childId) {
                $children[] = $build((int) $childId);
            }

            $agg = [
                'total' => array_sum(array_column($positions, 'total')),
                'filled' => array_sum(array_column($positions, 'filled')),
                'vacant' => array_sum(array_column($positions, 'vacant')),
                'over' => array_sum(array_column($positions, 'over')),
                'off_staff' => array_sum(array_column($offStaffEntries, 'filled')),
                'unassigned' => count(array_filter($positions, fn (array $p): bool => $p['kind'] === 'unassigned')),
                'covered' => array_sum(array_map(fn (array $p): int => min($p['filled'], $p['total']), $positions)),
            ];
            foreach ($children as $child) {
                foreach (array_keys($agg) as $key) {
                    $agg[$key] += (int) $child['agg'][$key];
                }
            }
            $agg['rate'] = $agg['total'] > 0 ? (int) round($agg['covered'] / $agg['total'] * 100) : 0;

            return [
                'id' => $structureId,
                'name' => (string) $meta['name'],
                'level' => (int) ($meta['level'] ?? 0),
                'positions' => $positions,
                'off_staff' => $offStaffEntries,
                'children' => $children,
                'agg' => $agg,
            ];
        };

        // Display roots: included structures whose parent is outside the included set.
        $rootIds = [];
        foreach (array_keys($included) as $structureId) {
            $parentId = (int) ($map[$structureId]['parent_id'] ?? 0);
            if (! isset($included[$parentId])) {
                $rootIds[] = $structureId;
            }
        }
        usort($rootIds, fn ($a, $b) => strcmp($map[$a]['name'] ?? '', $map[$b]['name'] ?? ''));

        $tree = array_map(fn ($id) => $build((int) $id), $rootIds);

        return ['tree' => $tree, 'ids' => $ids];
    }

    /**
     * @param  array<int, array<int, int>>  $offStaff
     * @return array<int, string>
     */
    protected function offStaffPositionNames(array $offStaff): array
    {
        $positionIds = collect($offStaff)
            ->flatMap(fn (array $byPosition): array => array_keys($byPosition))
            ->filter(fn ($id): bool => (int) $id > 0)
            ->unique()
            ->values()
            ->all();

        return $positionIds === []
            ? []
            : Position::query()->whereIn('id', $positionIds)->pluck('name', 'id')->map(fn ($name): string => (string) $name)->all();
    }

    public function toggleNode(int $id): void
    {
        $open = $this->openNodes ?? [];

        $this->openNodes = in_array($id, $open, true)
            ? array_values(array_diff($open, [$id]))
            : [...$open, $id];
    }

    public function expandAllNodes(): void
    {
        ['ids' => $ids] = $this->cachedStructureTree();

        $this->openNodes = array_values(array_map('intval', $ids));
    }

    public function collapseAllNodes(): void
    {
        $this->openNodes = [];
    }

    /**
     * The rows the page opens with: enough of the chart to read at a glance, without
     * paying for every unit under every department.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return list<int>
     */
    protected function defaultOpenNodes(array $tree, int $depth = 0): array
    {
        if ($depth >= self::TREE_EAGER_DEPTH) {
            return [];
        }

        $ids = [];

        foreach ($tree as $node) {
            $ids[] = (int) $node['id'];
            $ids = [...$ids, ...$this->defaultOpenNodes($node['children'], $depth + 1)];
        }

        return $ids;
    }

    /**
     * @return array{tree: array<int,array<string,mixed>>, ids: array<int,int>}
     */
    protected function cachedStructureTree(): array
    {
        return Cache::remember(
            'staff:tree:'.md5(json_encode([$this->structure, $this->accessibleStructureIds])),
            now()->addSeconds(10),
            fn () => $this->buildStructureTree(),
        );
    }

    public function render(): View
    {
        if ($this->selectedPage === 'all') {
            ['tree' => $staffTree, 'ids' => $staffTreeIds] = $this->cachedStructureTree();

            $this->openNodes ??= $this->defaultOpenNodes($staffTree);

            $search = trim($this->search);
            $visibleTree = $this->filterTree($staffTree, $search, $this->onlyVacant);

            return view('staff::livewire.staff-schedule.staffs', [
                'staffs' => collect(),
                'staffTree' => $staffTree,
                'visibleTree' => $visibleTree,
                // A search result is useless folded away, so every surviving branch opens.
                'treeOpenIds' => $search !== '' ? $this->collectTreeIds($visibleTree) : $this->openNodes,
                'treeSearch' => $search,
                'staffTreeIds' => $staffTreeIds,
                'staffAllOpen' => count($this->openNodes) >= count($staffTreeIds),
                'staffSummary' => $this->summarizeTree($staffTree),
            ]);
        }

        return view('staff::livewire.staff-schedule.staffs', [
            'staffs' => $this->returnData(),
            'staffTree' => [],
            'visibleTree' => [],
            'treeOpenIds' => [],
            'treeSearch' => '',
            'staffTreeIds' => [],
            'staffAllOpen' => false,
            'staffSummary' => $this->summarizeTree([]),
        ]);
    }

    /**
     * Narrow the (cached) tree in memory — no query. A node survives when its name matches
     * (then its whole subtree stays), when one of its positions matches (only those
     * positions stay), or when a descendant survives. `onlyVacant` drops every branch
     * and position without an open slot. Aggregates keep describing the whole unit.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, array<string, mixed>>
     */
    /**
     * Azerbaijani-aware lower case: İ→i and I→ı, one character each, so offsets in the
     * folded string still point at the same characters of the original.
     */
    public static function foldCase(string $text): string
    {
        return mb_strtolower(strtr($text, ['İ' => 'i', 'I' => 'ı']));
    }

    public function filterTree(array $tree, string $search, bool $onlyVacant): array
    {
        if ($search === '' && ! $onlyVacant) {
            return $tree;
        }

        $needle = self::foldCase($search);
        $matches = fn (string $text): bool => $needle === '' || mb_strpos(self::foldCase($text), $needle) !== false;
        $kept = [];

        foreach ($tree as $node) {
            if ($onlyVacant && (int) $node['agg']['vacant'] <= 0) {
                continue;
            }

            $positions = $onlyVacant
                ? array_values(array_filter($node['positions'], fn (array $p): bool => (int) $p['vacant'] > 0))
                : $node['positions'];

            // Off-staff people hold no slot, so a "vacant only" view has nothing to show for them.
            $offStaff = $onlyVacant ? [] : ($node['off_staff'] ?? []);

            if ($search !== '' && $matches($node['name'])) {
                $kept[] = [...$node, 'positions' => $positions, 'off_staff' => $offStaff, 'children' => $this->filterTree($node['children'], '', $onlyVacant)];

                continue;
            }

            $positions = array_values(array_filter($positions, fn (array $p): bool => $matches($p['title'])));
            $offStaff = array_values(array_filter($offStaff, fn (array $p): bool => $matches($p['title'])));
            $children = $this->filterTree($node['children'], $search, $onlyVacant);

            if ($positions !== [] || $offStaff !== [] || $children !== []) {
                $kept[] = [...$node, 'positions' => $positions, 'off_staff' => $offStaff, 'children' => $children];
            }
        }

        return $kept;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return list<int>
     */
    protected function collectTreeIds(array $tree): array
    {
        $ids = [];

        foreach ($tree as $node) {
            $ids = [...$ids, (int) $node['id'], ...$this->collectTreeIds($node['children'])];
        }

        return $ids;
    }

    /**
     * Roll the display roots up into one headline figure for the panel. The roots do not
     * overlap — each node's aggregate already includes its whole subtree — so summing
     * them is the establishment total, with no extra query. Vakant is the sum of per-row
     * vacancies (an over-staffed unit never cancels another unit's opening) and Doluluq
     * only credits a row up to its own total.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return array{total: int, filled: int, vacant: int, over: int, off_staff: int, unassigned: int, rate: float}
     */
    protected function summarizeTree(array $tree): array
    {
        $summary = ['total' => 0, 'filled' => 0, 'vacant' => 0, 'over' => 0, 'off_staff' => 0, 'unassigned' => 0, 'covered' => 0];

        foreach ($tree as $node) {
            foreach (array_keys($summary) as $key) {
                $summary[$key] += (int) ($node['agg'][$key] ?? 0);
            }
        }

        $covered = $summary['covered'];
        unset($summary['covered']);

        return [
            ...$summary,
            'rate' => $summary['total'] > 0 ? round($covered / $summary['total'] * 100, 1) : 0.0,
        ];
    }
}
