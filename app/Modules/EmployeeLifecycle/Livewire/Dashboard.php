<?php

namespace App\Modules\EmployeeLifecycle\Livewire;

use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use App\Modules\EmployeeLifecycle\Application\Services\LifecyclePlanTemplateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $search = '';

    public string $type = '';

    public string $status = '';

    public array $templateForm = [
        'name' => '',
        'type' => 'onboarding',
        'default_duration_days' => 14,
        'description' => '',
        'tasks' => "HR sənədlərini yoxla\nİş yeri və giriş hüquqlarını hazırla\nRəhbər ilə giriş görüşü keçir",
    ];

    public ?int $selectedTemplateId = null;

    public bool $isTemplateEditorOpen = false;

    public array $editingTemplateForm = [
        'name' => '',
        'type' => 'onboarding',
        'default_duration_days' => 14,
        'description' => '',
        'is_active' => true,
        'usage_count' => 0,
        'tasks' => [],
    ];

    public array $launchForm = [
        'template_id' => '',
        'personnel_id' => '',
        'start_date' => '',
        'owner_user_id' => '',
    ];

    public array $probationForm = [
        'personnel_id' => '',
        'review_due_at' => '',
        'manager_user_id' => '',
        'hr_reviewer_user_id' => '',
    ];

    public array $movementForm = [
        'personnel_id' => '',
        'movement_type' => 'transfer',
        'target_structure_id' => '',
        'target_position_id' => '',
        'effective_date' => '',
        'reason' => '',
        'owner_user_id' => '',
    ];

    public array $offboardingForm = [
        'personnel_id' => '',
        'last_working_date' => '',
        'reason' => '',
        'owner_user_id' => '',
    ];

    public array $completionForm = [
        'probation_review_id' => '',
        'probation_decision' => 'confirm',
        'probation_score' => '',
        'probation_note' => '',
        'movement_id' => '',
        'offboarding_case_id' => '',
        'exit_summary' => '',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('show-employee-lifecycle'), 403);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->status = '';
    }

    public function createTemplate(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'templateForm.name' => ['required', 'string', 'max:255'],
            'templateForm.type' => ['required', 'in:onboarding,probation,movement,offboarding'],
            'templateForm.default_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'templateForm.description' => ['nullable', 'string', 'max:2000'],
            'templateForm.tasks' => ['required', 'string', 'max:5000'],
        ])['templateForm'];

        $service->createTemplate([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?: null,
            'default_duration_days' => (int) $data['default_duration_days'],
            'created_by' => auth()->id(),
        ], $this->taskTemplateRows((string) $data['tasks']));

        $this->templateForm['name'] = '';
        $this->templateForm['description'] = '';
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.template_created'));
    }

    public function selectTemplate(int $templateId): void
    {
        $template = DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->first();

        if (! $template) {
            $this->selectedTemplateId = null;
            $this->isTemplateEditorOpen = false;
            $this->dispatch('notify', type: 'error', message: __('employee-lifecycle::dashboard.errors.template_not_found'));

            return;
        }

        $tasks = DB::table('employee_lifecycle_task_templates')
            ->where('plan_template_id', $templateId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['title', 'owner_type', 'due_offset_days', 'is_required', 'sort_order'])
            ->map(fn ($task): array => [
                'title' => (string) $task->title,
                'owner_type' => (string) $task->owner_type,
                'due_offset_days' => (int) $task->due_offset_days,
                'is_required' => (bool) $task->is_required,
                'sort_order' => (int) $task->sort_order,
            ])
            ->values()
            ->all();

        $this->selectedTemplateId = $templateId;
        $this->editingTemplateForm = [
            'name' => (string) $template->name,
            'type' => (string) $template->type,
            'default_duration_days' => (int) $template->default_duration_days,
            'description' => (string) ($template->description ?? ''),
            'is_active' => (bool) $template->is_active,
            'usage_count' => DB::table('employee_lifecycle_events')->where('plan_template_id', $templateId)->count(),
            'tasks' => $tasks ?: [$this->emptyTaskRow()],
        ];

        $this->isTemplateEditorOpen = true;
        $this->resetErrorBag();
    }

    public function closeTemplateEditor(): void
    {
        $this->isTemplateEditorOpen = false;
        $this->resetErrorBag();
    }

    public function addTemplateTaskRow(): void
    {
        $this->authorizeManage();

        $this->editingTemplateForm['tasks'][] = $this->emptyTaskRow(count($this->editingTemplateForm['tasks'] ?? []));
        $this->dispatch('notify', type: 'info', message: __('employee-lifecycle::dashboard.messages.template_task_added'));
    }

    public function removeTemplateTaskRow(int $index): void
    {
        $this->authorizeManage();

        unset($this->editingTemplateForm['tasks'][$index]);
        $this->editingTemplateForm['tasks'] = array_values($this->editingTemplateForm['tasks']);

        if ($this->editingTemplateForm['tasks'] === []) {
            $this->editingTemplateForm['tasks'][] = $this->emptyTaskRow();
        }
    }

    public function updateTemplate(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();
        abort_unless($this->selectedTemplateId, 404);

        $data = $this->validateLifecycle([
            'editingTemplateForm.name' => ['required', 'string', 'max:255'],
            'editingTemplateForm.type' => ['required', 'in:onboarding,probation,movement,offboarding'],
            'editingTemplateForm.default_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'editingTemplateForm.description' => ['nullable', 'string', 'max:2000'],
            'editingTemplateForm.is_active' => ['boolean'],
            'editingTemplateForm.tasks' => ['required', 'array', 'min:1'],
            'editingTemplateForm.tasks.*.title' => ['required', 'string', 'max:255'],
            'editingTemplateForm.tasks.*.owner_type' => ['required', 'in:hr,manager,it,employee'],
            'editingTemplateForm.tasks.*.due_offset_days' => ['required', 'integer', 'min:0', 'max:365'],
            'editingTemplateForm.tasks.*.is_required' => ['boolean'],
        ])['editingTemplateForm'];

        $service->updateTemplate(
            $this->selectedTemplateId,
            [
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?: null,
                'default_duration_days' => (int) $data['default_duration_days'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ],
            $this->normalizedTaskRows($data['tasks'])
        );

        $this->selectTemplate($this->selectedTemplateId);
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.template_updated'));
    }

    public function toggleTemplateActive(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();
        abort_unless($this->selectedTemplateId, 404);

        $nextState = ! (bool) ($this->editingTemplateForm['is_active'] ?? false);
        $service->setTemplateActive($this->selectedTemplateId, $nextState);
        $this->selectTemplate($this->selectedTemplateId);

        $this->dispatch(
            'notify',
            type: 'success',
            message: $nextState
                ? __('employee-lifecycle::dashboard.messages.template_activated')
                : __('employee-lifecycle::dashboard.messages.template_deactivated')
        );
    }

    public function deleteOrArchiveTemplate(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();
        abort_unless($this->selectedTemplateId, 404);

        $result = $service->deleteOrArchiveTemplate($this->selectedTemplateId);

        if ($result === 'deleted') {
            $this->selectedTemplateId = null;
            $this->isTemplateEditorOpen = false;
            $this->editingTemplateForm = [
                'name' => '',
                'type' => 'onboarding',
                'default_duration_days' => 14,
                'description' => '',
                'is_active' => true,
                'usage_count' => 0,
                'tasks' => [],
            ];
        } else {
            $this->selectTemplate($this->selectedTemplateId);
        }

        $this->dispatch(
            'notify',
            type: 'success',
            message: $result === 'deleted'
                ? __('employee-lifecycle::dashboard.messages.template_deleted')
                : __('employee-lifecycle::dashboard.messages.template_archived')
        );
    }

    public function launchTemplate(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'launchForm.template_id' => ['required', 'integer', 'exists:employee_lifecycle_plan_templates,id'],
            'launchForm.personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'launchForm.start_date' => ['required', 'date'],
            'launchForm.owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ])['launchForm'];

        $service->launchForPersonnel(
            (int) $data['template_id'],
            (int) $data['personnel_id'],
            $data['start_date'],
            filled($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            auth()->id()
        );

        $this->launchForm = ['template_id' => '', 'personnel_id' => '', 'start_date' => '', 'owner_user_id' => ''];
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.plan_launched'));
    }

    public function scheduleProbation(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'probationForm.personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'probationForm.review_due_at' => ['required', 'date'],
            'probationForm.manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'probationForm.hr_reviewer_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ])['probationForm'];

        $service->scheduleProbationReview(
            (int) $data['personnel_id'],
            $data['review_due_at'],
            filled($data['manager_user_id']) ? (int) $data['manager_user_id'] : null,
            filled($data['hr_reviewer_user_id']) ? (int) $data['hr_reviewer_user_id'] : null,
            auth()->id()
        );

        $this->probationForm = ['personnel_id' => '', 'review_due_at' => '', 'manager_user_id' => '', 'hr_reviewer_user_id' => ''];
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.probation_scheduled'));
    }

    public function scheduleMovement(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'movementForm.personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'movementForm.movement_type' => ['required', 'in:transfer,promotion,role_change'],
            'movementForm.target_structure_id' => ['nullable', 'integer', 'exists:structures,id'],
            'movementForm.target_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'movementForm.effective_date' => ['required', 'date'],
            'movementForm.reason' => ['nullable', 'string', 'max:2000'],
            'movementForm.owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ])['movementForm'];

        $service->scheduleMovement(
            (int) $data['personnel_id'],
            $data['movement_type'],
            filled($data['target_structure_id']) ? (int) $data['target_structure_id'] : null,
            filled($data['target_position_id']) ? (int) $data['target_position_id'] : null,
            $data['effective_date'],
            $data['reason'] ?: null,
            filled($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            auth()->id()
        );

        $this->movementForm = ['personnel_id' => '', 'movement_type' => 'transfer', 'target_structure_id' => '', 'target_position_id' => '', 'effective_date' => '', 'reason' => '', 'owner_user_id' => ''];
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.movement_scheduled'));
    }

    public function openOffboarding(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'offboardingForm.personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'offboardingForm.last_working_date' => ['required', 'date'],
            'offboardingForm.reason' => ['nullable', 'string', 'max:2000'],
            'offboardingForm.owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ])['offboardingForm'];

        $service->openOffboardingCase(
            (int) $data['personnel_id'],
            $data['last_working_date'],
            $data['reason'] ?: null,
            filled($data['owner_user_id']) ? (int) $data['owner_user_id'] : null,
            auth()->id()
        );

        $this->offboardingForm = ['personnel_id' => '', 'last_working_date' => '', 'reason' => '', 'owner_user_id' => ''];
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.offboarding_opened'));
    }

    public function completeProbationReview(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'completionForm.probation_review_id' => ['required', 'integer', 'exists:employee_lifecycle_probation_reviews,id'],
            'completionForm.probation_decision' => ['required', 'in:confirm,extend,terminate'],
            'completionForm.probation_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'completionForm.probation_note' => ['nullable', 'string', 'max:2000'],
        ])['completionForm'];

        $service->completeProbationReview(
            (int) $data['probation_review_id'],
            $data['probation_decision'],
            filled($data['probation_score']) ? (int) $data['probation_score'] : null,
            $data['probation_note'] ?: null,
            auth()->id()
        );

        $this->completionForm['probation_review_id'] = '';
        $this->completionForm['probation_score'] = '';
        $this->completionForm['probation_note'] = '';
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.probation_completed'));
    }

    public function completeMovement(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'completionForm.movement_id' => ['required', 'integer', 'exists:employee_lifecycle_movements,id'],
        ])['completionForm'];

        $service->completeMovement((int) $data['movement_id'], auth()->id());

        $this->completionForm['movement_id'] = '';
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.movement_completed'));
    }

    public function completeOffboarding(LifecyclePlanTemplateService $service): void
    {
        $this->authorizeManage();

        $data = $this->validateLifecycle([
            'completionForm.offboarding_case_id' => ['required', 'integer', 'exists:employee_lifecycle_offboarding_cases,id'],
            'completionForm.exit_summary' => ['nullable', 'string', 'max:2000'],
        ])['completionForm'];

        $service->completeOffboardingCase((int) $data['offboarding_case_id'], $data['exit_summary'] ?: null, auth()->id());

        $this->completionForm['offboarding_case_id'] = '';
        $this->completionForm['exit_summary'] = '';
        $this->dispatch('notify', type: 'success', message: __('employee-lifecycle::dashboard.messages.offboarding_completed'));
    }

    public function render(LifecycleDashboardReadService $service)
    {
        return view('employee-lifecycle::livewire.dashboard', [
            ...$service->dashboard([
                'search' => $this->search,
                'type' => $this->type,
                'status' => $this->status,
            ]),
            'personnelOptions' => $this->personnelOptions(),
            'userOptions' => $this->userOptions(),
            'structureOptions' => $this->structureOptions(),
            'positionOptions' => $this->positionOptions(),
        ]);
    }

    /**
     * @return array<int, array{title: string, owner_type: string, due_offset_days: int, sort_order: int}>
     */
    private function taskTemplateRows(string $tasks): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $tasks) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->map(fn (string $title, int $index): array => [
                'title' => $title,
                'owner_type' => $index === 1 ? 'it' : ($index === 2 ? 'manager' : 'hr'),
                'due_offset_days' => $index,
                'sort_order' => $index,
            ])
            ->all();
    }

    private function emptyTaskRow(int $sortOrder = 0): array
    {
        return [
            'title' => '',
            'owner_type' => 'hr',
            'due_offset_days' => $sortOrder,
            'is_required' => true,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @param  array<int, array{title: string, owner_type?: string, due_offset_days?: int|string, is_required?: bool|string}>  $tasks
     * @return array<int, array{title: string, owner_type: string, due_offset_days: int, is_required: bool, sort_order: int}>
     */
    private function normalizedTaskRows(array $tasks): array
    {
        return collect($tasks)
            ->values()
            ->map(fn (array $task, int $index): array => [
                'title' => trim((string) $task['title']),
                'owner_type' => (string) ($task['owner_type'] ?? 'hr'),
                'due_offset_days' => (int) ($task['due_offset_days'] ?? 0),
                'is_required' => (bool) ($task['is_required'] ?? false),
                'sort_order' => $index,
            ])
            ->all();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-employee-lifecycle'), 403);
    }

    private function validateLifecycle(array $rules): array
    {
        return $this->validate($rules, [], [
            'templateForm.name' => __('employee-lifecycle::dashboard.fields.template_name'),
            'templateForm.type' => __('employee-lifecycle::dashboard.fields.type'),
            'templateForm.default_duration_days' => __('employee-lifecycle::dashboard.fields.default_duration_days'),
            'templateForm.description' => __('employee-lifecycle::dashboard.fields.description'),
            'templateForm.tasks' => __('employee-lifecycle::dashboard.fields.task_lines'),
            'editingTemplateForm.name' => __('employee-lifecycle::dashboard.fields.template_name'),
            'editingTemplateForm.type' => __('employee-lifecycle::dashboard.fields.type'),
            'editingTemplateForm.default_duration_days' => __('employee-lifecycle::dashboard.fields.default_duration_days'),
            'editingTemplateForm.description' => __('employee-lifecycle::dashboard.fields.description'),
            'editingTemplateForm.is_active' => __('employee-lifecycle::dashboard.fields.template_active'),
            'editingTemplateForm.tasks' => __('employee-lifecycle::dashboard.fields.task_lines'),
            'editingTemplateForm.tasks.*.title' => __('employee-lifecycle::dashboard.fields.task_title'),
            'editingTemplateForm.tasks.*.owner_type' => __('employee-lifecycle::dashboard.fields.task_owner_type'),
            'editingTemplateForm.tasks.*.due_offset_days' => __('employee-lifecycle::dashboard.fields.task_due_offset_days'),
            'editingTemplateForm.tasks.*.is_required' => __('employee-lifecycle::dashboard.fields.task_required'),
            'launchForm.template_id' => __('employee-lifecycle::dashboard.fields.template'),
            'launchForm.personnel_id' => __('employee-lifecycle::dashboard.fields.personnel'),
            'launchForm.start_date' => __('employee-lifecycle::dashboard.fields.start_date'),
            'launchForm.owner_user_id' => __('employee-lifecycle::dashboard.fields.owner'),
            'probationForm.personnel_id' => __('employee-lifecycle::dashboard.fields.personnel'),
            'probationForm.review_due_at' => __('employee-lifecycle::dashboard.fields.review_due_at'),
            'probationForm.manager_user_id' => __('employee-lifecycle::dashboard.fields.manager'),
            'probationForm.hr_reviewer_user_id' => __('employee-lifecycle::dashboard.fields.hr_reviewer'),
            'movementForm.personnel_id' => __('employee-lifecycle::dashboard.fields.personnel'),
            'movementForm.movement_type' => __('employee-lifecycle::dashboard.fields.movement_type'),
            'movementForm.target_structure_id' => __('employee-lifecycle::dashboard.fields.target_structure'),
            'movementForm.target_position_id' => __('employee-lifecycle::dashboard.fields.target_position'),
            'movementForm.effective_date' => __('employee-lifecycle::dashboard.fields.effective_date'),
            'movementForm.reason' => __('employee-lifecycle::dashboard.fields.reason'),
            'movementForm.owner_user_id' => __('employee-lifecycle::dashboard.fields.owner'),
            'offboardingForm.personnel_id' => __('employee-lifecycle::dashboard.fields.personnel'),
            'offboardingForm.last_working_date' => __('employee-lifecycle::dashboard.fields.last_working_date'),
            'offboardingForm.reason' => __('employee-lifecycle::dashboard.fields.reason'),
            'offboardingForm.owner_user_id' => __('employee-lifecycle::dashboard.fields.owner'),
            'completionForm.probation_review_id' => __('employee-lifecycle::dashboard.forms.probation'),
            'completionForm.probation_decision' => __('employee-lifecycle::dashboard.probation_decisions.confirm'),
            'completionForm.probation_score' => __('employee-lifecycle::dashboard.fields.probation_score'),
            'completionForm.probation_note' => __('employee-lifecycle::dashboard.fields.probation_note'),
            'completionForm.movement_id' => __('employee-lifecycle::dashboard.forms.movement'),
            'completionForm.offboarding_case_id' => __('employee-lifecycle::dashboard.forms.offboarding'),
            'completionForm.exit_summary' => __('employee-lifecycle::dashboard.fields.exit_summary'),
        ]);
    }

    private function personnelOptions(): Collection
    {
        return DB::table('personnels')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->whereNull('personnels.deleted_at')
            ->orderBy('personnels.surname')
            ->limit(120)
            ->get([
                'personnels.id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
            ])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'label' => trim(implode(' ', array_filter([$row->surname, $row->name, $row->patronymic]))).' · '.$row->tabel_no,
                'meta' => trim(implode(' · ', array_filter([$row->structure_name, $row->position_name]))),
            ]);
    }

    private function userOptions(): Collection
    {
        return DB::table('users')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name', 'email'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'label' => (string) $row->name,
                'meta' => (string) $row->email,
            ]);
    }

    private function structureOptions(): Collection
    {
        return DB::table('structures')
            ->orderBy('name')
            ->limit(120)
            ->get(['id', 'name'])
            ->map(fn ($row): array => ['id' => (int) $row->id, 'label' => (string) $row->name]);
    }

    private function positionOptions(): Collection
    {
        return DB::table('positions')
            ->orderBy('name')
            ->limit(120)
            ->get(['id', 'name'])
            ->map(fn ($row): array => ['id' => (int) $row->id, 'label' => (string) $row->name]);
    }
}
