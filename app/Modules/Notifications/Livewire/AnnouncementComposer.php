<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationRule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationAudienceTargetRegistry;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use App\Modules\Notifications\Support\NotificationTriggerRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AnnouncementComposer extends Component
{
    use InteractsWithNotificationAuthorization;

    public string $structureSearch = '';

    public string $userSearch = '';

    protected ?NotificationRule $matchedRuleCache = null;

    public array $form = [
        'category' => 'announcement',
        'template_id' => null,
        'title' => '',
        'body' => '',
        'holiday_name' => '',
        'holiday_date' => '',
        'duration' => '1 gün',
        'scope' => '',
        'holiday_rules' => '',
        'channel' => 'database',
        'format' => 'text',
        'audience_targets' => 'all_employees',
        'structure_ids' => [],
        'user_ids' => [],
        'schedule_mode' => 'send_now',
        'scheduled_at' => '',
        'approval_required' => true,
        'send_now' => true,
    ];

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
        $this->applyRuleDefaults();
    }

    protected function rules(): array
    {
        $rules = [
            'form.category' => ['required', 'string', 'in:announcement,holiday'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.body' => ['nullable', 'string', 'max:5000'],
            'form.holiday_name' => ['nullable', 'string', 'max:255'],
            'form.holiday_date' => ['nullable', 'date'],
            'form.duration' => ['nullable', 'string', 'max:100'],
            'form.scope' => ['nullable', 'string', 'max:255'],
            'form.holiday_rules' => ['nullable', 'string', 'max:1000'],
            'form.channel' => ['required', 'string', 'in:database,mail'],
            'form.format' => ['required', 'string', 'in:text,html'],
            'form.audience_targets' => ['required', 'string', 'max:255'],
            'form.structure_ids' => ['nullable', 'array'],
            'form.structure_ids.*' => ['integer'],
            'form.user_ids' => ['nullable', 'array'],
            'form.user_ids.*' => ['integer'],
            'form.schedule_mode' => ['required', 'string', 'in:send_now,after_2_hours,tomorrow_morning,holiday_eve,holiday_morning,custom'],
            'form.scheduled_at' => ['nullable', 'date'],
            'form.approval_required' => ['required', 'boolean'],
            'form.send_now' => ['required', 'boolean'],
        ];

        if ($this->form['category'] === 'announcement') {
            $rules['form.body'][0] = 'required';
        }

        if ($this->form['category'] === 'holiday') {
            $rules['form.holiday_name'][0] = 'required';
            $rules['form.holiday_date'][0] = 'required';
            $rules['form.scope'][0] = 'required';
        }

        if (($this->form['schedule_mode'] ?? 'send_now') === 'custom') {
            $rules['form.scheduled_at'][0] = 'required';
        }

        return $rules;
    }

    public function updatedFormCategory(): void
    {
        $this->matchedRuleCache = null;
        $this->applyRuleDefaults();

        if (! array_key_exists($this->form['schedule_mode'], $this->scheduleModes())) {
            $this->form['schedule_mode'] = 'send_now';
            $this->form['scheduled_at'] = '';
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

    protected function validationAttributes(): array
    {
        return [
            'form.category' => __('notifications::common.fields.category'),
            'form.template_id' => __('notifications::common.fields.template'),
            'form.title' => __('notifications::common.fields.title'),
            'form.body' => __('notifications::common.fields.body'),
            'form.holiday_name' => __('notifications::common.fields.holiday_name'),
            'form.holiday_date' => __('notifications::common.fields.holiday_date'),
            'form.duration' => __('notifications::common.fields.duration'),
            'form.scope' => __('notifications::common.fields.scope'),
            'form.holiday_rules' => __('notifications::common.fields.holiday_rules'),
            'form.channel' => __('notifications::common.fields.channel'),
            'form.format' => __('notifications::common.fields.format'),
            'form.audience_targets' => __('notifications::common.fields.audience_targets'),
            'form.structure_ids' => __('notifications::common.fields.department_targets'),
            'form.user_ids' => __('notifications::common.fields.specific_users'),
            'form.schedule_mode' => __('notifications::common.fields.schedule_mode'),
            'form.scheduled_at' => __('notifications::common.fields.scheduled_at'),
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => __('notifications::common.validation.required'),
            'string' => __('notifications::common.validation.string'),
            'max' => __('notifications::common.validation.max'),
            'in' => __('notifications::common.validation.in'),
            'date' => __('notifications::common.validation.date'),
        ];
    }

    public function save(): void
    {
        $this->authorizeCampaignManagement();

        $payload = $this->validate()['form'];
        $normalizedTargets = $this->validatedAudienceTargets($payload['audience_targets'] ?? '');

        if ($normalizedTargets === null) {
            return;
        }

        $payload['audience_targets'] = implode(', ', $normalizedTargets);
        $payload['scheduled_at'] = $this->resolveScheduledAt($payload);
        app(NotificationCampaignDispatcher::class)->createAnnouncementCampaign($payload);

        $this->resetForm();

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_saved'));
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->form = [
            'category' => 'announcement',
            'template_id' => null,
            'title' => '',
            'body' => '',
            'holiday_name' => '',
            'holiday_date' => '',
            'duration' => '1 gün',
            'scope' => '',
            'holiday_rules' => '',
            'channel' => 'database',
            'format' => 'text',
            'audience_targets' => 'all_employees',
            'structure_ids' => [],
            'user_ids' => [],
            'schedule_mode' => 'send_now',
            'scheduled_at' => '',
            'approval_required' => true,
            'send_now' => true,
        ];

        $this->matchedRuleCache = null;
        $this->applyRuleDefaults();
    }

    protected function resolveScheduledAt(array $payload): ?string
    {
        $scheduleMode = (string) ($payload['schedule_mode'] ?? 'send_now');

        if ($scheduleMode === 'custom') {
            return filled($payload['scheduled_at'] ?? null)
                ? Carbon::parse((string) $payload['scheduled_at'])->format('Y-m-d H:i:s')
                : null;
        }

        return match ($scheduleMode) {
            'after_2_hours' => now()->addHours(2)->startOfMinute()->format('Y-m-d H:i:s'),
            'tomorrow_morning' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'holiday_eve' => filled($payload['holiday_date'] ?? null)
                ? Carbon::parse((string) $payload['holiday_date'])->subDay()->setTime(18, 0)->format('Y-m-d H:i:s')
                : null,
            'holiday_morning' => filled($payload['holiday_date'] ?? null)
                ? Carbon::parse((string) $payload['holiday_date'])->setTime(9, 0)->format('Y-m-d H:i:s')
                : null,
            default => null,
        };
    }

    protected function scheduleModes(): array
    {
        $modes = [
            'send_now' => __('notifications::common.schedule_modes.send_now'),
            'after_2_hours' => __('notifications::common.schedule_modes.after_2_hours'),
            'tomorrow_morning' => __('notifications::common.schedule_modes.tomorrow_morning'),
            'custom' => __('notifications::common.schedule_modes.custom'),
        ];

        if ($this->form['category'] === 'holiday') {
            $modes = [
                'send_now' => __('notifications::common.schedule_modes.send_now'),
                'holiday_eve' => __('notifications::common.schedule_modes.holiday_eve'),
                'holiday_morning' => __('notifications::common.schedule_modes.holiday_morning'),
                'custom' => __('notifications::common.schedule_modes.custom'),
            ];
        }

        return $modes;
    }

    protected function resolvedSchedulePreviewText(): string
    {
        $resolved = $this->resolveScheduledAt($this->form);

        if (! $resolved) {
            return __('notifications::common.helpers.schedule_preview_now');
        }

        return Carbon::parse($resolved)->format('d.m.Y H:i');
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

    protected function parseAudienceTargets(string|array|null $value): array
    {
        return NotificationAudienceTargetRegistry::normalize($value, 'announcements');
    }

    protected function hasAudienceTarget(string $target): bool
    {
        return in_array($target, $this->parseAudienceTargets($this->form['audience_targets'] ?? ''), true);
    }

    protected function audienceTargetDefinitions(): array
    {
        return NotificationAudienceTargetRegistry::definitionsFor('announcements');
    }

    protected function audienceTargetLabel(string $target): string
    {
        return NotificationAudienceTargetRegistry::label($target);
    }

    protected function audienceSelectionSummary(array $audienceTargets, array $selectedStructureIds, array $selectedUserIds): array
    {
        $highlights = collect($audienceTargets)
            ->map(fn (string $target) => $this->audienceTargetLabel($target))
            ->values()
            ->all();

        $facts = [];

        if (in_array('department', $audienceTargets, true)) {
            $facts[] = __('notifications::common.helpers.department_selection_summary', ['count' => count($selectedStructureIds)]);
        }

        if (in_array('specific_users', $audienceTargets, true)) {
            $facts[] = __('notifications::common.helpers.user_selection_summary', ['count' => count($selectedUserIds)]);
        }

        if (($this->form['approval_required'] ?? false) === true) {
            $facts[] = __('notifications::common.helpers.approval_summary');
        }

        return [
            'highlights' => $highlights,
            'facts' => $facts,
        ];
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    public function render()
    {
        $selectedStructureIds = $this->parseIntegerList($this->form['structure_ids'] ?? []);
        $selectedUserIds = $this->parseIntegerList($this->form['user_ids'] ?? []);
        $matchedRule = $this->matchedRule();
        $audienceTargets = $this->parseAudienceTargets($this->form['audience_targets'] ?? '');
        $needsStructurePicker = in_array('department', $audienceTargets, true);
        $needsUserPicker = in_array('specific_users', $audienceTargets, true);

        return view('notification::livewire.notification.announcement-composer', [
            'canManageCampaigns' => $this->canManageCampaigns(),
            'matchedRuleLabel' => $matchedRule
                ? __('notifications::common.categories.'.$matchedRule->category).' / '.__('notifications::common.triggers.'.$matchedRule->trigger)
                : null,
            'audienceTargetDefinitions' => $this->audienceTargetDefinitions(),
            'selectedAudienceTargets' => $audienceTargets,
            'audienceSelectionSummary' => $this->audienceSelectionSummary($audienceTargets, $selectedStructureIds, $selectedUserIds),
            'needsStructurePicker' => $needsStructurePicker,
            'needsUserPicker' => $needsUserPicker,
            'structureOptions' => $needsStructurePicker ? $this->structureOptions() : [],
            'selectedStructureOptions' => $needsStructurePicker ? $this->selectedStructureOptions($selectedStructureIds) : [],
            'userOptions' => $needsUserPicker ? $this->userOptions() : [],
            'selectedUserOptions' => $needsUserPicker ? $this->selectedUserOptions($selectedUserIds) : [],
            'scheduleModes' => $this->scheduleModes(),
            'resolvedSchedulePreview' => $this->resolvedSchedulePreviewText(),
        ]);
    }

    protected function applyRuleDefaults(): void
    {
        $rule = $this->activeRuleForCategory($this->form['category']);

        if (! $rule) {
            return;
        }

        $this->form['template_id'] = $rule->template_id;
        $this->form['channel'] = $rule->channel ?: $this->form['channel'];
        $this->form['approval_required'] = (bool) $rule->approval_required;
        $normalizedTargets = NotificationAudienceTargetRegistry::implodeNormalized(
            (array) data_get($rule->audience_config, 'targets', []),
            'announcements',
        );

        $this->form['audience_targets'] = $normalizedTargets !== ''
            ? $normalizedTargets
            : $this->form['audience_targets'];
        $this->form['structure_ids'] = (array) data_get($rule->audience_config, 'structure_ids', []);
        $this->form['user_ids'] = (array) data_get($rule->audience_config, 'user_ids', []);
    }

    protected function validatedAudienceTargets(string|array|null $value): ?array
    {
        $invalidTargets = NotificationAudienceTargetRegistry::invalid($value, 'announcements');
        $normalized = NotificationAudienceTargetRegistry::normalize($value, 'announcements');

        if ($invalidTargets !== [] || $normalized === []) {
            $this->addError('form.audience_targets', __('notifications::common.validation.invalid_audience_targets'));

            return null;
        }

        return $normalized;
    }

    protected function activeRuleForCategory(string $category): ?NotificationRule
    {
        $candidateTriggers = array_filter([
            NotificationTriggerRegistry::trigger($category, 'manual'),
            NotificationTriggerRegistry::trigger($category, 'scheduled'),
            NotificationTriggerRegistry::trigger($category, 'system'),
        ]);

        return NotificationRule::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->when($candidateTriggers !== [], fn ($query) => $query->whereIn('trigger', $candidateTriggers))
            ->orderByRaw('template_id is null')
            ->latest('id')
            ->first();
    }

    protected function matchedRule(): ?NotificationRule
    {
        if ($this->matchedRuleCache === null) {
            $this->matchedRuleCache = $this->activeRuleForCategory($this->form['category']);
        }

        return $this->matchedRuleCache;
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
