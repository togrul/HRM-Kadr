<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\EmployeeCompetencyProfile;
use App\Models\Personnel;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Models\TrainingProgramCompetency;
use Illuminate\Validation\Rule;

trait HandlesTrainingCatalogMutations
{
    // Catalog manage state: which record (if any) each form is editing, and the
    // per-list search terms that drive the paginated manage tables.
    public ?int $editingGroupId = null;

    public ?int $editingLevelId = null;

    public ?int $editingCompetencyId = null;

    public ?int $editingProgramId = null;

    public string $groupListSearch = '';

    public string $levelListSearch = '';

    public string $competencyListSearch = '';

    public string $programListSearch = '';

    public function updatedGroupListSearch(): void
    {
        $this->resetPage('groupsPage');
    }

    public function updatedLevelListSearch(): void
    {
        $this->resetPage('levelsPage');
    }

    public function updatedCompetencyListSearch(): void
    {
        $this->resetPage('competenciesPage');
    }

    public function updatedProgramListSearch(): void
    {
        $this->resetPage('programsPage');
    }

    /** Paginated + searchable catalog lists for the Catalogs tab manage tables. */
    public function getCatalogGroupsProperty()
    {
        $term = trim($this->groupListSearch);

        return TrainingCompetencyGroup::query()
            ->withCount('competencies')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(8, ['*'], 'groupsPage');
    }

    public function getCatalogLevelsProperty()
    {
        $term = trim($this->levelListSearch);

        return TrainingLevel::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('score')
            ->orderBy('sort_order')
            ->paginate(8, ['*'], 'levelsPage');
    }

    public function getCatalogCompetenciesProperty()
    {
        $term = trim($this->competencyListSearch);

        return TrainingCompetency::query()
            ->with('group:id,name')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(8, ['*'], 'competenciesPage');
    }

    public function getCatalogProgramsProperty()
    {
        $term = trim($this->programListSearch);

        return TrainingProgram::query()
            ->when($term !== '', fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'))
            ->orderBy('title')
            ->paginate(8, ['*'], 'programsPage');
    }

    public function storeGroup(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'groupForm.name' => 'required|string|min:2|max:120',
            'groupForm.description' => 'nullable|string|max:1000',
            'groupForm.sort_order' => 'nullable|integer|min:0',
            'groupForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'groupForm.name' => __('training_needs::dashboard.fields.group_name'),
            'groupForm.description' => __('training_needs::dashboard.fields.description'),
            'groupForm.sort_order' => __('training_needs::dashboard.fields.sort_order'),
        ]);

        $name = trim((string) data_get($validated, 'groupForm.name'));
        $payload = [
            'name' => $name,
            'description' => data_get($validated, 'groupForm.description'),
            'sort_order' => (int) (data_get($validated, 'groupForm.sort_order') ?? 0),
            'is_active' => (bool) (data_get($validated, 'groupForm.is_active') ?? true),
        ];

        if ($this->editingGroupId) {
            $group = TrainingCompetencyGroup::query()->findOrFail($this->editingGroupId);
            $group->update([
                ...$payload,
                'slug' => $group->name === $name ? $group->slug : $this->uniqueSlug(TrainingCompetencyGroup::class, $name, 'name', $group->id),
            ]);
        } else {
            TrainingCompetencyGroup::query()->create([
                ...$payload,
                'slug' => $this->uniqueSlug(TrainingCompetencyGroup::class, $name),
            ]);
        }

        $this->cancelGroupEdit();
        $this->reset('searchCompetencyGroup');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.group_saved'));
    }

    public function editGroup(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        $group = TrainingCompetencyGroup::query()->findOrFail($id);
        $this->editingGroupId = $group->id;
        $this->groupForm = [
            'name' => (string) $group->name,
            'description' => (string) ($group->description ?? ''),
            'sort_order' => (int) $group->sort_order,
            'is_active' => (bool) $group->is_active,
        ];
        $this->resetValidation();
    }

    public function cancelGroupEdit(): void
    {
        $this->editingGroupId = null;
        $this->groupForm = $this->groupDefaults();
        $this->resetValidation();
    }

    public function storeLevel(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'levelForm.name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('training_levels', 'name')->ignore($this->editingLevelId)],
            'levelForm.score' => ['required', 'integer', 'min:1', 'max:10', Rule::unique('training_levels', 'score')->ignore($this->editingLevelId)],
            'levelForm.description' => 'nullable|string|max:1000',
            'levelForm.sort_order' => 'nullable|integer|min:0',
            'levelForm.is_default' => 'nullable|boolean',
        ], attributes: [
            'levelForm.name' => __('training_needs::dashboard.fields.level_name'),
            'levelForm.score' => __('training_needs::dashboard.fields.score'),
            'levelForm.description' => __('training_needs::dashboard.fields.description'),
            'levelForm.sort_order' => __('training_needs::dashboard.fields.sort_order'),
        ]);

        $isDefault = (bool) (data_get($validated, 'levelForm.is_default') ?? false);
        if ($isDefault) {
            TrainingLevel::query()->update(['is_default' => false]);
        }

        $payload = [
            'name' => trim((string) data_get($validated, 'levelForm.name')),
            'score' => (int) data_get($validated, 'levelForm.score'),
            'description' => data_get($validated, 'levelForm.description'),
            'sort_order' => (int) (data_get($validated, 'levelForm.sort_order') ?? 0),
            'is_default' => $isDefault,
        ];

        if ($this->editingLevelId) {
            TrainingLevel::query()->findOrFail($this->editingLevelId)->update($payload);
        } else {
            TrainingLevel::query()->create($payload);
        }

        $this->cancelLevelEdit();
        $this->reset('searchCompetencyLevel');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.level_saved'));
    }

    public function editLevel(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        $level = TrainingLevel::query()->findOrFail($id);
        $this->editingLevelId = $level->id;
        $this->levelForm = [
            'name' => (string) $level->name,
            'score' => (int) $level->score,
            'description' => (string) ($level->description ?? ''),
            'sort_order' => (int) $level->sort_order,
            'is_default' => (bool) $level->is_default,
        ];
        $this->resetValidation();
    }

    public function cancelLevelEdit(): void
    {
        $this->editingLevelId = null;
        $this->levelForm = $this->levelDefaults();
        $this->resetValidation();
    }

    public function storeCompetency(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'competencyForm.training_competency_group_id' => 'nullable|exists:training_competency_groups,id',
            'competencyForm.name' => 'required|string|min:2|max:160',
            'competencyForm.description' => 'nullable|string|max:1000',
            'competencyForm.is_mandatory' => 'nullable|boolean',
            'competencyForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'competencyForm.training_competency_group_id' => __('training_needs::dashboard.fields.group'),
            'competencyForm.name' => __('training_needs::dashboard.fields.competency_name'),
            'competencyForm.description' => __('training_needs::dashboard.fields.description'),
        ]);

        $name = trim((string) data_get($validated, 'competencyForm.name'));
        $payload = [
            'training_competency_group_id' => data_get($validated, 'competencyForm.training_competency_group_id'),
            'name' => $name,
            'description' => data_get($validated, 'competencyForm.description'),
            'is_mandatory' => (bool) (data_get($validated, 'competencyForm.is_mandatory') ?? false),
            'is_active' => (bool) (data_get($validated, 'competencyForm.is_active') ?? true),
        ];

        if ($this->editingCompetencyId) {
            $competency = TrainingCompetency::query()->findOrFail($this->editingCompetencyId);
            $competency->update([
                ...$payload,
                'slug' => $competency->name === $name ? $competency->slug : $this->uniqueSlug(TrainingCompetency::class, $name, 'name', $competency->id),
            ]);
        } else {
            TrainingCompetency::query()->create([
                ...$payload,
                'slug' => $this->uniqueSlug(TrainingCompetency::class, $name),
            ]);
        }

        $this->cancelCompetencyEdit();
        $this->reset('searchCompetency');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.competency_saved'));
    }

    public function editCompetency(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        $competency = TrainingCompetency::query()->findOrFail($id);
        $this->editingCompetencyId = $competency->id;
        $this->competencyForm = [
            'training_competency_group_id' => $competency->training_competency_group_id,
            'name' => (string) $competency->name,
            'description' => (string) ($competency->description ?? ''),
            'is_mandatory' => (bool) $competency->is_mandatory,
            'is_active' => (bool) $competency->is_active,
        ];
        $this->resetValidation();
    }

    public function cancelCompetencyEdit(): void
    {
        $this->editingCompetencyId = null;
        $this->competencyForm = $this->competencyDefaults();
        $this->resetValidation();
    }

    public function storeProgram(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'programForm.title' => 'required|string|min:2|max:160',
            'programForm.code' => 'nullable|string|max:40',
            'programForm.delivery_type' => 'required|in:internal,external,hybrid',
            'programForm.duration_hours' => 'nullable|numeric|min:0|max:999.99',
            'programForm.description' => 'nullable|string|max:1000',
            'programForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'programForm.title' => __('training_needs::dashboard.fields.program_title'),
            'programForm.code' => __('training_needs::dashboard.fields.program_code'),
            'programForm.delivery_type' => __('training_needs::dashboard.fields.delivery_type'),
            'programForm.duration_hours' => __('training_needs::dashboard.fields.duration_hours'),
            'programForm.description' => __('training_needs::dashboard.fields.description'),
        ]);

        $title = trim((string) data_get($validated, 'programForm.title'));
        $payload = [
            'title' => $title,
            'code' => blank(data_get($validated, 'programForm.code')) ? null : trim((string) data_get($validated, 'programForm.code')),
            'delivery_type' => (string) data_get($validated, 'programForm.delivery_type'),
            'duration_hours' => data_get($validated, 'programForm.duration_hours'),
            'description' => data_get($validated, 'programForm.description'),
            'is_active' => (bool) (data_get($validated, 'programForm.is_active') ?? true),
        ];

        if ($this->editingProgramId) {
            $program = TrainingProgram::query()->findOrFail($this->editingProgramId);
            $program->update([
                ...$payload,
                'slug' => $program->title === $title ? $program->slug : $this->uniqueSlug(TrainingProgram::class, $title, 'title', $program->id),
            ]);
        } else {
            TrainingProgram::query()->create([
                ...$payload,
                'slug' => $this->uniqueSlug(TrainingProgram::class, $title, 'title'),
            ]);
        }

        $this->cancelProgramEdit();
        $this->reset('searchTrainingProgram');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.program_saved'));
    }

    public function editProgram(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        $program = TrainingProgram::query()->findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->programForm = [
            'title' => (string) $program->title,
            'code' => (string) ($program->code ?? ''),
            'delivery_type' => (string) $program->delivery_type,
            'duration_hours' => $program->duration_hours,
            'description' => (string) ($program->description ?? ''),
            'is_active' => (bool) $program->is_active,
        ];
        $this->resetValidation();
    }

    public function cancelProgramEdit(): void
    {
        $this->editingProgramId = null;
        $this->programForm = $this->programDefaults();
        $this->resetValidation();
    }

    public function deleteGroup(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        TrainingCompetencyGroup::query()->whereKey($id)->delete();
        if ($this->editingGroupId === $id) {
            $this->cancelGroupEdit();
        }
        $this->resetPage('groupsPage');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.group_deleted'));
    }

    public function deleteLevel(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        TrainingLevel::query()->whereKey($id)->delete();
        if ($this->editingLevelId === $id) {
            $this->cancelLevelEdit();
        }
        $this->resetPage('levelsPage');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.level_deleted'));
    }

    public function deleteCompetency(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        TrainingCompetency::query()->whereKey($id)->delete();
        if ($this->editingCompetencyId === $id) {
            $this->cancelCompetencyEdit();
        }
        $this->resetPage('competenciesPage');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.competency_deleted'));
    }

    public function deleteProgram(int $id): void
    {
        $this->authorizeTrainingNeedsManage();
        TrainingProgram::query()->whereKey($id)->delete();
        if ($this->editingProgramId === $id) {
            $this->cancelProgramEdit();
        }
        $this->resetPage('programsPage');
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.program_deleted'));
    }

    public function storeProgramMap(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'programMapForm.training_program_id' => 'required|exists:training_programs,id',
            'programMapForm.training_competency_id' => 'required|exists:training_competencies,id',
            'programMapForm.target_level_id' => 'nullable|exists:training_levels,id',
        ], attributes: [
            'programMapForm.training_program_id' => __('training_needs::dashboard.fields.program'),
            'programMapForm.training_competency_id' => __('training_needs::dashboard.fields.competency'),
            'programMapForm.target_level_id' => __('training_needs::dashboard.fields.target_level'),
        ]);

        TrainingProgramCompetency::query()->updateOrCreate(
            [
                'training_program_id' => (int) data_get($validated, 'programMapForm.training_program_id'),
                'training_competency_id' => (int) data_get($validated, 'programMapForm.training_competency_id'),
            ],
            [
                'target_level_id' => data_get($validated, 'programMapForm.target_level_id'),
            ]
        );

        $this->reset('programMapForm');
        $this->programMapForm = $this->programMapDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.program_map_saved'));
    }

    public function storeRequirement(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'requirementForm.position_id' => 'required|exists:positions,id',
            'requirementForm.training_competency_id' => 'required|exists:training_competencies,id',
            'requirementForm.required_level_id' => 'required|exists:training_levels,id',
            'requirementForm.priority' => 'required|in:low,medium,high',
            'requirementForm.is_mandatory' => 'nullable|boolean',
        ], attributes: [
            'requirementForm.position_id' => __('training_needs::dashboard.fields.position'),
            'requirementForm.training_competency_id' => __('training_needs::dashboard.fields.competency'),
            'requirementForm.required_level_id' => __('training_needs::dashboard.fields.required_level'),
            'requirementForm.priority' => __('training_needs::dashboard.fields.priority'),
        ]);

        RoleCompetencyRequirement::query()->updateOrCreate(
            [
                'position_id' => (int) data_get($validated, 'requirementForm.position_id'),
                'training_competency_id' => (int) data_get($validated, 'requirementForm.training_competency_id'),
            ],
            [
                'required_level_id' => (int) data_get($validated, 'requirementForm.required_level_id'),
                'priority' => (string) data_get($validated, 'requirementForm.priority'),
                'is_mandatory' => (bool) (data_get($validated, 'requirementForm.is_mandatory') ?? false),
            ]
        );

        $this->reset('requirementForm', 'searchRequirementPosition');
        $this->requirementForm = $this->requirementDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.requirement_saved'));
    }

    public function storeProfile(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'profileForm.personnel_id' => 'required|exists:personnels,id',
            'profileForm.training_competency_id' => 'required|exists:training_competencies,id',
            'profileForm.current_level_id' => 'nullable|exists:training_levels,id',
            'profileForm.source' => 'nullable|string|max:60',
            'profileForm.last_assessed_at' => 'nullable|date',
        ], attributes: [
            'profileForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'profileForm.training_competency_id' => __('training_needs::dashboard.fields.competency'),
            'profileForm.current_level_id' => __('training_needs::dashboard.fields.current_level'),
            'profileForm.source' => __('training_needs::dashboard.fields.source'),
            'profileForm.last_assessed_at' => __('training_needs::dashboard.fields.last_assessed_at'),
        ]);

        EmployeeCompetencyProfile::query()->updateOrCreate(
            [
                'personnel_id' => (int) data_get($validated, 'profileForm.personnel_id'),
                'training_competency_id' => (int) data_get($validated, 'profileForm.training_competency_id'),
            ],
            [
                'current_level_id' => data_get($validated, 'profileForm.current_level_id'),
                'source' => blank(data_get($validated, 'profileForm.source')) ? null : trim((string) data_get($validated, 'profileForm.source')),
                'last_assessed_at' => data_get($validated, 'profileForm.last_assessed_at'),
            ]
        );

        $this->reset('profileForm', 'searchPersonnel');
        $this->profileForm = $this->profileDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.profile_saved'));
    }

    public function storeNeed(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'needForm.personnel_id' => 'required|exists:personnels,id',
            'needForm.training_competency_id' => 'required|exists:training_competencies,id',
            'needForm.recommended_program_id' => 'nullable|exists:training_programs,id',
            'needForm.target_level_id' => 'nullable|exists:training_levels,id',
            'needForm.priority' => 'required|in:low,medium,high',
            'needForm.source' => 'nullable|string|max:60',
            'needForm.status' => 'required|in:draft,review,approved,planned',
            'needForm.reason' => 'nullable|string|max:1000',
            'needForm.plan_note' => 'nullable|string|max:1000',
            'needForm.target_completion_date' => 'nullable|date',
        ], attributes: [
            'needForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'needForm.training_competency_id' => __('training_needs::dashboard.fields.competency'),
            'needForm.recommended_program_id' => __('training_needs::dashboard.fields.recommended_program'),
            'needForm.target_level_id' => __('training_needs::dashboard.fields.target_level'),
            'needForm.priority' => __('training_needs::dashboard.fields.priority'),
            'needForm.source' => __('training_needs::dashboard.fields.source'),
            'needForm.status' => __('training_needs::dashboard.fields.status'),
            'needForm.reason' => __('training_needs::dashboard.fields.reason'),
            'needForm.plan_note' => __('training_needs::dashboard.fields.plan_note'),
            'needForm.target_completion_date' => __('training_needs::dashboard.fields.target_completion_date'),
        ]);

        $personnel = Personnel::query()->select('id', 'position_id')->findOrFail((int) data_get($validated, 'needForm.personnel_id'));

        TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => (int) data_get($validated, 'needForm.training_competency_id'),
            'position_id' => $personnel->position_id,
            'recommended_program_id' => data_get($validated, 'needForm.recommended_program_id'),
            'target_level_id' => data_get($validated, 'needForm.target_level_id'),
            'priority' => (string) data_get($validated, 'needForm.priority'),
            'source' => blank(data_get($validated, 'needForm.source')) ? null : trim((string) data_get($validated, 'needForm.source')),
            'status' => (string) data_get($validated, 'needForm.status'),
            'target_completion_date' => data_get($validated, 'needForm.target_completion_date'),
            'reason' => data_get($validated, 'needForm.reason'),
            'plan_note' => data_get($validated, 'needForm.plan_note'),
        ]);

        $this->reset('needForm', 'searchPersonnel');
        $this->needForm = $this->needDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.need_saved'));
    }
}
