<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationTriggerRegistry;
use App\Modules\Notifications\Support\NotificationTemplateRenderer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;

class OverviewPanel extends Component
{
    use InteractsWithNotificationAuthorization;

    protected ?bool $managementTablesReadyCache = null;

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    #[On('notification-template-changed')]
    #[On('notification-rule-changed')]
    #[On('notification-campaign-changed')]
    public function refreshPanel(): void
    {
        $this->managementTablesReadyCache = null;
    }

    public function seedBirthdayStarter(): void
    {
        $this->authorizeTemplateManagement();
        $template = NotificationTemplate::query()->updateOrCreate(
            ['key' => 'birthday.default'],
            [
                'category' => 'birthday',
                'channel' => 'database',
                'format' => 'text',
                'subject_template' => __('notifications::common.mail.subject_birthday').': {{ name }}',
                'body_template' => '{{ name }} - {{ position }} - {{ structure }} - {{ birthday_label }}',
                'variables_schema' => ['name', 'position', 'structure', 'birthday_label'],
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        NotificationRule::query()->updateOrCreate(
            [
                'category' => 'birthday',
                'trigger' => NotificationTriggerRegistry::trigger('birthday') ?? 'birthday_due',
                'channel' => 'database',
            ],
            [
                'template_id' => $template->id,
                'audience_config' => ['targets' => ['employee', 'same_structure', 'hr']],
                'approval_required' => false,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('notification-template-changed');
        $this->dispatch('notification-rule-changed');
    }

    public function seedPositionChangeStarter(): void
    {
        $this->authorizeTemplateManagement();
        $template = NotificationTemplate::query()->updateOrCreate(
            ['key' => 'position-change.default'],
            [
                'category' => 'position_change',
                'channel' => 'database',
                'format' => 'text',
                'subject_template' => __('notifications::common.mail.subject_position_change').': {{ name }}',
                'body_template' => '{{ name }} - {{ old_position }} → {{ new_position }} / {{ old_structure }} → {{ new_structure }} / {{ change_reason }} / {{ effective_date }}',
                'variables_schema' => ['name', 'old_position', 'new_position', 'effective_date', 'old_structure', 'new_structure', 'change_reason'],
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        NotificationRule::query()->updateOrCreate(
            [
                'category' => 'position_change',
                'trigger' => NotificationTriggerRegistry::trigger('position_change') ?? 'position_changed',
                'channel' => 'database',
            ],
            [
                'template_id' => $template->id,
                'audience_config' => ['targets' => ['employee', 'hr', 'notification_permission']],
                'approval_required' => true,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('notification-template-changed');
        $this->dispatch('notification-rule-changed');
    }

    public function seedHolidayStarter(): void
    {
        $this->authorizeTemplateManagement();
        $template = NotificationTemplate::query()->updateOrCreate(
            ['key' => 'holiday.default'],
            [
                'category' => 'holiday',
                'channel' => 'database',
                'format' => 'text',
                'subject_template' => __('notifications::common.mail.subject_holiday').': {{ holiday_name }}',
                'body_template' => '{{ holiday_name }} - {{ holiday_date }} - {{ duration }} - {{ scope }} - {{ holiday_rules }}',
                'variables_schema' => ['holiday_name', 'holiday_date', 'duration', 'scope', 'holiday_rules'],
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        NotificationRule::query()->updateOrCreate(
            [
                'category' => 'holiday',
                'trigger' => NotificationTriggerRegistry::trigger('holiday') ?? 'holiday_due',
                'channel' => 'database',
            ],
            [
                'template_id' => $template->id,
                'audience_config' => ['targets' => ['all_employees']],
                'approval_required' => false,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('notification-template-changed');
        $this->dispatch('notification-rule-changed');
    }

    public function render()
    {
        return view('notification::livewire.notification.overview-panel', [
            'managementTablesReady' => $this->managementTablesReady(),
            'stats' => $this->stats(),
            'previews' => $this->previews(),
            'pillars' => [
                [
                    'title' => __('notifications::common.titles.module'),
                    'body' => __('notifications::common.helpers.module_summary'),
                ],
                [
                    'title' => __('notifications::common.titles.workstreams'),
                    'body' => __('notifications::common.helpers.tabs_summary'),
                ],
                [
                    'title' => __('notifications::common.titles.first_flows'),
                    'body' => __('notifications::common.titles.flow_starter'),
                ],
            ],
            'phases' => [
                [
                    'code' => __('notifications::common.phases.p1_code'),
                    'title' => __('notifications::common.phases.p1_title'),
                    'items' => __('notifications::common.phases.p1_items'),
                ],
                [
                    'code' => __('notifications::common.phases.p2_code'),
                    'title' => __('notifications::common.phases.p2_title'),
                    'items' => __('notifications::common.phases.p2_items'),
                ],
                [
                    'code' => __('notifications::common.phases.p3_code'),
                    'title' => __('notifications::common.phases.p3_title'),
                    'items' => __('notifications::common.phases.p3_items'),
                ],
                [
                    'code' => __('notifications::common.phases.p4_code'),
                    'title' => __('notifications::common.phases.p4_title'),
                    'items' => __('notifications::common.phases.p4_items'),
                ],
            ],
            'workstreams' => [
                __('notifications::common.workstreams.rules'),
                __('notifications::common.workstreams.templates'),
                __('notifications::common.workstreams.audiences'),
                __('notifications::common.workstreams.approval'),
                __('notifications::common.workstreams.history'),
                __('notifications::common.workstreams.channels'),
            ],
            'firstFlows' => [
                [
                    'title' => __('notifications::common.flows.birthday_title'),
                    'detail' => __('notifications::common.flows.birthday_detail'),
                ],
                [
                    'title' => __('notifications::common.flows.position_change_title'),
                    'detail' => __('notifications::common.flows.position_change_detail'),
                ],
                [
                    'title' => __('notifications::common.flows.announcement_title'),
                    'detail' => __('notifications::common.flows.announcement_detail'),
                ],
                [
                    'title' => __('notifications::common.flows.holiday_title'),
                    'detail' => __('notifications::common.flows.holiday_detail'),
                ],
            ],
            'starterFlows' => [
                'birthday' => $this->starterPreview('birthday.default', 'birthday', NotificationTriggerRegistry::trigger('birthday') ?? 'birthday_due', [
                    'name' => 'Murad Əliyev',
                    'position' => 'Baş məsləhətçi',
                    'structure' => 'İnsan resursları şöbəsi',
                    'birthday_label' => '16.03.2026',
                ]),
                'position_change' => $this->starterPreview('position-change.default', 'position_change', NotificationTriggerRegistry::trigger('position_change') ?? 'position_changed', [
                    'name' => 'Leyla Məmmədova',
                    'old_position' => 'Məsləhətçi',
                    'new_position' => 'Aparıcı məsləhətçi',
                    'old_structure' => 'Maliyyə şöbəsi',
                    'new_structure' => 'İnsan resursları şöbəsi',
                    'change_reason' => 'Daxili rotasiya',
                    'effective_date' => now()->format('d.m.Y'),
                ]),
                'holiday' => $this->starterPreview('holiday.default', 'holiday', NotificationTriggerRegistry::trigger('holiday') ?? 'holiday_due', [
                    'holiday_name' => 'Novruz bayramı',
                    'holiday_date' => '20.03.2026',
                    'duration' => '3 gün',
                    'scope' => 'Bütün əməkdaşlar',
                    'holiday_rules' => 'Rəsmi qeyri-iş günləri',
                ]),
            ],
        ]);
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    protected function stats(): array
    {
        if (! $this->managementTablesReady()) {
            return [
                ['label' => __('notifications::common.stats.templates'), 'value' => 0, 'detail' => __('notifications::common.stats.templates_detail')],
                ['label' => __('notifications::common.stats.rules'), 'value' => 0, 'detail' => __('notifications::common.stats.rules_detail')],
                ['label' => __('notifications::common.stats.queued'), 'value' => 0, 'detail' => __('notifications::common.stats.queued_detail')],
                ['label' => __('notifications::common.stats.failed'), 'value' => 0, 'detail' => __('notifications::common.stats.failed_detail')],
            ];
        }

        return [
            [
                'label' => __('notifications::common.stats.templates'),
                'value' => NotificationTemplate::query()->where('is_active', true)->count(),
                'detail' => __('notifications::common.stats.templates_detail'),
            ],
            [
                'label' => __('notifications::common.stats.rules'),
                'value' => NotificationRule::query()->where('is_active', true)->count(),
                'detail' => __('notifications::common.stats.rules_detail'),
            ],
            [
                'label' => __('notifications::common.stats.queued'),
                'value' => NotificationCampaign::query()
                    ->whereIn('status', ['draft', 'queued'])
                    ->orWhere('approval_status', 'pending')
                    ->count(),
                'detail' => __('notifications::common.stats.queued_detail'),
            ],
            [
                'label' => __('notifications::common.stats.failed'),
                'value' => NotificationDispatch::query()->where('status', 'failed')->count(),
                'detail' => __('notifications::common.stats.failed_detail'),
            ],
        ];
    }

    protected function managementTablesReady(): bool
    {
        if ($this->managementTablesReadyCache !== null) {
            return $this->managementTablesReadyCache;
        }

        foreach ([
            'notification_templates',
            'notification_rules',
            'notification_campaigns',
            'notification_dispatches',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return $this->managementTablesReadyCache = false;
            }
        }

        return $this->managementTablesReadyCache = true;
    }

    protected function previews(): array
    {
        if (! $this->managementTablesReady()) {
            return [
                'templates' => collect(),
                'rules' => collect(),
                'campaigns' => collect(),
                'failures' => collect(),
            ];
        }

        return [
            'templates' => NotificationTemplate::query()
                ->latest('id')
                ->limit(4)
                ->get(['id', 'key', 'category', 'channel', 'format', 'is_active']),
            'rules' => NotificationRule::query()
                ->latest('id')
                ->limit(4)
                ->get(['id', 'category', 'trigger', 'channel', 'approval_required', 'is_active']),
            'campaigns' => NotificationCampaign::query()
                ->where(function ($query) {
                    $query->whereIn('status', ['draft', 'queued'])
                        ->orWhere('approval_status', 'pending');
                })
                ->latest('id')
                ->limit(4)
                ->get(['id', 'title', 'category', 'status', 'approval_status', 'scheduled_at']),
            'failures' => NotificationDispatch::query()
                ->where('status', 'failed')
                ->latest('id')
                ->limit(4)
                ->get(['id', 'campaign_id', 'channel', 'status', 'failed_at', 'error_message']),
        ];
    }

    protected function starterPreview(string $templateKey, string $category, string $trigger, array $samplePayload): array
    {
        if (! $this->managementTablesReady()) {
            return [
                'template_key' => $templateKey,
                'trigger' => $trigger,
                'exists' => false,
                'rule_exists' => false,
                'subject' => null,
                'body' => null,
                'channel' => 'database',
                'format' => 'text',
                'targets' => [],
                'audience_labels' => [],
                'meta_items' => $this->starterMetaItems($category, $samplePayload),
                'approval_required' => false,
            ];
        }

        $template = NotificationTemplate::query()
            ->where('key', $templateKey)
            ->first(['id', 'key', 'channel', 'format', 'subject_template', 'body_template']);

        $rule = NotificationRule::query()
            ->where('category', $category)
            ->where('trigger', $trigger)
            ->latest('id')
            ->first(['id', 'audience_config', 'approval_required']);

        $renderer = app(NotificationTemplateRenderer::class);

        return [
            'template_key' => $templateKey,
            'trigger' => $trigger,
            'exists' => $template !== null,
            'rule_exists' => $rule !== null,
            'subject' => $template?->subject_template
                ? $renderer->render($template->subject_template, $samplePayload)
                : null,
            'body' => $template?->body_template
                ? $renderer->render($template->body_template, $samplePayload)
                : null,
            'channel' => $template?->channel ?? 'database',
            'format' => $template?->format ?? 'text',
            'targets' => $targets = Arr::wrap(data_get($rule?->audience_config, 'targets', [])),
            'audience_labels' => $this->starterAudienceLabels($targets),
            'meta_items' => $this->starterMetaItems($category, $samplePayload),
            'approval_required' => (bool) $rule?->approval_required,
        ];
    }

    protected function starterAudienceLabels(array $targets): array
    {
        return collect($targets)
            ->map(fn (string $target): string => __('notifications::common.audience_targets.'.$target.'.label'))
            ->filter()
            ->values()
            ->all();
    }

    protected function starterMetaItems(string $category, array $samplePayload): array
    {
        return match ($category) {
            'birthday' => array_values(array_filter([
                data_get($samplePayload, 'birthday_label'),
                data_get($samplePayload, 'position'),
                data_get($samplePayload, 'structure'),
            ])),
            'position_change' => array_values(array_filter([
                trim(implode(' → ', array_filter([
                    data_get($samplePayload, 'old_position'),
                    data_get($samplePayload, 'new_position'),
                ]))),
                data_get($samplePayload, 'effective_date'),
                data_get($samplePayload, 'new_structure'),
            ])),
            'holiday' => array_values(array_filter([
                data_get($samplePayload, 'holiday_date'),
                data_get($samplePayload, 'duration'),
                data_get($samplePayload, 'scope'),
            ])),
            default => [],
        };
    }
}
