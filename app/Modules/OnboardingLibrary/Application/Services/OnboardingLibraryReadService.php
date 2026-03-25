<?php

namespace App\Modules\OnboardingLibrary\Application\Services;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Support\Library\BuildsLibraryDirectoryPayload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OnboardingLibraryReadService
{
    use BuildsLibraryDirectoryPayload;

    public function build(string $templateSearch = '', string $personnelSearch = '', string $structureSearch = '', string $positionSearch = ''): array
    {
        return [
            'summary' => $this->summary(),
            'analytics' => $this->analytics(),
            'templates' => $this->mapTemplates($this->templatesQuery($templateSearch)->get()),
            'assignment_templates' => $this->assignmentTemplates(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignments(),
        ];
    }

    public function buildGeneral(
        string $personnelSearch = '',
        string $structureSearch = '',
        string $positionSearch = '',
        string $pageName = 'onboardingRecentAssignmentsPage'
    ): array {
        return [
            'summary' => $this->summary(),
            'assignment_templates' => $this->assignmentTemplates(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignmentsPaginated(10, $pageName),
        ];
    }

    public function buildSummary(): array
    {
        return [
            'summary' => $this->summary(),
        ];
    }

    public function buildLibrary(string $templateSearch = ''): array
    {
        return [
            'templates' => $this->mapTemplates($this->templatesQuery($templateSearch)->get()),
        ];
    }

    public function buildReports(): array
    {
        return [
            'analytics' => $this->analytics(),
        ];
    }

    public function exportTemplates(string $templateSearch = ''): array
    {
        return $this->templatesQuery($templateSearch)
            ->get()
            ->map(fn (OnboardingDocumentTemplate $template): array => [
                'title' => $template->title,
                'type' => __('personnel::my_hr.onboarding.document_types.'.$template->document_type),
                'version' => $template->version,
                'is_active' => $template->is_active ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no'),
                'is_archived' => $template->archived_at ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no'),
                'auto_assign_new_hires' => $template->auto_assign_new_hires ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no'),
                'required' => $template->is_required ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no'),
                'assignments_count' => (int) $template->assignments_count,
                'acknowledged_assignments_count' => (int) $template->acknowledged_assignments_count,
                'overdue_assignments_count' => (int) $template->overdue_assignments_count,
            ])
            ->values()
            ->all();
    }

    public function exportAssignments(): array
    {
        return $this->recentAssignmentRowsQuery()
            ->get()
            ->map(fn (object $row): array => [
                'template' => $row->template_title ?: '—',
                'version' => $row->template_version ?: '—',
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'assigned_at' => $this->formatDateTime($row->assigned_at),
                'status' => __('personnel::my_hr.onboarding.status.'.$row->status),
                'acknowledged_at' => $this->formatDateTime($row->acknowledged_at),
            ])
            ->values()
            ->all();
    }

    public function exportOverdueAssignments(): array
    {
        return $this->assignmentRowsBaseQuery()
            ->where(function (Builder $query): void {
                $query->where('onboarding_document_assignments.status', 'overdue')
                    ->orWhere(function (Builder $deep): void {
                        $deep->whereNotNull('onboarding_document_assignments.due_at')
                            ->where('onboarding_document_assignments.due_at', '<', now()->toDateString())
                            ->whereNull('onboarding_document_receipts.acknowledged_at');
                    });
            })
            ->orderByDesc('onboarding_document_assignments.due_at')
            ->get()
            ->map(fn (object $row): array => [
                'template' => $row->template_title ?: '—',
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'due_at' => $this->formatDate($row->due_at),
                'status' => __('personnel::my_hr.onboarding.status.'.$row->status),
            ])
            ->values()
            ->all();
    }

    public function exportAcknowledgedAssignments(): array
    {
        return $this->assignmentRowsBaseQuery()
            ->whereNotNull('onboarding_document_receipts.acknowledged_at')
            ->orderByDesc('onboarding_document_receipts.acknowledged_at')
            ->get()
            ->map(fn (object $row): array => [
                'template' => $row->template_title ?: '—',
                'personnel' => $row->personnel_fullname ?: '—',
                'position' => $row->position_name ?: '—',
                'assigned_at' => $this->formatDateTime($row->assigned_at),
                'acknowledged_at' => $this->formatDateTime($row->acknowledged_at),
            ])
            ->values()
            ->all();
    }

    public function exportVersionHistory(string $templateSearch = ''): array
    {
        return $this->templatesQuery($templateSearch)
            ->get()
            ->map(fn (OnboardingDocumentTemplate $template): array => [
                'title' => $template->title,
                'version' => $template->version,
                'family_key' => $template->version_family_key ?: '—',
                'previous_version_id' => $template->previous_version_id ?: '—',
                'archived' => $template->archived_at ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no'),
                'effective_from' => $this->formatDate($template->effective_from),
                'effective_to' => $this->formatDate($template->effective_to),
            ])
            ->values()
            ->all();
    }

    protected function libraryCachePrefix(): string
    {
        return 'onboarding-library';
    }

    private function summary(): array
    {
        return Cache::remember('onboarding-library:summary', now()->addMinutes(2), function (): array {
            $dueSoonCutoff = now()->addDays(7)->toDateString();

            $templateSummary = OnboardingDocumentTemplate::query()
                ->selectRaw('COUNT(*) as template_total')
                ->selectRaw('SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as required_templates')
                ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_templates')
                ->selectRaw('SUM(CASE WHEN auto_assign_new_hires = 1 THEN 1 ELSE 0 END) as auto_assign_templates')
                ->selectRaw('SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_templates')
                ->first();

            $assignmentSummary = OnboardingDocumentAssignment::query()
                ->leftJoin('onboarding_document_receipts', 'onboarding_document_receipts.assignment_id', '=', 'onboarding_document_assignments.id')
                ->selectRaw('COUNT(onboarding_document_assignments.id) as active_assignments')
                ->selectRaw('SUM(CASE WHEN onboarding_document_receipts.acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) as acknowledged_assignments')
                ->selectRaw("SUM(CASE WHEN onboarding_document_assignments.status = 'overdue' OR (onboarding_document_assignments.due_at IS NOT NULL AND onboarding_document_assignments.due_at < ? AND onboarding_document_receipts.acknowledged_at IS NULL) THEN 1 ELSE 0 END) as overdue_assignments", [now()->toDateString()])
                ->selectRaw("SUM(CASE WHEN onboarding_document_assignments.due_at IS NOT NULL AND onboarding_document_assignments.due_at >= ? AND onboarding_document_assignments.due_at <= ? AND onboarding_document_assignments.status IN ('assigned','opened','overdue') THEN 1 ELSE 0 END) as due_soon_assignments", [now()->toDateString(), $dueSoonCutoff])
                ->first();

            return [
                'template_total' => (int) ($templateSummary?->template_total ?? 0),
                'required_templates' => (int) ($templateSummary?->required_templates ?? 0),
                'active_templates' => (int) ($templateSummary?->active_templates ?? 0),
                'auto_assign_templates' => (int) ($templateSummary?->auto_assign_templates ?? 0),
                'active_assignments' => (int) ($assignmentSummary?->active_assignments ?? 0),
                'overdue_assignments' => (int) ($assignmentSummary?->overdue_assignments ?? 0),
                'acknowledged_assignments' => (int) ($assignmentSummary?->acknowledged_assignments ?? 0),
                'archived_templates' => (int) ($templateSummary?->archived_templates ?? 0),
                'due_soon_assignments' => (int) ($assignmentSummary?->due_soon_assignments ?? 0),
            ];
        });
    }

    private function analytics(): array
    {
        return Cache::remember('onboarding-library:analytics', now()->addMinutes(2), function (): array {
            $typeBreakdown = OnboardingDocumentTemplate::query()
                ->select('document_type', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('document_type')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn (object $row): array => [
                    'label' => __('personnel::my_hr.onboarding.document_types.'.$row->document_type),
                    'count' => (int) $row->aggregate,
                ])
                ->all();

            $statusBreakdown = OnboardingDocumentAssignment::query()
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('status')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn (object $row): array => [
                    'label' => __('personnel::my_hr.onboarding.status.'.$row->status),
                    'count' => (int) $row->aggregate,
                ])
                ->all();

            $topStructures = OnboardingDocumentAssignment::query()
                ->join('personnels', 'personnels.id', '=', 'onboarding_document_assignments.personnel_id')
                ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
                ->selectRaw('COALESCE(structures.name, ?) as label, COUNT(*) as aggregate', ['—'])
                ->groupBy('structures.name')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(fn (object $row): array => ['label' => $row->label, 'count' => (int) $row->aggregate])
                ->all();

            $topPositions = OnboardingDocumentAssignment::query()
                ->join('personnels', 'personnels.id', '=', 'onboarding_document_assignments.personnel_id')
                ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
                ->selectRaw('COALESCE(positions.name, ?) as label, COUNT(*) as aggregate', ['—'])
                ->groupBy('positions.name')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(fn (object $row): array => ['label' => $row->label, 'count' => (int) $row->aggregate])
                ->all();

            $versionedFamilies = (int) OnboardingDocumentTemplate::query()
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

    private function templatesQuery(string $templateSearch = ''): Builder
    {
        return OnboardingDocumentTemplate::query()
            ->withCount([
                'assignments',
                'assignments as acknowledged_assignments_count' => fn (Builder $query) => $query
                    ->whereHas('receipt', fn (Builder $receipt) => $receipt->whereNotNull('acknowledged_at')),
                'assignments as overdue_assignments_count' => fn (Builder $query) => $query
                    ->where(function (Builder $nested): void {
                        $nested->where('status', 'overdue')
                            ->orWhere(function (Builder $deep): void {
                                $deep->whereNotNull('due_at')
                                    ->where('due_at', '<', now()->toDateString())
                                    ->whereDoesntHave('receipt', fn (Builder $receipt) => $receipt->whereNotNull('acknowledged_at'));
                            });
                    }),
            ])
            ->when($templateSearch !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$templateSearch.'%'))
            ->latest('created_at')
            ->limit(10);
    }

    private function mapTemplates(Collection $templates): array
    {
        $previousVersions = OnboardingDocumentTemplate::query()
            ->whereIn('id', $templates->pluck('previous_version_id')->filter()->values())
            ->get(['id', 'title', 'version', 'document_type', 'is_required', 'requires_acknowledgement', 'auto_assign_new_hires', 'effective_from', 'effective_to'])
            ->keyBy('id');

        $familyCounts = OnboardingDocumentTemplate::query()
            ->whereIn('version_family_key', $templates->pluck('version_family_key')->filter()->values())
            ->select('version_family_key', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('version_family_key')
            ->pluck('aggregate', 'version_family_key');

        return $templates->map(fn (OnboardingDocumentTemplate $template): array => [
            'id' => $template->id,
            'title' => $template->title,
            'type' => __('personnel::my_hr.onboarding.document_types.'.$template->document_type),
            'version' => $template->version,
            'required' => (bool) $template->is_required,
            'is_active' => (bool) $template->is_active,
            'auto_assign_new_hires' => (bool) $template->auto_assign_new_hires,
            'assignments_count' => (int) $template->assignments_count,
            'acknowledged_assignments_count' => (int) $template->acknowledged_assignments_count,
            'overdue_assignments_count' => (int) $template->overdue_assignments_count,
            'file_url' => $template->fileUrl(),
            'is_archived' => $template->archived_at !== null,
            'archived_at' => $this->formatDateTime($template->archived_at),
            'previous_version_label' => $previousVersions->get($template->previous_version_id)?->version,
            'version_family_count' => (int) ($familyCounts[$template->version_family_key] ?? 1),
            'compare_summary' => $this->templateCompareSummary($template, $previousVersions->get($template->previous_version_id)),
            'toggle_active_label' => $template->is_active
                ? __('onboarding-library::dashboard.actions.deactivate_template')
                : __('onboarding-library::dashboard.actions.activate_template'),
        ])->all();
    }

    public function assignmentTemplates(): array
    {
        return OnboardingDocumentTemplate::query()
            ->select(['id', 'title', 'version'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (OnboardingDocumentTemplate $template): array => [
                'id' => $template->id,
                'title' => $template->title,
                'version' => $template->version,
            ])
            ->all();
    }

    private function recentAssignments(): array
    {
        return $this->recentAssignmentRowsQuery()
            ->get()
            ->map(fn (object $row): array => $this->mapRecentAssignmentRow($row))
            ->all();
    }

    public function recentAssignmentsPaginated(int $perPage = 10, string $pageName = 'onboardingRecentAssignmentsPage'): LengthAwarePaginator
    {
        $paginator = $this->assignmentRowsBaseQuery()
            ->latest('onboarding_document_assignments.assigned_at')
            ->paginate($perPage, ['*'], $pageName);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (object $row): array => $this->mapRecentAssignmentRow($row))
        );

        return $paginator;
    }

    private function recentAssignmentRowsQuery(): Builder
    {
        return $this->assignmentRowsBaseQuery()
            ->latest('onboarding_document_assignments.assigned_at')
            ->limit(12);
    }

    private function assignmentRowsBaseQuery(): Builder
    {
        return OnboardingDocumentAssignment::query()
            ->leftJoin('onboarding_document_templates', 'onboarding_document_templates.id', '=', 'onboarding_document_assignments.template_id')
            ->leftJoin('personnels', 'personnels.id', '=', 'onboarding_document_assignments.personnel_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('onboarding_document_receipts', 'onboarding_document_receipts.assignment_id', '=', 'onboarding_document_assignments.id')
            ->select([
                'onboarding_document_assignments.id',
                'onboarding_document_assignments.status',
                'onboarding_document_assignments.assigned_at',
                'onboarding_document_assignments.due_at',
                DB::raw('COALESCE(onboarding_document_templates.title, "—") as template_title'),
                DB::raw('COALESCE(onboarding_document_templates.version, "—") as template_version'),
                DB::raw("TRIM(CONCAT_WS(' ', personnels.surname, personnels.name, personnels.patronymic)) as personnel_fullname"),
                DB::raw('COALESCE(positions.name, "—") as position_name'),
                'onboarding_document_receipts.acknowledged_at',
            ]);
    }

    private function templateCompareSummary(OnboardingDocumentTemplate $current, ?OnboardingDocumentTemplate $previous): array
    {
        if (! $previous) {
            return [];
        }

        $changes = [];

        if ((string) $current->document_type !== (string) $previous->document_type) {
            $changes[] = __('onboarding-library::dashboard.compare.document_type');
        }

        if ((bool) $current->is_required !== (bool) $previous->is_required) {
            $changes[] = __('onboarding-library::dashboard.compare.required');
        }

        if ((bool) $current->requires_acknowledgement !== (bool) $previous->requires_acknowledgement) {
            $changes[] = __('onboarding-library::dashboard.compare.acknowledgement');
        }

        if ((bool) $current->auto_assign_new_hires !== (bool) $previous->auto_assign_new_hires) {
            $changes[] = __('onboarding-library::dashboard.compare.auto_assign');
        }

        if ((string) optional($current->effective_from)?->toDateString() !== (string) optional($previous->effective_from)?->toDateString()
            || (string) optional($current->effective_to)?->toDateString() !== (string) optional($previous->effective_to)?->toDateString()) {
            $changes[] = __('onboarding-library::dashboard.compare.effective_window');
        }

        return $changes;
    }

    private function resolveAssignmentStatus(object $row): string
    {
        if (filled($row->acknowledged_at)) {
            return 'acknowledged';
        }

        if ($row->status === 'waived') {
            return 'waived';
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
            'template' => trim(($row->template_title ?: '—').' · v'.($row->template_version ?: '—')),
            'personnel' => $row->personnel_fullname ?: '—',
            'position' => $row->position_name ?: '—',
            'assigned_at' => $this->formatDateTime($row->assigned_at),
            'status' => __('personnel::my_hr.onboarding.status.'.$status),
            'status_mode' => match ($status) {
                'acknowledged' => 'emerald',
                'overdue' => 'rose',
                'opened' => 'sky',
                default => 'muted',
            },
            'acknowledged_at' => $this->formatDateTime($row->acknowledged_at),
        ];
    }
}
