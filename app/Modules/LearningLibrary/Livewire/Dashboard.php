<?php

namespace App\Modules\LearningLibrary\Livewire;

use App\Models\EmployeeContentAsset;
use App\Modules\LearningLibrary\Application\Services\LearningLibraryReadService;
use App\Modules\Personnel\Contracts\LearningAssignmentManager;
use App\Support\Library\LibraryExportAction;
use App\Support\Livewire\AbstractLibraryDashboard;
use Livewire\Attributes\Computed;

class Dashboard extends AbstractLibraryDashboard
{
    public string $searchAsset = '';

    public ?int $versionSourceAssetId = null;

    public $assetUpload = null;

    public array $assetForm = [
        'title' => '',
        'content_type' => 'pdf',
        'version' => '1.0',
        'description' => '',
        'external_url' => '',
        'visibility' => 'internal',
        'is_active' => true,
        'auto_assign_new_hires' => false,
        'is_required' => false,
        'estimated_minutes' => null,
    ];

    public array $assignmentForm = [
        'asset_id' => null,
        'due_at' => null,
        'include_recent_hires' => false,
        'recent_hire_days' => 30,
    ];

    public function saveAsset(): void
    {
        abort_unless($this->canManageLibrary(), 403);

        $validated = $this->validate([
            'assetForm.title' => 'required|string|max:255',
            'assetForm.content_type' => 'required|in:video,presentation,pdf,link,other',
            'assetForm.version' => 'required|string|max:32',
            'assetForm.description' => 'nullable|string|max:5000',
            'assetForm.external_url' => 'nullable|url|max:2048',
            'assetForm.visibility' => 'required|in:internal,public',
            'assetForm.is_active' => 'boolean',
            'assetForm.auto_assign_new_hires' => 'boolean',
            'assetForm.is_required' => 'boolean',
            'assetForm.estimated_minutes' => 'nullable|integer|min:1|max:600',
            'assetUpload' => 'nullable|file|max:20480',
        ], attributes: [
            'assetForm.title' => __('learning-library::dashboard.fields.asset_title'),
            'assetForm.content_type' => __('learning-library::dashboard.fields.content_type'),
            'assetForm.version' => __('learning-library::dashboard.fields.version'),
            'assetForm.description' => __('learning-library::dashboard.fields.description'),
            'assetForm.external_url' => __('learning-library::dashboard.fields.external_url'),
            'assetForm.visibility' => __('learning-library::dashboard.fields.visibility'),
            'assetForm.is_active' => __('learning-library::dashboard.fields.is_active'),
            'assetForm.auto_assign_new_hires' => __('learning-library::dashboard.fields.auto_assign_new_hires'),
            'assetForm.is_required' => __('learning-library::dashboard.fields.is_required'),
            'assetForm.estimated_minutes' => __('learning-library::dashboard.fields.estimated_minutes'),
            'assetUpload' => __('learning-library::dashboard.fields.file'),
        ]);

        $sourceAsset = $this->versionSourceAssetId
            ? EmployeeContentAsset::query()->find($this->versionSourceAssetId)
            : null;

        $asset = app(LearningAssignmentManager::class)->createAsset(
            data_get($validated, 'assetForm', []),
            $this->assetUpload,
            auth()->user(),
            $sourceAsset
        );

        $this->assignmentForm['asset_id'] = $asset->id;
        $this->assetUpload = null;
        $this->assetForm = [
            'title' => '',
            'content_type' => 'pdf',
            'version' => '1.0',
            'description' => '',
            'external_url' => '',
            'visibility' => 'internal',
            'is_active' => true,
            'auto_assign_new_hires' => false,
            'is_required' => false,
            'estimated_minutes' => null,
        ];
        $this->versionSourceAssetId = null;

        $this->dispatch('notify', type: 'success', message: __('learning-library::dashboard.messages.asset_saved'));
    }

    public function assignSelected(): void
    {
        abort_unless($this->canAssignContent(), 403);

        $validated = $this->validate([
            'assignmentForm.asset_id' => 'required|exists:employee_content_assets,id',
            'assignmentForm.due_at' => 'nullable|date',
            'selectedPersonnelIds' => 'array',
            'selectedPersonnelIds.*' => 'integer|exists:personnels,id',
            'selectedStructureIds' => 'array',
            'selectedStructureIds.*' => 'integer|exists:structures,id',
            'selectedPositionIds' => 'array',
            'selectedPositionIds.*' => 'integer|exists:positions,id',
            'assignmentForm.include_recent_hires' => 'boolean',
            'assignmentForm.recent_hire_days' => 'nullable|integer|min:1|max:365',
        ], attributes: [
            'assignmentForm.asset_id' => __('learning-library::dashboard.fields.asset'),
            'assignmentForm.due_at' => __('learning-library::dashboard.fields.due_at'),
            'assignmentForm.include_recent_hires' => __('learning-library::dashboard.fields.include_recent_hires'),
            'assignmentForm.recent_hire_days' => __('learning-library::dashboard.fields.recent_hire_days'),
            'selectedPersonnelIds' => __('learning-library::dashboard.fields.target_people'),
            'selectedPersonnelIds.*' => __('learning-library::dashboard.fields.target_person'),
            'selectedStructureIds' => __('learning-library::dashboard.fields.target_structures'),
            'selectedStructureIds.*' => __('learning-library::dashboard.fields.target_structure'),
            'selectedPositionIds' => __('learning-library::dashboard.fields.target_positions'),
            'selectedPositionIds.*' => __('learning-library::dashboard.fields.target_position'),
        ]);

        if (! $this->ensureTargetsSelected(
            (bool) data_get($validated, 'assignmentForm.include_recent_hires', false),
            'selectedPersonnelIds',
            __('learning-library::dashboard.messages.target_required')
        )) {
            return;
        }

        $count = app(LearningAssignmentManager::class)->assignByTargets(
            data_get($validated, 'selectedPersonnelIds', []),
            data_get($validated, 'selectedStructureIds', []),
            data_get($validated, 'selectedPositionIds', []),
            (bool) data_get($validated, 'assignmentForm.include_recent_hires', false),
            data_get($validated, 'assignmentForm.recent_hire_days'),
            (int) data_get($validated, 'assignmentForm.asset_id'),
            data_get($validated, 'assignmentForm.due_at'),
            auth()->user()
        );

        $this->dispatch('notify', type: 'success', message: __('learning-library::dashboard.messages.assignment_saved', ['count' => $count]));
    }

    public function toggleAssetActive(int $assetId): void
    {
        abort_unless($this->canManageLibrary(), 403);

        $asset = \App\Models\EmployeeContentAsset::query()->findOrFail($assetId);
        app(LearningAssignmentManager::class)->toggleAssetActive($asset);

        unset($this->payload);
        $this->dispatch('notify', type: 'success', message: __('learning-library::dashboard.messages.asset_state_updated'));
    }

    public function toggleAssetArchived(int $assetId): void
    {
        abort_unless($this->canManageLibrary(), 403);

        $asset = EmployeeContentAsset::query()->findOrFail($assetId);
        app(LearningAssignmentManager::class)->setAssetArchived($asset, $asset->archived_at === null, auth()->user());

        unset($this->payload);
        $this->dispatch('notify', type: 'success', message: __('learning-library::dashboard.messages.asset_archive_updated'));
    }

    public function prepareNextAssetVersion(int $assetId): void
    {
        abort_unless($this->canManageLibrary(), 403);

        $asset = EmployeeContentAsset::query()->findOrFail($assetId);
        $this->versionSourceAssetId = $asset->id;
        $this->assetForm = [
            'title' => $asset->title,
            'content_type' => $asset->content_type,
            'version' => $this->incrementVersion($asset->version ?: '1.0'),
            'description' => $asset->description ?: '',
            'external_url' => $asset->external_url ?: '',
            'visibility' => $asset->visibility ?: 'internal',
            'is_active' => true,
            'auto_assign_new_hires' => (bool) $asset->auto_assign_new_hires,
            'is_required' => (bool) $asset->is_required,
            'estimated_minutes' => $asset->estimated_minutes,
        ];

        $this->dispatch('notify', type: 'info', message: __('learning-library::dashboard.messages.version_prefilled'));
    }

    public function exportAssets()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(LearningLibraryReadService::class)->exportAssets($this->searchAsset),
            [
                ['key' => 'title', 'label' => __('learning-library::dashboard.fields.asset_title')],
                ['key' => 'type', 'label' => __('learning-library::dashboard.fields.content_type')],
                ['key' => 'required', 'label' => __('learning-library::dashboard.fields.is_required')],
                ['key' => 'is_active', 'label' => __('learning-library::dashboard.fields.is_active')],
                ['key' => 'auto_assign_new_hires', 'label' => __('learning-library::dashboard.fields.auto_assign_new_hires')],
                ['key' => 'estimated_minutes', 'label' => __('learning-library::dashboard.fields.estimated_minutes')],
                ['key' => 'assignments_count', 'label' => __('learning-library::dashboard.summary.active_assignments')],
                ['key' => 'completed_assignments_count', 'label' => __('learning-library::dashboard.summary.completed_assignments')],
            ],
            'learning-assets.xlsx'
        );
    }

    public function exportAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(LearningLibraryReadService::class)->exportAssignments(),
            [
                ['key' => 'asset', 'label' => __('learning-library::dashboard.fields.asset')],
                ['key' => 'type', 'label' => __('learning-library::dashboard.fields.content_type')],
                ['key' => 'personnel', 'label' => __('learning-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'assigned_at', 'label' => __('personnel::my_hr.learning.labels.assigned_at')],
                ['key' => 'status', 'label' => __('personnel::my_hr.requests.fields.status')],
                ['key' => 'completed_at', 'label' => __('personnel::my_hr.learning.labels.completed_at')],
            ],
            'learning-assignments.xlsx'
        );
    }

    public function exportOverdueAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(LearningLibraryReadService::class)->exportOverdueAssignments(),
            [
                ['key' => 'asset', 'label' => __('learning-library::dashboard.fields.asset')],
                ['key' => 'personnel', 'label' => __('learning-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'due_at', 'label' => __('learning-library::dashboard.fields.due_at')],
                ['key' => 'status', 'label' => __('personnel::my_hr.requests.fields.status')],
            ],
            'learning-overdue-assignments.xlsx'
        );
    }

    public function exportCompletedAssignments()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(LearningLibraryReadService::class)->exportCompletedAssignments(),
            [
                ['key' => 'asset', 'label' => __('learning-library::dashboard.fields.asset')],
                ['key' => 'personnel', 'label' => __('learning-library::dashboard.fields.target_person')],
                ['key' => 'position', 'label' => __('personnel::my_hr.summary.position')],
                ['key' => 'assigned_at', 'label' => __('personnel::my_hr.learning.labels.assigned_at')],
                ['key' => 'completed_at', 'label' => __('personnel::my_hr.learning.labels.completed_at')],
            ],
            'learning-completed-assignments.xlsx'
        );
    }

    public function exportVersionHistory()
    {
        abort_unless($this->canView(), 403);

        return $this->downloadReportTable(
            app(LearningLibraryReadService::class)->exportVersionHistory($this->searchAsset),
            [
                ['key' => 'title', 'label' => __('learning-library::dashboard.fields.asset_title')],
                ['key' => 'version', 'label' => __('learning-library::dashboard.fields.version')],
                ['key' => 'family_key', 'label' => __('learning-library::dashboard.reports.version_family')],
                ['key' => 'previous_version_id', 'label' => __('learning-library::dashboard.reports.previous_version')],
                ['key' => 'archived', 'label' => __('learning-library::dashboard.fields.archived')],
                ['key' => 'visibility', 'label' => __('learning-library::dashboard.fields.visibility')],
                ['key' => 'estimated_minutes', 'label' => __('learning-library::dashboard.fields.estimated_minutes')],
            ],
            'learning-version-history.xlsx'
        );
    }

    #[Computed]
    public function exportActions(): array
    {
        return [
            LibraryExportAction::make('exportAssets', 'export_assets')->toArray(),
            LibraryExportAction::make('exportAssignments', 'export_assignments')->toArray(),
            LibraryExportAction::make('exportOverdueAssignments', 'export_overdue_assignments')->toArray(),
            LibraryExportAction::make('exportCompletedAssignments', 'export_completed_assignments')->toArray(),
            LibraryExportAction::make('exportVersionHistory', 'export_version_history')->toArray(),
        ];
    }

    #[Computed]
    public function summaryPayload(): array
    {
        return app(LearningLibraryReadService::class)->buildSummary();
    }

    #[Computed]
    public function generalPayload(): array
    {
        return app(LearningLibraryReadService::class)->buildGeneral(
            $this->searchPersonnel,
            $this->searchStructure,
            $this->searchPosition,
            'learningRecentAssignmentsPage'
        );
    }

    #[Computed]
    public function libraryPayload(): array
    {
        return app(LearningLibraryReadService::class)->buildLibrary($this->searchAsset);
    }

    #[Computed]
    public function reportsPayload(): array
    {
        return app(LearningLibraryReadService::class)->buildReports();
    }

    #[Computed]
    public function payload(): array
    {
        return app(LearningLibraryReadService::class)->build($this->searchAsset, $this->searchPersonnel, $this->searchStructure, $this->searchPosition);
    }

    public function canView(): bool
    {
        return auth()->user()?->can('view-learning-library') ?? false;
    }

    public function canManageLibrary(): bool
    {
        return auth()->user()?->can('manage-employee-content-library') ?? false;
    }

    public function canAssignContent(): bool
    {
        return auth()->user()?->can('assign-employee-content') ?? false;
    }

    public function render()
    {
        return view('learning-library::livewire.learning-library.dashboard');
    }
}
