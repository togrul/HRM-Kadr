<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\LearningAssignmentManagerService;
use App\Modules\Personnel\Application\Services\MyHr\MyHrLearningReadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class LearningAssignmentManager extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $personnelModel;

    public Personnel $personnel;

    public string $searchAsset = '';

    public $assetUpload = null;

    public array $assetForm = [
        'title' => '',
        'content_type' => 'pdf',
        'description' => '',
        'external_url' => '',
        'visibility' => 'internal',
        'is_required' => false,
        'estimated_minutes' => null,
    ];

    public array $assignmentForm = [
        'asset_id' => null,
        'due_at' => null,
    ];

    public function mount(int $personnelModel): void
    {
        abort_unless($this->canManageLibrary() || $this->canAssignContent(), 403);

        $this->personnel = Personnel::query()->with(['position', 'structure'])->findOrFail($personnelModel);
    }

    public function saveAsset(): void
    {
        abort_unless($this->canManageLibrary(), 403);

        $validated = $this->validate([
            'assetForm.title' => 'required|string|max:255',
            'assetForm.content_type' => 'required|in:video,presentation,pdf,link,other',
            'assetForm.description' => 'nullable|string|max:5000',
            'assetForm.external_url' => 'nullable|url|max:2048',
            'assetForm.visibility' => 'required|in:internal,public',
            'assetForm.is_required' => 'boolean',
            'assetForm.estimated_minutes' => 'nullable|integer|min:1|max:600',
            'assetUpload' => 'nullable|file|max:20480',
        ], attributes: [
            'assetForm.title' => __('personnel::my_hr.learning_admin.fields.asset_title'),
            'assetForm.content_type' => __('personnel::my_hr.learning_admin.fields.content_type'),
            'assetForm.external_url' => __('personnel::my_hr.learning_admin.fields.external_url'),
            'assetForm.visibility' => __('personnel::my_hr.learning_admin.fields.visibility'),
            'assetForm.estimated_minutes' => __('personnel::my_hr.learning_admin.fields.estimated_minutes'),
            'assetUpload' => __('personnel::my_hr.learning_admin.fields.file'),
        ]);

        $asset = app(LearningAssignmentManagerService::class)->createAsset(
            data_get($validated, 'assetForm', []),
            $this->assetUpload,
            auth()->user()
        );

        $this->assignmentForm['asset_id'] = $asset->id;
        $this->assetUpload = null;
        $this->assetForm = [
            'title' => '',
            'content_type' => 'pdf',
            'description' => '',
            'external_url' => '',
            'visibility' => 'internal',
            'is_required' => false,
            'estimated_minutes' => null,
        ];

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.learning_admin.messages.asset_saved'));
    }

    public function assignAsset(): void
    {
        abort_unless($this->canAssignContent(), 403);

        $validated = $this->validate([
            'assignmentForm.asset_id' => 'required|exists:employee_content_assets,id',
            'assignmentForm.due_at' => 'nullable|date',
        ], attributes: [
            'assignmentForm.asset_id' => __('personnel::my_hr.learning_admin.fields.asset'),
            'assignmentForm.due_at' => __('personnel::my_hr.learning_admin.fields.due_at'),
        ]);

        app(LearningAssignmentManagerService::class)->assign(
            $this->personnel,
            (int) data_get($validated, 'assignmentForm.asset_id'),
            data_get($validated, 'assignmentForm.due_at'),
            auth()->user()
        );

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.learning_admin.messages.assignment_saved'));
    }

    public function waiveAssignment(int $assignmentId): void
    {
        abort_unless($this->canAssignContent(), 403);

        app(LearningAssignmentManagerService::class)->waive($this->assignment($assignmentId));

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.learning_admin.messages.assignment_waived'));
    }

    public function removeAssignment(int $assignmentId): void
    {
        abort_unless($this->canAssignContent(), 403);

        app(LearningAssignmentManagerService::class)->remove($this->assignment($assignmentId));

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.learning_admin.messages.assignment_removed'));
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrLearningReadService::class)->build($this->personnel);
    }

    public function assetOptions(): array
    {
        return EmployeeContentAsset::query()
            ->when($this->searchAsset !== '', function ($query): void {
                $query->where('title', 'like', '%'.$this->searchAsset.'%');
            })
            ->latest('created_at')
            ->get(['id', 'title', 'content_type'])
            ->map(fn (EmployeeContentAsset $asset): array => [
                'id' => $asset->id,
                'label' => trim($asset->title.' · '.__('personnel::my_hr.learning.content_types.'.$asset->content_type)),
            ])
            ->all();
    }

    public function assets(): array
    {
        return EmployeeContentAsset::query()
            ->withCount('assignments')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (EmployeeContentAsset $asset): array => [
                'id' => $asset->id,
                'title' => $asset->title,
                'content_type_label' => __('personnel::my_hr.learning.content_types.'.$asset->content_type),
                'estimated_minutes' => $asset->estimated_minutes,
                'required' => (bool) $asset->is_required,
                'content_url' => $asset->contentUrl(),
                'assignments_count' => $asset->assignments_count,
            ])
            ->all();
    }

    public function canManageLibrary(): bool
    {
        return auth()->user()?->can('manage-employee-content-library') ?? false;
    }

    public function canAssignContent(): bool
    {
        return auth()->user()?->can('assign-employee-content') ?? false;
    }

    protected function assignment(int $assignmentId): EmployeeContentAssignment
    {
        return EmployeeContentAssignment::query()
            ->where('personnel_id', $this->personnel->id)
            ->with(['asset', 'view'])
            ->findOrFail($assignmentId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.learning-assignment-manager');
    }
}
