<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationAudienceTargetRegistry;
use App\Modules\Notifications\Support\NotificationTriggerRegistry;
use Illuminate\Support\Arr;
use Livewire\Component;

class RuleManager extends Component
{
    use InteractsWithNotificationAuthorization;

    public ?int $editingId = null;

    public string $search = '';

    public string $structureSearch = '';

    public string $userSearch = '';

    public array $form = [
        'category' => 'birthday',
        'trigger' => 'birthday_due',
        'template_id' => null,
        'channel' => 'database',
        'audience_targets' => 'employee',
        'structure_ids' => [],
        'user_ids' => [],
        'approval_required' => false,
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    protected function rules(): array
    {
        return [
            'form.category' => ['required', 'string', 'in:'.implode(',', NotificationTriggerRegistry::categories())],
            'form.trigger' => ['required', 'string', 'max:100'],
            'form.template_id' => ['nullable', 'integer', 'exists:notification_templates,id'],
            'form.channel' => ['required', 'string', 'in:database,mail'],
            'form.audience_targets' => ['required', 'string', 'max:255'],
            'form.structure_ids' => ['nullable', 'array'],
            'form.structure_ids.*' => ['integer'],
            'form.user_ids' => ['nullable', 'array'],
            'form.user_ids.*' => ['integer'],
            'form.approval_required' => ['required', 'boolean'],
            'form.is_active' => ['required', 'boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.category' => __('notifications::common.fields.category'),
            'form.trigger' => __('notifications::common.fields.trigger'),
            'form.template_id' => __('notifications::common.fields.template'),
            'form.channel' => __('notifications::common.fields.channel'),
            'form.audience_targets' => __('notifications::common.fields.audience_targets'),
            'form.structure_ids' => __('notifications::common.fields.department_targets'),
            'form.user_ids' => __('notifications::common.fields.specific_users'),
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => __('notifications::common.validation.required'),
            'string' => __('notifications::common.validation.string'),
            'max' => __('notifications::common.validation.max'),
            'in' => __('notifications::common.validation.in'),
            'exists' => __('notifications::common.validation.exists', ['attribute' => ':attribute']),
        ];
    }

    protected function parseIntegerList(string|array|null $value): array
    {
        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item > 0)
            ->values()
            ->all();
    }

    public function save(): void
    {
        $this->authorizeRuleManagement();
        $validated = $this->validate()['form'];
        $normalizedTargets = $this->validatedAudienceTargets($validated['audience_targets'] ?? '');

        if ($normalizedTargets === null) {
            return;
        }

        if (! array_key_exists($validated['trigger'], $this->availableTriggerOptions($validated['category']))) {
            $this->addError('form.trigger', __('notifications::common.validation.in'));

            return;
        }

        $payload = [
            'category' => $validated['category'],
            'trigger' => trim($validated['trigger']),
            'template_id' => $validated['template_id'] ?: null,
            'channel' => $validated['channel'],
            'audience_config' => [
                'targets' => $normalizedTargets,
                'structure_ids' => $this->parseIntegerList($validated['structure_ids'] ?? ''),
                'user_ids' => $this->parseIntegerList($validated['user_ids'] ?? ''),
            ],
            'approval_required' => (bool) $validated['approval_required'],
            'is_active' => (bool) $validated['is_active'],
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            NotificationRule::query()->findOrFail($this->editingId)->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            NotificationRule::query()->create($payload);
        }

        $this->resetForm();
        $this->dispatch('notification-rule-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.rule_saved'));
    }

    public function edit(int $id): void
    {
        $this->authorizeRuleManagement();
        $rule = NotificationRule::query()->findOrFail($id);

        $this->editingId = $rule->id;
        $this->form = [
            'category' => $rule->category,
            'trigger' => $rule->trigger,
            'template_id' => $rule->template_id,
            'channel' => $rule->channel,
            'audience_targets' => NotificationAudienceTargetRegistry::implodeNormalized(
                (array) data_get($rule->audience_config, 'targets', []),
                'rules',
            ),
            'structure_ids' => $this->parseIntegerList(data_get($rule->audience_config, 'structure_ids', [])),
            'user_ids' => $this->parseIntegerList(data_get($rule->audience_config, 'user_ids', [])),
            'approval_required' => (bool) $rule->approval_required,
            'is_active' => (bool) $rule->is_active,
        ];
    }

    public function delete(int $id): void
    {
        $this->authorizeRuleManagement();
        NotificationRule::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('notification-rule-changed');
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'category' => 'birthday',
            'trigger' => NotificationTriggerRegistry::firstForCategory('birthday') ?? 'birthday_due',
            'template_id' => null,
            'channel' => 'database',
            'audience_targets' => 'employee',
            'structure_ids' => [],
            'user_ids' => [],
            'approval_required' => false,
            'is_active' => true,
        ];
    }

    public function updatedFormCategory(string $category): void
    {
        $triggerOptions = $this->availableTriggerOptions($category);
        $this->form['audience_targets'] = NotificationAudienceTargetRegistry::implodeNormalized(
            $this->form['audience_targets'] ?? '',
            'rules',
        );

        if (! isset($triggerOptions[$this->form['trigger']])) {
            $this->form['trigger'] = array_key_first($triggerOptions) ?? '';
        }
    }

    public function toggleAudienceTarget(string $target): void
    {
        $selected = collect($this->parseAudienceTargets($this->form['audience_targets'] ?? ''));

        if ($selected->contains($target)) {
            $selected = $selected->reject(fn (string $value) => $value === $target)->values();
        } else {
            $selected->push($target);
        }

        $ordered = collect(array_keys($this->audienceTargetDefinitions()))
            ->filter(fn (string $value) => $selected->contains($value))
            ->values();

        $this->form['audience_targets'] = $ordered->implode(', ');

        if (! $ordered->contains('department')) {
            $this->form['structure_ids'] = [];
        }

        if (! $ordered->contains('specific_users')) {
            $this->form['user_ids'] = [];
        }
    }

    public function render()
    {
        $selectedStructureIds = $this->parseIntegerList($this->form['structure_ids'] ?? []);
        $selectedUserIds = $this->parseIntegerList($this->form['user_ids'] ?? []);

        $rules = NotificationRule::query()
            ->with('template:id,key')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('category', 'like', '%'.$this->search.'%')
                        ->orWhere('trigger', 'like', '%'.$this->search.'%')
                        ->orWhere('channel', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')
            ->limit(12)
            ->get();

        return view('notification::livewire.notification.rule-manager', [
            'rules' => $rules,
            'canManageRules' => $this->canManageRules(),
            'canApproveCampaigns' => $this->canApproveCampaigns(),
            'categories' => NotificationTriggerRegistry::categories(),
            'categoryLabels' => collect(NotificationTriggerRegistry::categories())
                ->mapWithKeys(fn (string $category) => [$category => __('notifications::common.categories.'.$category)])
                ->all(),
            'triggerOptions' => $this->availableTriggerOptions(),
            'audienceTargetDefinitions' => $this->audienceTargetDefinitions(),
            'selectedAudienceTargets' => $this->parseAudienceTargets($this->form['audience_targets'] ?? ''),
            'showDepartmentPicker' => $this->hasAudienceTarget('department'),
            'showSpecificUsersPicker' => $this->hasAudienceTarget('specific_users'),
            'templates' => NotificationTemplate::query()
                ->where('is_active', true)
                ->orderBy('key')
                ->get(['id', 'key']),
            'structureOptions' => $this->structureOptions(),
            'selectedStructureOptions' => $this->selectedStructureOptions($selectedStructureIds),
            'userOptions' => $this->userOptions(),
            'selectedUserOptions' => $this->selectedUserOptions($selectedUserIds),
        ]);
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    protected function availableTriggerOptions(?string $category = null): array
    {
        return NotificationTriggerRegistry::optionsForCategory($category ?? $this->form['category']);
    }

    protected function audienceTargetDefinitions(): array
    {
        return NotificationAudienceTargetRegistry::definitionsFor('rules');
    }

    protected function parseAudienceTargets(string|array|null $value): array
    {
        return NotificationAudienceTargetRegistry::normalize($value, 'rules');
    }

    protected function hasAudienceTarget(string $target): bool
    {
        return in_array($target, $this->parseAudienceTargets($this->form['audience_targets'] ?? ''), true);
    }

    protected function validatedAudienceTargets(string|array|null $value): ?array
    {
        $invalidTargets = NotificationAudienceTargetRegistry::invalid($value, 'rules');
        $normalized = NotificationAudienceTargetRegistry::normalize($value, 'rules');

        if ($invalidTargets !== [] || $normalized === []) {
            $this->addError('form.audience_targets', __('notifications::common.validation.invalid_audience_targets'));

            return null;
        }

        return $normalized;
    }

    protected function structureOptions(): array
    {
        return Structure::query()
            ->select('id', 'parent_id', 'name', 'level', 'code')
            ->withRecursive('parent', false)
            ->when($this->structureSearch !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->structureSearch.'%');
            })
            ->orderBy('level')
            ->orderBy('code')
            ->limit(40)
            ->get()
            ->map(fn (Structure $structure) => $this->buildStructureOption($structure))
            ->all();
    }

    protected function selectedStructureOptions(array $selectedStructureIds): array
    {
        if ($selectedStructureIds === []) {
            return [];
        }

        return Structure::query()
            ->select('id', 'parent_id', 'name')
            ->withRecursive('parent', false)
            ->whereIn('id', $selectedStructureIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Structure $structure) => Arr::only($this->buildStructureOption($structure), ['id', 'label', 'meta']))
            ->all();
    }

    protected function userOptions(): array
    {
        return User::query()
            ->select('id', 'name', 'email', 'is_active')
            ->with(['personnel:id,email,surname,name,patronymic,structure_id', 'personnel.structure:id,parent_id,name'])
            ->where('is_active', true)
            ->when($this->userSearch !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('email', 'like', '%'.$this->userSearch.'%')
                        ->orWhereHas('personnel', function ($personnel) {
                            $personnel->where('surname', 'like', '%'.$this->userSearch.'%')
                                ->orWhere('name', 'like', '%'.$this->userSearch.'%')
                                ->orWhere('patronymic', 'like', '%'.$this->userSearch.'%');
                        });
                });
            })
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'label' => $user->personnel?->fullname ?: ($user->name ?: $user->email),
                    'meta' => $user->email,
                    'group' => $user->personnel?->structure?->name ?: __('notifications::common.categories.notification'),
                ];
            })
            ->sortBy(['group', 'label'])
            ->values()
            ->all();
    }

    protected function selectedUserOptions(array $selectedUserIds): array
    {
        if ($selectedUserIds === []) {
            return [];
        }

        return User::query()
            ->select('id', 'name', 'email', 'is_active')
            ->with(['personnel:id,email,surname,name,patronymic'])
            ->whereIn('id', $selectedUserIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $user->personnel?->fullname ?: ($user->name ?: $user->email),
                'meta' => $user->email,
            ])
            ->all();
    }

    protected function buildStructureOption(Structure $structure): array
    {
        $path = $structure->fullStructurePath(false);
        $segments = array_values(array_filter(array_map('trim', explode(' / ', $path))));
        $label = array_pop($segments) ?: $structure->name;
        $meta = count($segments) ? implode(' / ', $segments) : null;

        return [
            'id' => $structure->id,
            'label' => $label,
            'meta' => $meta,
            'group' => null,
            'indent' => max(0, count($segments)),
        ];
    }
}
