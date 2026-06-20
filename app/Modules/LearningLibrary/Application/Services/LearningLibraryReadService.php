<?php

namespace App\Modules\LearningLibrary\Application\Services;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Support\Library\AbstractLibraryReadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LearningLibraryReadService extends AbstractLibraryReadService
{
    public function exportAssets(string $assetSearch = ''): array
    {
        return $this->assetsQuery($assetSearch)
            ->get()
            ->map(fn (EmployeeContentAsset $asset): array => [
                'title' => $asset->title,
                'type' => __('personnel::my_hr.learning.content_types.'.$asset->content_type),
                'version' => $asset->version ?: '1.0',
                'required' => $asset->is_required ? __('learning-library::dashboard.values.yes') : __('learning-library::dashboard.values.no'),
                'is_active' => $asset->is_active ? __('learning-library::dashboard.values.yes') : __('learning-library::dashboard.values.no'),
                'is_archived' => $asset->archived_at ? __('learning-library::dashboard.values.yes') : __('learning-library::dashboard.values.no'),
                'auto_assign_new_hires' => $asset->auto_assign_new_hires ? __('learning-library::dashboard.values.yes') : __('learning-library::dashboard.values.no'),
                'estimated_minutes' => $asset->estimated_minutes ?: '—',
                'assignments_count' => (int) $asset->assignments_count,
                'completed_assignments_count' => (int) $asset->completed_assignments_count,
                'overdue_assignments_count' => (int) $asset->overdue_assignments_count,
            ])
            ->values()
            ->all();
    }

    public function exportAssignments(): array
    {
        return $this->recentAssignmentRowsQuery()
            ->get()
            ->map(fn (object $row): array => [
                'asset' => $row->asset_title ?: '—',
                'type' => __('personnel::my_hr.learning.content_types.'.($row->content_type ?: 'other')),
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'assigned_at' => $this->formatDateTime($row->assigned_at),
                'status' => __('personnel::my_hr.learning.status.'.$row->status),
                'completed_at' => $this->formatDateTime($row->completed_at),
            ])
            ->values()
            ->all();
    }

    public function exportOverdueAssignments(): array
    {
        return $this->assignmentRowsBaseQuery()
            ->where(function (Builder $query): void {
                $query->where('employee_content_assignments.status', 'overdue')
                    ->orWhere(function (Builder $deep): void {
                        $deep->whereNotNull('employee_content_assignments.due_at')
                            ->where('employee_content_assignments.due_at', '<', now()->toDateString())
                            ->whereNull('employee_content_views.completed_at');
                    });
            })
            ->orderByDesc('employee_content_assignments.due_at')
            ->get()
            ->map(fn (object $row): array => [
                'asset' => $row->asset_title ?: '—',
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'due_at' => $this->formatDate($row->due_at),
                'status' => __('personnel::my_hr.learning.status.'.$row->status),
            ])
            ->values()
            ->all();
    }

    public function exportCompletedAssignments(): array
    {
        return $this->assignmentRowsBaseQuery()
            ->whereNotNull('employee_content_views.completed_at')
            ->orderByDesc('employee_content_views.completed_at')
            ->get()
            ->map(fn (object $row): array => [
                'asset' => $row->asset_title ?: '—',
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'assigned_at' => $this->formatDateTime($row->assigned_at),
                'completed_at' => $this->formatDateTime($row->completed_at),
            ])
            ->values()
            ->all();
    }

    public function exportVersionHistory(string $assetSearch = ''): array
    {
        return $this->assetsQuery($assetSearch)
            ->get()
            ->map(fn (EmployeeContentAsset $asset): array => [
                'title' => $asset->title,
                'version' => $asset->version ?: '1.0',
                'family_key' => $asset->version_family_key ?: '—',
                'previous_version_id' => $asset->previous_version_id ?: '—',
                'archived' => $asset->archived_at ? __('learning-library::dashboard.values.yes') : __('learning-library::dashboard.values.no'),
                'visibility' => __('personnel::my_hr.learning_admin.visibility.'.($asset->visibility ?: 'internal')),
                'estimated_minutes' => $asset->estimated_minutes ?: '—',
            ])
            ->values()
            ->all();
    }

    protected function libraryCachePrefix(): string
    {
        return 'learning-library';
    }

    protected function summaryData(): array
    {
        return Cache::remember('learning-library:summary', now()->addMinutes(2), function (): array {
            $dueSoonCutoff = now()->addDays(7)->toDateString();

            $assetSummary = EmployeeContentAsset::query()
                ->selectRaw('COUNT(*) as asset_total')
                ->selectRaw('SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as required_assets')
                ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_assets')
                ->selectRaw('SUM(CASE WHEN auto_assign_new_hires = 1 THEN 1 ELSE 0 END) as auto_assign_assets')
                ->selectRaw('SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_assets')
                ->first();

            $assignmentSummary = EmployeeContentAssignment::query()
                ->leftJoin('employee_content_views', 'employee_content_views.assignment_id', '=', 'employee_content_assignments.id')
                ->selectRaw('COUNT(employee_content_assignments.id) as active_assignments')
                ->selectRaw('SUM(CASE WHEN employee_content_views.completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_assignments')
                ->selectRaw("SUM(CASE WHEN employee_content_assignments.status = 'overdue' OR (employee_content_assignments.due_at IS NOT NULL AND employee_content_assignments.due_at < ? AND employee_content_views.completed_at IS NULL) THEN 1 ELSE 0 END) as overdue_assignments", [now()->toDateString()])
                ->selectRaw("SUM(CASE WHEN employee_content_assignments.due_at IS NOT NULL AND employee_content_assignments.due_at >= ? AND employee_content_assignments.due_at <= ? AND employee_content_assignments.status IN ('assigned','opened','overdue') THEN 1 ELSE 0 END) as due_soon_assignments", [now()->toDateString(), $dueSoonCutoff])
                ->first();

            return [
                'asset_total' => (int) ($assetSummary?->asset_total ?? 0),
                'required_assets' => (int) ($assetSummary?->required_assets ?? 0),
                'active_assets' => (int) ($assetSummary?->active_assets ?? 0),
                'auto_assign_assets' => (int) ($assetSummary?->auto_assign_assets ?? 0),
                'active_assignments' => (int) ($assignmentSummary?->active_assignments ?? 0),
                'completed_assignments' => (int) ($assignmentSummary?->completed_assignments ?? 0),
                'overdue_assignments' => (int) ($assignmentSummary?->overdue_assignments ?? 0),
                'archived_assets' => (int) ($assetSummary?->archived_assets ?? 0),
                'due_soon_assignments' => (int) ($assignmentSummary?->due_soon_assignments ?? 0),
            ];
        });
    }

    protected function analyticsData(): array
    {
        return Cache::remember('learning-library:analytics', now()->addMinutes(2), function (): array {
            $typeBreakdown = EmployeeContentAsset::query()
                ->select('content_type', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('content_type')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn (object $row): array => [
                    'label' => __('personnel::my_hr.learning.content_types.'.$row->content_type),
                    'count' => (int) $row->aggregate,
                ])
                ->all();

            $statusBreakdown = EmployeeContentAssignment::query()
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('status')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn (object $row): array => [
                    'label' => __('personnel::my_hr.learning.status.'.$row->status),
                    'count' => (int) $row->aggregate,
                ])
                ->all();

            $topStructures = EmployeeContentAssignment::query()
                ->join('personnels', 'personnels.id', '=', 'employee_content_assignments.personnel_id')
                ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
                ->selectRaw('COALESCE(structures.name, ?) as label, COUNT(*) as aggregate', ['—'])
                ->groupBy('structures.name')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(fn (object $row): array => ['label' => $row->label, 'count' => (int) $row->aggregate])
                ->all();

            $topPositions = EmployeeContentAssignment::query()
                ->join('personnels', 'personnels.id', '=', 'employee_content_assignments.personnel_id')
                ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
                ->selectRaw('COALESCE(positions.name, ?) as label, COUNT(*) as aggregate', ['—'])
                ->groupBy('positions.name')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(fn (object $row): array => ['label' => $row->label, 'count' => (int) $row->aggregate])
                ->all();

            $versionedFamilies = (int) EmployeeContentAsset::query()
                ->whereNotNull('version_family_key')
                ->select('version_family_key')
                ->groupBy('version_family_key')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            return [
                'type_breakdown' => $typeBreakdown,
                'status_breakdown' => $statusBreakdown,
                'top_structures' => $topStructures,
                'top_positions' => $topPositions,
                'versioned_families' => $versionedFamilies,
            ];
        });
    }

    private function assetsQuery(string $assetSearch = ''): Builder
    {
        return EmployeeContentAsset::query()
            ->withCount([
                'assignments',
                'assignments as completed_assignments_count' => fn (Builder $query) => $query
                    ->whereHas('view', fn (Builder $view) => $view->whereNotNull('completed_at')),
                'assignments as overdue_assignments_count' => fn (Builder $query) => $query
                    ->where(function (Builder $nested): void {
                        $nested->where('status', 'overdue')
                            ->orWhere(function (Builder $deep): void {
                                $deep->whereNotNull('due_at')
                                    ->where('due_at', '<', now()->toDateString())
                                    ->whereDoesntHave('view', fn (Builder $view) => $view->whereNotNull('completed_at'));
                            });
                    }),
            ])
            ->when($assetSearch !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$assetSearch.'%'))
            ->latest('created_at')
            ->limit(10);
    }

    private function mapAssets(Collection $assets): array
    {
        $previousVersions = EmployeeContentAsset::query()
            ->whereIn('id', $assets->pluck('previous_version_id')->filter()->values())
            ->get(['id', 'title', 'version', 'content_type', 'visibility', 'is_required', 'estimated_minutes', 'auto_assign_new_hires'])
            ->keyBy('id');

        $familyCounts = EmployeeContentAsset::query()
            ->whereIn('version_family_key', $assets->pluck('version_family_key')->filter()->values())
            ->select('version_family_key', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('version_family_key')
            ->pluck('aggregate', 'version_family_key');

        return $assets->map(fn (EmployeeContentAsset $asset): array => [
            'id' => $asset->id,
            'title' => $asset->title,
            'type' => __('personnel::my_hr.learning.content_types.'.$asset->content_type),
            'required' => (bool) $asset->is_required,
            'is_active' => (bool) $asset->is_active,
            'is_archived' => $asset->archived_at !== null,
            'archived_at' => $this->formatDateTime($asset->archived_at),
            'version' => $asset->version ?: '1.0',
            'previous_version_label' => $previousVersions->get($asset->previous_version_id)?->version,
            'version_family_count' => (int) ($familyCounts[$asset->version_family_key] ?? 1),
            'compare_summary' => $this->assetCompareSummary($asset, $previousVersions->get($asset->previous_version_id)),
            'auto_assign_new_hires' => (bool) $asset->auto_assign_new_hires,
            'estimated_minutes' => $asset->estimated_minutes,
            'assignments_count' => (int) $asset->assignments_count,
            'completed_assignments_count' => (int) $asset->completed_assignments_count,
            'overdue_assignments_count' => (int) $asset->overdue_assignments_count,
            'content_url' => $asset->contentUrl(),
            'toggle_active_label' => $asset->is_active
                ? __('learning-library::dashboard.actions.deactivate_asset')
                : __('learning-library::dashboard.actions.activate_asset'),
        ])->all();
    }

    protected function assignmentItems(): array
    {
        return EmployeeContentAsset::query()
            ->select(['id', 'title', 'content_type'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (EmployeeContentAsset $asset): array => [
                'id' => $asset->id,
                'title' => $asset->title,
                'type' => __('personnel::my_hr.learning.content_types.'.$asset->content_type),
            ])
            ->all();
    }

    protected function recentAssignmentsData(): array
    {
        return $this->recentAssignmentRowsQuery()
            ->get()
            ->map(fn (object $row): array => $this->mapRecentAssignmentRow($row))
            ->all();
    }

    protected function recentAssignmentsPaginatedData(int $perPage, string $pageName): LengthAwarePaginator
    {
        $paginator = $this->assignmentRowsBaseQuery()
            ->latest('employee_content_assignments.assigned_at')
            ->paginate($perPage, ['*'], $pageName);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (object $row): array => $this->mapRecentAssignmentRow($row))
        );

        return $paginator;
    }

    protected function libraryItems(string $librarySearch): array
    {
        return $this->mapAssets($this->assetsQuery($librarySearch)->get());
    }

    protected function libraryItemsKey(): string
    {
        return 'assets';
    }

    protected function assignmentItemsKey(): string
    {
        return 'assignment_assets';
    }

    private function recentAssignmentRowsQuery(): Builder
    {
        return $this->assignmentRowsBaseQuery()
            ->latest('employee_content_assignments.assigned_at')
            ->limit(12);
    }

    private function assignmentRowsBaseQuery(): Builder
    {
        return EmployeeContentAssignment::query()
            ->leftJoin('employee_content_assets', 'employee_content_assets.id', '=', 'employee_content_assignments.asset_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'employee_content_assignments.personnel_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('employee_content_views', 'employee_content_views.assignment_id', '=', 'employee_content_assignments.id')
            ->select([
                'employee_content_assignments.id',
                'employee_content_assignments.status',
                'employee_content_assignments.assigned_at',
                'employee_content_assignments.due_at',
                DB::raw('COALESCE(employee_content_assets.title, "—") as asset_title'),
                DB::raw('COALESCE(employee_content_assets.content_type, "other") as content_type'),
                DB::raw("TRIM(CONCAT_WS(' ', personnels.surname, personnels.name, personnels.patronymic)) as personnel_fullname"),
                DB::raw('COALESCE(positions.name, "—") as position_name'),
                'employee_content_views.completed_at',
            ]);
    }

    private function assetCompareSummary(EmployeeContentAsset $current, ?EmployeeContentAsset $previous): array
    {
        if (! $previous) {
            return [];
        }

        $changes = [];

        if ((string) $current->content_type !== (string) $previous->content_type) {
            $changes[] = __('learning-library::dashboard.compare.content_type');
        }

        if ((string) $current->visibility !== (string) $previous->visibility) {
            $changes[] = __('learning-library::dashboard.compare.visibility');
        }

        if ((bool) $current->is_required !== (bool) $previous->is_required) {
            $changes[] = __('learning-library::dashboard.compare.required');
        }

        if ((bool) $current->auto_assign_new_hires !== (bool) $previous->auto_assign_new_hires) {
            $changes[] = __('learning-library::dashboard.compare.auto_assign');
        }

        if ((int) ($current->estimated_minutes ?? 0) !== (int) ($previous->estimated_minutes ?? 0)) {
            $changes[] = __('learning-library::dashboard.compare.duration');
        }

        return $changes;
    }

    private function resolveAssignmentStatus(object $row): string
    {
        if (filled($row->completed_at)) {
            return 'completed';
        }

        if ($row->status === 'overdue') {
            return 'overdue';
        }

        return $row->status ?: 'assigned';
    }

    private function mapRecentAssignmentRow(object $row): array
    {
        $status = $this->resolveAssignmentStatus($row);

        return [
            'id' => (int) $row->id,
            'asset' => trim(($row->asset_title ?: '—').' · '.__('personnel::my_hr.learning.content_types.'.($row->content_type ?: 'other'))),
            'personnel' => $row->personnel_fullname ?: '—',
            'position' => $row->position_name ?: '—',
            'assigned_at' => $this->formatDateTime($row->assigned_at),
            'status' => __('personnel::my_hr.learning.status.'.$status),
            'status_mode' => match ($status) {
                'completed' => 'emerald',
                'overdue' => 'rose',
                'opened' => 'sky',
                default => 'muted',
            },
            'completed_at' => $this->formatDateTime($row->completed_at),
        ];
    }
}
