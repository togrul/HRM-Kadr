@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');

    $processDots = [
        'onboarding' => 'bg-[#0ea5e9]',
        'probation' => 'bg-[#f59e0b]',
        'movement' => 'bg-[#8b5cf6]',
        'offboarding' => 'bg-[#f43f5e]',
        'profile_change' => 'bg-[#a1a1aa]',
    ];

    $statusDots = [
        'planned' => 'bg-[#a1a1aa]',
        'in_progress' => 'bg-[#0ea5e9]',
        'blocked' => 'bg-[#f43f5e]',
        'completed' => 'bg-[#10b981]',
        'cancelled' => 'bg-[#d4d4d8]',
    ];

    $lifecycleMetrics = ['active_templates', 'active_events', 'overdue_tasks', 'probation_queue', 'movement_queue', 'offboarding_queue'];

    $typeTone = fn (string $type): string => match ($type) {
        'onboarding' => 'sky',
        'probation' => 'amber',
        'movement' => 'violet',
        'offboarding' => 'rose',
        default => 'secondary',
    };

    $eventTone = fn (array $event): string => $event['is_overdue'] || $event['status'] === 'blocked'
        ? 'rose'
        : match ($event['status']) {
            'completed' => 'green',
            'cancelled' => 'secondary',
            'planned' => 'sky',
            default => 'amber',
        };

    $canManage = (bool) auth()->user()?->can('manage-employee-lifecycle');
    $startTabs = ['plan' => 'forms.launch_plan', 'probation' => 'forms.probation', 'movement' => 'forms.movement', 'offboarding' => 'forms.offboarding'];
    $panelTitle = match ($panel) {
        'templates' => __('employee-lifecycle::dashboard.labels.plan_templates'),
        'launch' => __('employee-lifecycle::dashboard.actions.start_process'),
        'complete' => __('employee-lifecycle::dashboard.forms.completion'),
        default => '',
    };
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('employee-lifecycle::dashboard.kicker')"
            :subtitle="__('employee-lifecycle::dashboard.labels.active_process_count', ['count' => $num($summary['active_events'] ?? 0)])"
        >
            <x-context-panel.section :title="__('employee-lifecycle::dashboard.fields.type')">
                <x-context-panel.item
                    wire:click.prevent="$set('type', '')"
                    :active="$type === ''"
                    :count="$num($typeCounts[''] ?? 0)"
                >{{ __('employee-lifecycle::dashboard.filters.all_types') }}</x-context-panel.item>

                @foreach ($processDots as $option => $dot)
                    <x-context-panel.item
                        wire:key="lifecycle-type-{{ $option }}"
                        wire:click.prevent="$set('type', '{{ $option }}')"
                        :active="$type === $option"
                        :dot="$dot"
                        :count="$num($typeCounts[$option] ?? 0)"
                    >{{ __('employee-lifecycle::dashboard.types.'.$option) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :title="__('employee-lifecycle::dashboard.fields.status')">
                <x-context-panel.item
                    wire:click.prevent="$set('status', '')"
                    :active="$status === ''"
                    :count="$num($statusCounts[''] ?? 0)"
                >{{ __('employee-lifecycle::dashboard.filters.all_statuses') }}</x-context-panel.item>

                @foreach ($statusDots as $option => $dot)
                    <x-context-panel.item
                        wire:key="lifecycle-status-{{ $option }}"
                        wire:click.prevent="$set('status', '{{ $option }}')"
                        :active="$status === $option"
                        :dot="$dot"
                        :count="$num($statusCounts[$option] ?? 0)"
                    >{{ __('employee-lifecycle::dashboard.statuses.'.$option) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :padded="false">
                <div class="p-2.5">
                    <x-context-panel.meta :items="collect($lifecycleMetrics)->map(fn ($metric) => [
                        'label' => __('employee-lifecycle::dashboard.summary.'.$metric),
                        'value' => $num($summary[$metric] ?? 0),
                    ])->all()" />
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('employee-lifecycle::dashboard.title')"
        :breadcrumb="__('employee-lifecycle::dashboard.kicker')"
        :guide-title="$canManage ? null : __('employee-lifecycle::dashboard.labels.management_locked')"
        :guide-description="$canManage ? null : __('employee-lifecycle::dashboard.labels.management_locked_note')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/><path d="m19 8-3 3 3 3"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($summary['active_events'] ?? 0)" :label="__('employee-lifecycle::dashboard.summary.active_events')" />
            <x-page-header.stat :value="$num($summary['overdue_tasks'] ?? 0)" :label="__('employee-lifecycle::dashboard.summary.overdue_tasks')" tone="rose" />
            <x-page-header.stat :value="$num($summary['probation_queue'] ?? 0)" :label="__('employee-lifecycle::dashboard.summary.probation_queue')" tone="amber" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">
                {{ __('employee-lifecycle::dashboard.actions.reset_filters') }}
            </x-pill-button>

            @if ($canManage)
                <x-pill-button wire:click="openPanel('templates')">
                    {{ __('employee-lifecycle::dashboard.actions.manage_templates') }}
                </x-pill-button>

                <x-pill-button wire:click="openPanel('complete')">
                    {{ __('employee-lifecycle::dashboard.actions.complete_processes') }}
                </x-pill-button>

                <x-pill-button variant="primary" wire:click="openPanel('launch')">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('employee-lifecycle::dashboard.actions.start_process') }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <p class="max-w-3xl text-[13px] leading-6 text-ink-muted">{{ __('employee-lifecycle::dashboard.description') }}</p>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="w-full sm:max-w-[420px]">
                    <x-ui.input
                        icon="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('employee-lifecycle::dashboard.placeholders.search') }}"
                    />
                </div>
                <p class="hrm-num shrink-0 text-[11.5px] text-ink-faint">
                    {{ __('employee-lifecycle::dashboard.labels.result_count', ['count' => $num($events->count())]) }}
                </p>
            </div>

            <x-table.tbl :headers="[
                __('employee-lifecycle::dashboard.columns.employee'),
                __('employee-lifecycle::dashboard.columns.process'),
                __('employee-lifecycle::dashboard.columns.template'),
                __('employee-lifecycle::dashboard.columns.start'),
                __('employee-lifecycle::dashboard.columns.owner'),
                __('employee-lifecycle::dashboard.columns.deadline'),
                __('employee-lifecycle::dashboard.columns.status'),
            ]">
                @forelse ($events as $event)
                    <tr wire:key="lifecycle-event-{{ $event['id'] }}">
                        <x-table.td standart-width>
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="(string) $event['employee_name']" :tone="$event['is_overdue'] ? 'rose' : 'neutral'" />
                                <div class="min-w-0 max-w-[240px] leading-tight">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $event['employee_name'] }}</p>
                                    <p class="truncate text-[11px] text-ink-faint" title="{{ $event['structure_name'] }} › {{ $event['position_name'] }}">
                                        {{ $event['structure_name'] }} <span class="px-0.5">›</span> {{ $event['position_name'] }}
                                    </p>
                                </div>
                            </div>
                        </x-table.td>

                        <x-table.td standart-width>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-small-badge :mode="$typeTone($event['type'])">
                                    {{ __('employee-lifecycle::dashboard.types.'.$event['type']) }}
                                </x-small-badge>
                                @if ($event['source_is_order'])
                                    <x-small-badge mode="secondary">{{ $event['source_label'] }}</x-small-badge>
                                @endif
                            </div>
                            <p class="mt-1 max-w-[220px] truncate text-[11.5px] text-ink-faint" title="{{ $event['title'] }}">{{ $event['title'] }}</p>
                        </x-table.td>

                        <x-table.td standart-width>
                            <p class="max-w-[180px] truncate text-[13px] text-ink-soft" title="{{ $event['template_name'] }}">
                                {{ $event['template_name'] ?: __('employee-lifecycle::dashboard.labels.no_template') }}
                            </p>
                        </x-table.td>

                        <x-table.td>
                            <span class="hrm-num text-[13px] text-ink-soft">{{ $event['effective_date'] ?? '—' }}</span>
                        </x-table.td>

                        <x-table.td>
                            <span class="text-[13px] text-ink-soft">{{ $event['owner_name'] }}</span>
                        </x-table.td>

                        <x-table.td>
                            <span @class(['hrm-num text-[13px]', 'text-ink-soft' => ! $event['is_overdue'], 'font-semibold text-[#be123c]' => $event['is_overdue']])>
                                {{ $event['deadline_at'] ?? '—' }}
                            </span>
                            @if ($event['is_overdue'])
                                <p class="text-[11px] font-medium text-[#be123c]">{{ __('employee-lifecycle::dashboard.labels.overdue') }}</p>
                            @endif
                        </x-table.td>

                        <x-table.td>
                            <x-small-badge :mode="$eventTone($event)" dot>
                                {{ __('employee-lifecycle::dashboard.statuses.'.$event['status']) }}
                            </x-small-badge>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10">
                            <x-ui.empty-state icon="icons.info-circle-icon" :message="__('employee-lifecycle::dashboard.empty')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </section>

        {{-- ===================== queues ===================== --}}
        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-4">
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee-lifecycle::dashboard.labels.probation_reviews') }}</h2>
                </div>
                <div class="hrm-scroll max-h-[340px] divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($probationReviews as $review)
                        <div wire:key="lifecycle-probation-{{ $review['id'] }}" class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $review['employee_name'] }}</p>
                                <span @class(['hrm-num shrink-0 text-[11.5px]', 'text-ink-faint' => ! $review['is_overdue'], 'font-semibold text-[#be123c]' => $review['is_overdue']])>{{ $review['review_due_at'] }}</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-4 text-ink-faint">
                                {{ __('employee-lifecycle::dashboard.labels.probation_meta', ['manager' => $review['manager_name'], 'hr_user' => $review['reviewer_name']]) }}
                            </p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee-lifecycle::dashboard.labels.movements') }}</h2>
                </div>
                <div class="hrm-scroll max-h-[340px] divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($movements as $movement)
                        <div wire:key="lifecycle-movement-{{ $movement['id'] }}" class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $movement['employee_name'] }}</p>
                                <span @class(['hrm-num shrink-0 text-[11.5px]', 'text-ink-faint' => ! $movement['is_overdue'], 'font-semibold text-[#be123c]' => $movement['is_overdue']])>{{ $movement['effective_date'] }}</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-4 text-ink-faint">
                                {{ $movement['current_structure_name'] }} <span class="px-0.5">→</span> {{ $movement['target_structure_name'] }}
                            </p>
                            <div class="mt-1.5">
                                <x-small-badge mode="violet">{{ $movement['movement_type_label'] }}</x-small-badge>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee-lifecycle::dashboard.labels.offboarding_cases') }}</h2>
                </div>
                <div class="hrm-scroll max-h-[340px] divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($offboardingCases as $case)
                        <div wire:key="lifecycle-offboarding-{{ $case['id'] }}" class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $case['employee_name'] }}</p>
                                <span @class(['hrm-num shrink-0 text-[11.5px]', 'text-ink-faint' => ! $case['is_overdue'], 'font-semibold text-[#be123c]' => $case['is_overdue']])>{{ $case['last_working_date'] }}</span>
                            </div>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <x-small-badge :mode="$case['status'] === 'completed' ? 'green' : ($case['is_overdue'] ? 'rose' : 'amber')" dot>
                                    {{ __('employee-lifecycle::dashboard.offboarding_statuses.'.$case['status']) }}
                                </x-small-badge>
                                <x-small-badge :mode="$case['exit_interview_done'] ? 'green' : 'secondary'">
                                    {{ $case['exit_interview_done'] ? __('employee-lifecycle::dashboard.labels.exit_interview_done') : __('employee-lifecycle::dashboard.labels.exit_interview_pending') }}
                                </x-small-badge>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee-lifecycle::dashboard.labels.overdue_tasks') }}</h2>
                </div>
                <div class="hrm-scroll max-h-[340px] divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($overdueTasks as $task)
                        <div wire:key="lifecycle-task-{{ $task['id'] }}" class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $task['title'] }}</p>
                                <span class="hrm-num shrink-0 text-[11.5px] font-semibold text-[#be123c]">{{ $task['due_at'] }}</span>
                            </div>
                            <p class="mt-1 truncate text-[11px] text-ink-faint">{{ $task['employee_name'] }} <span class="px-0.5">·</span> {{ $task['owner_label'] }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('employee-lifecycle::dashboard.empty_tasks') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    {{-- ===================== management side panel ===================== --}}
    @if ($canManage && $panel !== '' && ! $isTemplateEditorOpen)
        <x-ui.side-panel
            title-id="lifecycle-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('employee-lifecycle::dashboard.actions.close_editor')"
            width="3xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.labels.management_center') }}</p>
                    <h2 id="lifecycle-panel-title" class="mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ $panelTitle }}</h2>
                </div>

                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('employee-lifecycle::dashboard.actions.close_editor') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'templates')
                    <form wire:submit="createTemplate" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.forms.template') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.template_name')" :error="$errors->first('templateForm.name')">
                                <x-ui.input wire:model.defer="templateForm.name" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.type')" :error="$errors->first('templateForm.type')">
                                <x-ui.select wire:model.defer="templateForm.type">
                                    @foreach (['onboarding', 'probation', 'movement', 'offboarding'] as $option)
                                        <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.types.'.$option) }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.default_duration_days')" :error="$errors->first('templateForm.default_duration_days')">
                                <x-ui.input type="number" min="1" max="365" wire:model.defer="templateForm.default_duration_days" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.description')" :error="$errors->first('templateForm.description')">
                                <x-ui.input wire:model.defer="templateForm.description" />
                            </x-ui.input-shell>
                            <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.task_lines')" :error="$errors->first('templateForm.tasks')">
                                <x-ui.textarea wire:model.defer="templateForm.tasks" rows="4" />
                            </x-ui.input-shell>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.create_template') }}</x-pill-button>
                        </div>
                    </form>

                    <div class="space-y-2">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.labels.plan_templates') }}</p>
                        @forelse ($planTemplates as $template)
                            <button
                                type="button"
                                wire:key="lifecycle-template-{{ $template['id'] }}"
                                wire:click="selectTemplate({{ $template['id'] }})"
                                class="flex w-full items-start justify-between gap-3 rounded-xl border border-hairline bg-white px-4 py-3 text-left transition hover:border-zinc-300 hover:bg-[#fafafa]"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $template['name'] }}</p>
                                    <p class="mt-1 truncate text-[11.5px] text-ink-faint">
                                        {{ $template['type_label'] }} <span class="px-0.5">·</span>
                                        {{ __('employee-lifecycle::dashboard.labels.template_meta', ['tasks' => $template['tasks_count'], 'days' => $template['default_duration_days']]) }}
                                        <span class="px-0.5">·</span>
                                        {{ __('employee-lifecycle::dashboard.labels.template_usage_count', ['count' => $template['events_count'] ?? 0]) }}
                                    </p>
                                </div>
                                <x-small-badge :mode="$template['is_active'] ? 'green' : 'secondary'" dot>
                                    {{ $template['is_active'] ? __('employee-lifecycle::dashboard.labels.active') : __('employee-lifecycle::dashboard.labels.inactive') }}
                                </x-small-badge>
                            </button>
                        @empty
                            <p class="text-[12.5px] text-ink-faint">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                        @endforelse
                    </div>
                @elseif ($panel === 'launch')
                    <div class="flex flex-wrap gap-2">
                        @foreach ($startTabs as $tab => $labelKey)
                            <x-pill-button
                                wire:key="lifecycle-start-tab-{{ $tab }}"
                                wire:click="setStartTab('{{ $tab }}')"
                                :variant="$startTab === $tab ? 'primary' : 'secondary'"
                            >{{ __('employee-lifecycle::dashboard.'.$labelKey) }}</x-pill-button>
                        @endforeach
                    </div>

                    @if ($startTab === 'plan')
                        <form wire:submit="launchTemplate" class="grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.template')" :error="$errors->first('launchForm.template_id')">
                                <x-ui.select wire:model.defer="launchForm.template_id">
                                    <option value="">---</option>
                                    @foreach ($planTemplates as $template)
                                        <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('launchForm.personnel_id')">
                                <x-ui.select wire:model.defer="launchForm.personnel_id">
                                    <option value="">---</option>
                                    @foreach ($personnelOptions as $personnel)
                                        <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.start_date')" :error="$errors->first('launchForm.start_date')">
                                <x-ui.input type="date" wire:model.defer="launchForm.start_date" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('launchForm.owner_user_id')">
                                <x-ui.select wire:model.defer="launchForm.owner_user_id">
                                    <option value="">---</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <div class="flex justify-end sm:col-span-2">
                                <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.launch_plan') }}</x-pill-button>
                            </div>
                        </form>
                    @elseif ($startTab === 'probation')
                        <form wire:submit="scheduleProbation" class="grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('probationForm.personnel_id')">
                                <x-ui.select wire:model.defer="probationForm.personnel_id">
                                    <option value="">---</option>
                                    @foreach ($personnelOptions as $personnel)
                                        <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.review_due_at')" :error="$errors->first('probationForm.review_due_at')">
                                <x-ui.input type="date" wire:model.defer="probationForm.review_due_at" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.manager')" :error="$errors->first('probationForm.manager_user_id')">
                                <x-ui.select wire:model.defer="probationForm.manager_user_id">
                                    <option value="">---</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.hr_reviewer')" :error="$errors->first('probationForm.hr_reviewer_user_id')">
                                <x-ui.select wire:model.defer="probationForm.hr_reviewer_user_id">
                                    <option value="">---</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <div class="flex justify-end sm:col-span-2">
                                <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.schedule_probation') }}</x-pill-button>
                            </div>
                        </form>
                    @elseif ($startTab === 'movement')
                        <form wire:submit="scheduleMovement" class="grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('movementForm.personnel_id')">
                                <x-ui.select wire:model.defer="movementForm.personnel_id">
                                    <option value="">---</option>
                                    @foreach ($personnelOptions as $personnel)
                                        <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.movement_type')" :error="$errors->first('movementForm.movement_type')">
                                <x-ui.select wire:model.defer="movementForm.movement_type">
                                    @foreach (['transfer', 'promotion', 'role_change'] as $option)
                                        <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.movement_types.'.$option) }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.target_structure')" :error="$errors->first('movementForm.target_structure_id')">
                                <x-ui.select wire:model.defer="movementForm.target_structure_id">
                                    <option value="">---</option>
                                    @foreach ($structureOptions as $structure)
                                        <option value="{{ $structure['id'] }}">{{ $structure['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.target_position')" :error="$errors->first('movementForm.target_position_id')">
                                <x-ui.select wire:model.defer="movementForm.target_position_id">
                                    <option value="">---</option>
                                    @foreach ($positionOptions as $position)
                                        <option value="{{ $position['id'] }}">{{ $position['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.effective_date')" :error="$errors->first('movementForm.effective_date')">
                                <x-ui.input type="date" wire:model.defer="movementForm.effective_date" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('movementForm.owner_user_id')">
                                <x-ui.select wire:model.defer="movementForm.owner_user_id">
                                    <option value="">---</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.reason')" :error="$errors->first('movementForm.reason')">
                                <x-ui.textarea wire:model.defer="movementForm.reason" rows="2" />
                            </x-ui.input-shell>
                            <div class="flex justify-end sm:col-span-2">
                                <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.schedule_movement') }}</x-pill-button>
                            </div>
                        </form>
                    @else
                        <form wire:submit="openOffboarding" class="grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('offboardingForm.personnel_id')">
                                <x-ui.select wire:model.defer="offboardingForm.personnel_id">
                                    <option value="">---</option>
                                    @foreach ($personnelOptions as $personnel)
                                        <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.last_working_date')" :error="$errors->first('offboardingForm.last_working_date')">
                                <x-ui.input type="date" wire:model.defer="offboardingForm.last_working_date" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('offboardingForm.owner_user_id')">
                                <x-ui.select wire:model.defer="offboardingForm.owner_user_id">
                                    <option value="">---</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.reason')" :error="$errors->first('offboardingForm.reason')">
                                <x-ui.input wire:model.defer="offboardingForm.reason" />
                            </x-ui.input-shell>
                            <div class="flex justify-end sm:col-span-2">
                                <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.open_offboarding') }}</x-pill-button>
                            </div>
                        </form>
                    @endif
                @else
                    <form wire:submit="completeProbationReview" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.forms.probation') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <x-ui.input-shell class="sm:col-span-2" :error="$errors->first('completionForm.probation_review_id')">
                                <x-ui.select wire:model.defer="completionForm.probation_review_id">
                                    <option value="">---</option>
                                    @foreach ($probationReviews as $review)
                                        <option value="{{ $review['id'] }}">{{ $review['employee_name'] }} · {{ $review['review_due_at'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :error="$errors->first('completionForm.probation_decision')">
                                <x-ui.select wire:model.defer="completionForm.probation_decision">
                                    @foreach (['confirm', 'extend', 'terminate'] as $decision)
                                        <option value="{{ $decision }}">{{ __('employee-lifecycle::dashboard.probation_decisions.'.$decision) }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.probation_score')" :error="$errors->first('completionForm.probation_score')">
                                <x-ui.input type="number" min="0" max="100" placeholder="0-100" wire:model.defer="completionForm.probation_score" />
                            </x-ui.input-shell>
                            <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.probation_note')" :error="$errors->first('completionForm.probation_note')">
                                <x-ui.textarea wire:model.defer="completionForm.probation_note" rows="2" />
                            </x-ui.input-shell>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.complete_probation') }}</x-pill-button>
                        </div>
                    </form>

                    <form wire:submit="completeMovement" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.forms.movement') }}</p>
                        <div class="mt-3">
                            <x-ui.input-shell :error="$errors->first('completionForm.movement_id')">
                                <x-ui.select wire:model.defer="completionForm.movement_id">
                                    <option value="">---</option>
                                    @foreach ($movements as $movement)
                                        <option value="{{ $movement['id'] }}">{{ $movement['employee_name'] }} · {{ $movement['movement_type_label'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.complete_movement') }}</x-pill-button>
                        </div>
                    </form>

                    <form wire:submit="completeOffboarding" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.forms.offboarding') }}</p>
                        <div class="mt-3 space-y-3">
                            <x-ui.input-shell :error="$errors->first('completionForm.offboarding_case_id')">
                                <x-ui.select wire:model.defer="completionForm.offboarding_case_id">
                                    <option value="">---</option>
                                    @foreach ($offboardingCases as $case)
                                        <option value="{{ $case['id'] }}">{{ $case['employee_name'] }} · {{ $case['last_working_date'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.exit_summary')" :error="$errors->first('completionForm.exit_summary')">
                                <x-ui.textarea wire:model.defer="completionForm.exit_summary" rows="3" />
                            </x-ui.input-shell>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <x-pill-button type="submit" variant="primary">{{ __('employee-lifecycle::dashboard.actions.complete_offboarding') }}</x-pill-button>
                        </div>
                    </form>
                @endif
            </div>
        </x-ui.side-panel>
    @endif

    {{-- ===================== template editor ===================== --}}
    @if ($selectedTemplateId && $isTemplateEditorOpen)
        <x-ui.side-panel
            title-id="lifecycle-template-editor-title"
            close-action="$wire.closeTemplateEditor()"
            :close-label="__('employee-lifecycle::dashboard.actions.close_editor')"
            width="3xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.labels.template_detail') }}</p>
                    <h2 id="lifecycle-template-editor-title" class="mt-1.5 truncate text-[17px] font-semibold tracking-[-0.025em] text-ink">
                        {{ $editingTemplateForm['name'] ?: __('employee-lifecycle::dashboard.forms.template') }}
                    </h2>
                    <p class="mt-1 text-[11.5px] text-ink-faint">
                        {{ __('employee-lifecycle::dashboard.labels.template_usage_count', ['count' => $editingTemplateForm['usage_count'] ?? 0]) }}
                    </p>
                </div>

                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('employee-lifecycle::dashboard.actions.close_editor') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.template_name')" :error="$errors->first('editingTemplateForm.name')">
                        <x-ui.input wire:model.defer="editingTemplateForm.name" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.type')" :error="$errors->first('editingTemplateForm.type')">
                        <x-ui.select wire:model.defer="editingTemplateForm.type">
                            @foreach (['onboarding', 'probation', 'movement', 'offboarding'] as $option)
                                <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.types.'.$option) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.default_duration_days')" :error="$errors->first('editingTemplateForm.default_duration_days')">
                        <x-ui.input type="number" min="1" max="365" wire:model.defer="editingTemplateForm.default_duration_days" />
                    </x-ui.input-shell>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.description')" :error="$errors->first('editingTemplateForm.description')">
                        <x-ui.textarea wire:model.defer="editingTemplateForm.description" rows="2" />
                    </x-ui.input-shell>
                </div>

                <div class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="hrm-eyebrow">{{ __('employee-lifecycle::dashboard.labels.template_tasks') }}</p>
                        <x-pill-button wire:click="addTemplateTaskRow">{{ __('employee-lifecycle::dashboard.actions.add_task') }}</x-pill-button>
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach ($editingTemplateForm['tasks'] as $index => $task)
                            <div wire:key="lifecycle-template-task-{{ $index }}" class="rounded-xl border border-hairline bg-white px-3 py-3">
                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_7rem_auto]">
                                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_title')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.title')">
                                        <x-ui.input wire:model.defer="editingTemplateForm.tasks.{{ $index }}.title" />
                                    </x-ui.input-shell>
                                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_owner_type')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.owner_type')">
                                        <x-ui.select wire:model.defer="editingTemplateForm.tasks.{{ $index }}.owner_type">
                                            @foreach (['hr', 'manager', 'it', 'employee'] as $option)
                                                <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.owner_types.'.$option) }}</option>
                                            @endforeach
                                        </x-ui.select>
                                    </x-ui.input-shell>
                                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_due_offset_days')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.due_offset_days')">
                                        <x-ui.input type="number" min="0" max="365" wire:model.defer="editingTemplateForm.tasks.{{ $index }}.due_offset_days" />
                                    </x-ui.input-shell>
                                    <div class="flex items-end">
                                        <x-pill-button variant="danger" wire:click="removeTemplateTaskRow({{ $index }})">
                                            {{ __('employee-lifecycle::dashboard.actions.remove_task') }}
                                        </x-pill-button>
                                    </div>
                                </div>
                                <label class="mt-2.5 inline-flex items-center gap-2 text-[12px] font-medium text-ink-muted">
                                    <input wire:model.defer="editingTemplateForm.tasks.{{ $index }}.is_required" type="checkbox" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                    {{ __('employee-lifecycle::dashboard.fields.task_required') }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3 text-[11.5px] leading-5 text-ink-faint">
                    {{ ($editingTemplateForm['usage_count'] ?? 0) > 0
                        ? __('employee-lifecycle::dashboard.labels.template_used_archive_note')
                        : __('employee-lifecycle::dashboard.labels.template_unused_delete_note') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-hairline-subtle bg-white px-5 py-3.5">
                <x-pill-button x-on:click="close()">{{ __('employee-lifecycle::dashboard.actions.close_editor') }}</x-pill-button>
                <x-pill-button wire:click="toggleTemplateActive">
                    {{ ($editingTemplateForm['is_active'] ?? false) ? __('employee-lifecycle::dashboard.actions.deactivate_template') : __('employee-lifecycle::dashboard.actions.activate_template') }}
                </x-pill-button>
                <x-pill-button variant="danger" wire:click="deleteOrArchiveTemplate">
                    {{ ($editingTemplateForm['usage_count'] ?? 0) > 0
                        ? __('employee-lifecycle::dashboard.actions.archive_template')
                        : __('employee-lifecycle::dashboard.actions.delete_template') }}
                </x-pill-button>
                <x-pill-button variant="primary" wire:click="updateTemplate">{{ __('employee-lifecycle::dashboard.actions.update_template') }}</x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
