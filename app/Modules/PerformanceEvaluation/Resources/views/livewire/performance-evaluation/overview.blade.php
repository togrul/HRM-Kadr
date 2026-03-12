<div class="space-y-4">
    <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        <x-surface-card :title="__('performance_evaluation::dashboard.cards.foundation_scope')" icon="icons.profile-outline-icon">
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ([
                    ['title' => 'cycles_scope_title', 'description' => 'cycles_scope_description'],
                    ['title' => 'templates_scope_title', 'description' => 'templates_scope_description'],
                    ['title' => 'evaluation_scope_title', 'description' => 'evaluation_scope_description'],
                    ['title' => 'integration_scope_title', 'description' => 'integration_scope_description'],
                ] as $card)
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-sm font-semibold text-zinc-800">{{ __('performance_evaluation::dashboard.cards.'.$card['title']) }}</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.cards.'.$card['description']) }}</p>
                    </div>
                @endforeach
            </div>
        </x-surface-card>

        <x-surface-card :title="__('performance_evaluation::dashboard.cards.weak_links')" icon="icons.pending-icon">
            <div class="space-y-3">
                @forelse ($this->recentWeakLinks as $link)
                    <x-ui.list-card tone="violet">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-violet-900">{{ $link->competency?->name ?? __('performance_evaluation::dashboard.labels.no_competency') }}</span>
                            <x-small-badge mode="violet">{{ __('performance_evaluation::dashboard.labels.linked_need') }}</x-small-badge>
                        </div>
                        <p class="mt-1 text-xs text-violet-700">{{ $link->form?->personnel?->fullname ?? '-' }}</p>
                        <p class="mt-1 text-xs text-violet-700">{{ $link->trainingNeed?->presentedReason() }}</p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.link-icon" :message="__('performance_evaluation::dashboard.empty.weak_links')" />
                @endforelse
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_cycles')" icon="icons.clock-icon">
            <div class="space-y-3">
                @forelse ($this->recentCycles as $cycle)
                    <x-ui.list-card>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-900">{{ $cycle->name }}</span>
                            <x-small-badge mode="green">{{ __('performance_evaluation::dashboard.statuses.'.$cycle->status) }}</x-small-badge>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.cycle_types.'.$cycle->cycle_type) }} • {{ $cycle->period_start?->format('d.m.Y') }} - {{ $cycle->period_end?->format('d.m.Y') }}</p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.clock-icon" :message="__('performance_evaluation::dashboard.empty.recent_cycles')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_templates')" icon="icons.folder-plus-icon">
            <div class="space-y-3">
                @forelse ($this->recentTemplates as $template)
                    <x-ui.list-card>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-900">{{ $template->name }}</span>
                            <x-small-badge :mode="$template->is_active ? 'green' : 'red'">
                                {{ $template->is_active ? __('performance_evaluation::dashboard.statuses.active') : __('performance_evaluation::dashboard.labels.inactive') }}
                            </x-small-badge>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">{{ $template->code ?: __('performance_evaluation::dashboard.labels.no_code') }} • {{ __('performance_evaluation::dashboard.labels.sections_count', ['count' => $template->sections_count]) }}</p>
                        @if (filled($template->description))
                            <p class="mt-2 text-xs leading-5 text-zinc-500">{{ $template->description }}</p>
                        @endif
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.folder-plus-icon" :message="__('performance_evaluation::dashboard.empty.recent_templates')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_forms')" icon="icons.profile-icon">
            <div class="space-y-3">
                @forelse ($this->recentForms as $form)
                    <x-ui.list-card>
                        <div class="space-y-4">
                            <div class="min-w-0 space-y-1">
                                <p class="text-sm font-semibold text-zinc-900">{{ $form->personnel_fullname ?? '-' }}</p>
                                <p class="text-xs text-zinc-500">{{ $form->cycle_name }} • {{ $form->template_name }}</p>
                                <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.evaluators.manager') }}: {{ $form->manager_name ?? __('performance_evaluation::dashboard.labels.no_manager') }}</p>
                                <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.evaluators.hr') }}: {{ $form->hr_reviewer_name ?? __('performance_evaluation::dashboard.labels.no_hr_reviewer') }}</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.self') }}: {{ $form->self_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.manager') }}: {{ $form->manager_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.hr') }}: {{ $form->hr_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                                    @if ($form->final_category)
                                        <x-small-badge :mode="$form->final_category === 'weak' ? 'red' : ($form->final_category === 'high' ? 'green' : 'amber')">
                                            {{ __('performance_evaluation::dashboard.labels.final_category') }}: {{ __('performance_evaluation::dashboard.categories.'.$form->final_category) }}
                                        </x-small-badge>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 border-t border-zinc-200/80 pt-3">
                                <x-small-badge mode="amber">{{ $form->final_score ?? '—' }}</x-small-badge>
                            </div>
                        </div>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.profile-icon" :message="__('performance_evaluation::dashboard.empty.recent_forms')" />
                @endforelse
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <x-surface-card :title="__('performance_evaluation::dashboard.cards.evaluator_workspace')" icon="icons.performance-icon">
            <div class="space-y-4">
                <p class="text-sm leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.evaluator_workspace_hint') }}</p>
                <a href="{{ route('performance-evaluation.evaluator') }}" class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                    {{ __('performance_evaluation::dashboard.actions.open_evaluator_workspace') }}
                </a>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('performance_evaluation::dashboard.cards.reports')" icon="icons.pending-icon">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <x-ui.action-pill wire:click="exportPerformanceFormsReport" icon="icons.document-icon">{{ __('performance_evaluation::dashboard.actions.export_forms_report') }}</x-ui.action-pill>
                <x-ui.action-pill wire:click="exportPerformanceSummaryReport" icon="icons.document-icon">{{ __('performance_evaluation::dashboard.actions.export_summary_report') }}</x-ui.action-pill>
                <x-ui.action-pill wire:click="exportPerformanceWeakLinksReport" icon="icons.document-icon">{{ __('performance_evaluation::dashboard.actions.export_weak_links_report') }}</x-ui.action-pill>
                <x-ui.action-pill wire:click="exportPerformanceWeakPivotReport" icon="icons.document-icon">{{ __('performance_evaluation::dashboard.actions.export_weak_pivot_report') }}</x-ui.action-pill>
                <x-ui.action-pill wire:click="exportPerformanceAuditReport" icon="icons.document-icon">{{ __('performance_evaluation::dashboard.actions.export_audit_report') }}</x-ui.action-pill>
                <a href="{{ route('performance-evaluation.print-summary') }}" target="_blank" class="inline-flex min-w-max items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-100">
                    {{ __('performance_evaluation::dashboard.actions.open_print_summary') }}
                </a>
            </div>
            <p class="mt-3 text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.export_report_hint') }}</p>
        </x-surface-card>
    </div>
</div>
