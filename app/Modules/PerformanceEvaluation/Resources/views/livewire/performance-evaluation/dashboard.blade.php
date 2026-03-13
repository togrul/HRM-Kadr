<div class="flex flex-col space-y-4 px-6 py-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.title')" icon="icons.performance-icon">
        <div class="space-y-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::dashboard.workspace.title') }}</p>
                    <p class="max-w-3xl text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.workspace.description') }}</p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('performance_evaluation::dashboard.stats.cycles') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->stats['cycles'] }}</p>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::dashboard.stats.templates') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->stats['templates'] }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::dashboard.stats.forms') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->stats['forms'] }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('performance_evaluation::dashboard.stats.links') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->stats['links'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::dashboard.sections.title') }}</p>
                    <span class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.sections.description') }}</span>
                </div>

                <x-filter.nav class="min-w-0">
                    <x-filter.item wire:click.prevent="switchTab('overview')" :active="$activeTab === 'overview'">
                        {{ __('performance_evaluation::dashboard.tabs.overview') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('cycles')" :active="$activeTab === 'cycles'">
                        {{ __('performance_evaluation::dashboard.tabs.cycles') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('templates')" :active="$activeTab === 'templates'">
                        {{ __('performance_evaluation::dashboard.tabs.templates') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('evaluations')" :active="$activeTab === 'evaluations'">
                        {{ __('performance_evaluation::dashboard.tabs.evaluations') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('tests')" :active="$activeTab === 'tests'">
                        {{ __('performance_evaluation::dashboard.tabs.tests') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                        {{ __('performance_evaluation::dashboard.tabs.reports') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('lists')" :active="$activeTab === 'lists'">
                        {{ __('performance_evaluation::dashboard.tabs.lists') }}
                    </x-filter.item>
                </x-filter.nav>
            </div>
        </div>
    </x-surface-card>

    @if ($activeTab === 'overview')
        <livewire:performance-evaluation.overview lazy />
    @endif

    @if ($activeTab === 'cycles')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.cycle_setup')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    @if ($editingCycleId)
                        <div class="md:col-span-2">
                            <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <x-label for="cycle-name">{{ __('performance_evaluation::dashboard.fields.cycle_name') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-name" wire:model.defer="cycleForm.name" />
                        @error('cycleForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle_type')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-type" wire:model.live="cycleForm.cycle_type"
                            :model="collect(['annual','academic','quarterly'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.cycle_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                        @error('cycleForm.cycle_type') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-status" wire:model.live="cycleForm.status"
                            :model="collect(['draft','active','closed'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                        @error('cycleForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="cycle-period-start">{{ __('performance_evaluation::dashboard.fields.period_start') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-period-start" type="date" wire:model.defer="cycleForm.period_start" />
                        @error('cycleForm.period_start') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="cycle-period-end">{{ __('performance_evaluation::dashboard.fields.period_end') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-period-end" type="date" wire:model.defer="cycleForm.period_end" />
                        @error('cycleForm.period_end') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="cycle-description">{{ __('performance_evaluation::dashboard.fields.description') }}</x-label>
                        <textarea id="cycle-description" wire:model.defer="cycleForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('cycleForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="md:col-span-2 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="cycleForm.auto_generate_forms" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.auto_generate_forms') }}
                    </label>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-button mode="black" wire:click="storeCycle">{{ __('performance_evaluation::dashboard.actions.save_cycle') }}</x-button>
                            @if ($editingCycleId)
                                <x-button mode="secondary" wire:click="cancelCycleEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_cycles')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->recentCycles as $cycle)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $cycle->name }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $cycle->period_start?->format('d.m.Y') }} - {{ $cycle->period_end?->format('d.m.Y') }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <x-small-badge mode="green">{{ __('performance_evaluation::dashboard.statuses.'.$cycle->status) }}</x-small-badge>
                                    <span class="text-xs text-zinc-500">
                                        {{ $cycle->auto_generate_forms ? __('performance_evaluation::dashboard.labels.auto_generation_on') : __('performance_evaluation::dashboard.labels.auto_generation_off') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <x-ui.action-pill wire:click="editCycle({{ $cycle->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteCycle({{ $cycle->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.empty.recent_cycles') }}</p>
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    @endif

    @if ($activeTab === 'templates')
        <div class="grid gap-4 xl:grid-cols-3">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.template_setup')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <p class="rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.template_setup_hint') }}</p>
                    @if ($editingTemplateId)
                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                    @endif
                    <div>
                        <x-label for="template-name">{{ __('performance_evaluation::dashboard.fields.template_name') }}</x-label>
                        <x-livewire-input mode="gray" id="template-name" wire:model.defer="templateForm.name" />
                        @error('templateForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="template-code">{{ __('performance_evaluation::dashboard.fields.template_code') }}</x-label>
                        <x-livewire-input mode="gray" id="template-code" wire:model.defer="templateForm.code" />
                        @error('templateForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="template-description">{{ __('performance_evaluation::dashboard.fields.description') }}</x-label>
                        <textarea id="template-description" wire:model.defer="templateForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('templateForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="templateForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.is_active') }}
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button mode="black" wire:click="storeTemplate">{{ __('performance_evaluation::dashboard.actions.save_template') }}</x-button>
                        @if ($editingTemplateId)
                            <x-button mode="secondary" wire:click="cancelTemplateEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                        @endif
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.section_setup')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <p class="rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.section_setup_hint') }}</p>
                    @if ($editingSectionId)
                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                    @endif
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.template')" placeholder="---" mode="gray" class="w-full" instance="perf-section-template"
                        wire:model.live="sectionForm.performance_form_template_id" :model="$this->templateOptions()" search-model="searchTemplate"></x-ui.select-dropdown>
                    @error('sectionForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <div>
                        <x-label for="section-name">{{ __('performance_evaluation::dashboard.fields.section_name') }}</x-label>
                        <x-livewire-input mode="gray" id="section-name" wire:model.defer="sectionForm.name" />
                        @error('sectionForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="section-weight">{{ __('performance_evaluation::dashboard.fields.weight_percent') }}</x-label>
                        <x-livewire-input mode="gray" id="section-weight" type="number" step="0.01" wire:model.defer="sectionForm.weight_percent" />
                        @error('sectionForm.weight_percent') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="section-sort">{{ __('performance_evaluation::dashboard.fields.sort_order') }}</x-label>
                        <x-livewire-input mode="gray" id="section-sort" type="number" wire:model.defer="sectionForm.sort_order" />
                        @error('sectionForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button mode="black" wire:click="storeSection">{{ __('performance_evaluation::dashboard.actions.save_section') }}</x-button>
                        @if ($editingSectionId)
                            <x-button mode="secondary" wire:click="cancelSectionEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                        @endif
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.item_setup')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <p class="rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.item_setup_hint') }}</p>
                    @if ($editingItemId)
                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                    @endif
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.section')" placeholder="---" mode="gray" class="w-full" instance="perf-item-section"
                        wire:model.live="itemForm.performance_form_template_section_id" :model="$this->sectionOptions()" search-model="searchSection"></x-ui.select-dropdown>
                    @error('itemForm.performance_form_template_section_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.competency')" placeholder="---" mode="gray" class="w-full" instance="perf-item-competency"
                        wire:model.live="itemForm.training_competency_id" :model="$this->competencyOptions()" search-model="searchCompetency"></x-ui.select-dropdown>
                    @error('itemForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <div>
                        <x-label for="item-name">{{ __('performance_evaluation::dashboard.fields.item_name') }}</x-label>
                        <x-livewire-input mode="gray" id="item-name" wire:model.defer="itemForm.name" />
                        @error('itemForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="item-weight">{{ __('performance_evaluation::dashboard.fields.weight_percent') }}</x-label>
                        <x-livewire-input mode="gray" id="item-weight" type="number" step="0.01" wire:model.defer="itemForm.weight_percent" />
                        @error('itemForm.weight_percent') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="item-threshold">{{ __('performance_evaluation::dashboard.fields.low_score_threshold') }}</x-label>
                        <x-livewire-input mode="gray" id="item-threshold" type="number" step="0.01" wire:model.defer="itemForm.low_score_threshold" />
                        @error('itemForm.low_score_threshold') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="itemForm.requires_comment" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.requires_comment') }}
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button mode="black" wire:click="storeItem">{{ __('performance_evaluation::dashboard.actions.save_item') }}</x-button>
                        @if ($editingItemId)
                            <x-button mode="secondary" wire:click="cancelItemEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                        @endif
                    </div>
                </div>
            </x-surface-card>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_templates')" icon="icons.folder-plus-icon">
                <div class="space-y-3">
                    @forelse ($this->recentTemplates as $template)
                        <x-ui.list-card>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $template->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $template->code ?: __('performance_evaluation::dashboard.labels.no_code') }} • {{ __('performance_evaluation::dashboard.labels.sections_count', ['count' => $template->sections_count]) }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <x-small-badge :mode="$template->is_active ? 'green' : 'red'">
                                        {{ $template->is_active ? __('performance_evaluation::dashboard.statuses.active') : __('performance_evaluation::dashboard.labels.inactive') }}
                                    </x-small-badge>
                                    <div class="flex items-center gap-2">
                                        <x-ui.action-pill wire:click="editTemplate({{ $template->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteTemplate({{ $template->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                    </div>
                                </div>
                            </div>
                            @if (filled($template->description))
                                <p class="mt-2 text-xs leading-5 text-zinc-500">{{ $template->description }}</p>
                            @endif
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.folder-plus-icon" :message="__('performance_evaluation::dashboard.empty.recent_templates')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_template_sections')" icon="icons.profile-outline-icon">
                <div class="space-y-3">
                    @forelse ($this->recentTemplateSections as $section)
                        <x-ui.list-card>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $section->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $section->template_name ?: $section->template_code ?: '—' }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-2">
                                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.weight_percent_value', ['value' => number_format((float) $section->weight_percent, 2)]) }}</x-small-badge>
                                        <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.labels.sort_order_value', ['value' => $section->sort_order]) }}</x-small-badge>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-ui.action-pill wire:click="editSection({{ $section->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteSection({{ $section->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                    </div>
                                </div>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.profile-outline-icon" :message="__('performance_evaluation::dashboard.empty.recent_template_sections')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_template_items')" icon="icons.profile-icon">
                <div class="space-y-3">
                    @forelse ($this->recentTemplateItems as $item)
                        <x-ui.list-card>
                            <div class="space-y-4">
                                <div class="space-y-3">
                                    <div class="min-w-0 space-y-1">
                                        <p class="text-sm font-semibold leading-6 text-zinc-900">{{ $item->name }}</p>
                                        <p class="text-xs leading-5 text-zinc-500">{{ $item->template_name ?? '—' }} • {{ $item->section_name ?? '—' }}</p>
                                        <p class="text-xs leading-5 text-zinc-500">{{ $item->competency_name ?? __('performance_evaluation::dashboard.labels.no_competency') }}</p>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.weight_percent_value', ['value' => number_format((float) $item->weight_percent, 2)]) }}</x-small-badge>
                                        <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.labels.threshold_value', ['value' => number_format((float) $item->low_score_threshold, 2)]) }}</x-small-badge>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 border-t border-zinc-200/80 pt-3">
                                    <x-ui.action-pill class="self-start" wire:click="editItem({{ $item->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                    <x-ui.action-pill class="self-start" mode="delete" wire:click="confirmDeleteItem({{ $item->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                </div>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.profile-icon" :message="__('performance_evaluation::dashboard.empty.recent_template_items')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    @endif

    @if ($activeTab === 'evaluations')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-4">
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.evaluation_assignment')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">
                            {{ __('performance_evaluation::dashboard.labels.evaluation_assignment_hint') }}
                        </div>
                        @if ($editingEvaluationFormId)
                            <div class="md:col-span-2">
                                <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-cycle"
                                direction="up"
                                wire:model.live="evaluationForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                            @error('evaluationForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.template')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-template"
                                direction="up"
                                wire:model.live="evaluationForm.performance_form_template_id" :model="$this->templateOptions()" search-model="searchTemplate"></x-ui.select-dropdown>
                            @error('evaluationForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-personnel"
                                direction="up"
                                wire:model.live="evaluationForm.personnel_id" :model="$this->personnelOptions()" search-model="searchPersonnel"></x-ui.select-dropdown>
                            @error('evaluationForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.manager')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-manager"
                                direction="up"
                                wire:model.live="evaluationForm.manager_id" :model="$this->evaluatorOptions('searchManager', 'manager_id')" search-model="searchManager"></x-ui.select-dropdown>
                            @error('evaluationForm.manager_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.hr_reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-hr"
                                direction="up"
                                wire:model.live="evaluationForm.hr_reviewer_id" :model="$this->evaluatorOptions('searchHrReviewer', 'hr_reviewer_id')" search-model="searchHrReviewer"></x-ui.select-dropdown>
                            @error('evaluationForm.hr_reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button mode="black" wire:click="storeEvaluationForm">{{ __('performance_evaluation::dashboard.actions.save_evaluation') }}</x-button>
                                @if ($editingEvaluationFormId)
                                    <x-button mode="secondary" wire:click="cancelEvaluationEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.score_capture')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">
                            {{ __('performance_evaluation::dashboard.labels.score_capture_hint') }}
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluation_form')" placeholder="---" mode="gray" class="w-full" instance="perf-score-form"
                                direction="up"
                                wire:model.live="scoreForm.performance_form_id" :model="$this->performanceFormOptions()" search-model="searchPerformanceForm"></x-ui.select-dropdown>
                            @error('scoreForm.performance_form_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.item')" placeholder="---" mode="gray" class="w-full" instance="perf-score-item"
                                direction="up"
                                wire:model.live="scoreForm.performance_form_template_item_id" :model="$this->templateItemOptions()" search-model="searchTemplateItem"></x-ui.select-dropdown>
                            @error('scoreForm.performance_form_template_item_id') <x-validation>{{ $message }}</x-validation> @enderror
                            @if ($this->selectedScoreItem && blank($this->selectedScoreItem->training_competency_id))
                                <x-validation>{{ __('performance_evaluation::dashboard.validation.item_without_competency') }}</x-validation>
                            @endif
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluator_type')" placeholder="---" mode="gray" class="w-full" instance="perf-score-evaluator-type" wire:model.live="scoreForm.evaluator_type"
                                direction="up"
                                :model="collect(['self','manager','hr'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.evaluators.'.$item)])->values()->all()"></x-ui.select-dropdown>
                            @error('scoreForm.evaluator_type') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="score-value">{{ __('performance_evaluation::dashboard.fields.score') }}</x-label>
                            <x-livewire-input mode="gray" id="score-value" type="number" step="0.01" wire:model.defer="scoreForm.score" />
                            @error('scoreForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-label for="score-comment">{{ __('performance_evaluation::dashboard.fields.comment') }}</x-label>
                            <textarea id="score-comment" wire:model.defer="scoreForm.comment" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                            @error('scoreForm.comment') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-button mode="black" wire:click="storeScore">{{ __('performance_evaluation::dashboard.actions.save_score') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>
            </div>

            <livewire:performance-evaluation.evaluations-summary :key="'performance-evaluation-evaluations-summary-'.$evaluationsSummaryVersion" lazy />
        </div>
    @endif

    @if ($activeTab === 'tests')
        <div class="grid gap-4 xl:grid-cols-3">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_bank_setup')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <div>
                        <x-label for="test-bank-name">{{ __('performance_evaluation::dashboard.fields.test_bank_name') }}</x-label>
                        <x-livewire-input mode="gray" id="test-bank-name" wire:model.defer="bankForm.name" />
                        @error('bankForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="test-bank-code">{{ __('performance_evaluation::dashboard.fields.test_bank_code') }}</x-label>
                        <x-livewire-input mode="gray" id="test-bank-code" wire:model.defer="bankForm.code" />
                        @error('bankForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <x-label for="test-bank-pass-score">{{ __('performance_evaluation::dashboard.fields.pass_score') }}</x-label>
                            <x-livewire-input mode="gray" id="test-bank-pass-score" type="number" step="0.01" wire:model.defer="bankForm.pass_score" />
                            @error('bankForm.pass_score') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="test-bank-duration">{{ __('performance_evaluation::dashboard.fields.duration_minutes') }}</x-label>
                            <x-livewire-input mode="gray" id="test-bank-duration" type="number" wire:model.defer="bankForm.duration_minutes" />
                            @error('bankForm.duration_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="test-bank-max-attempts">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}</x-label>
                            <x-livewire-input mode="gray" id="test-bank-max-attempts" type="number" wire:model.defer="bankForm.max_attempts" />
                            @error('bankForm.max_attempts') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>
                    <div>
                        <x-label for="test-bank-description">{{ __('performance_evaluation::dashboard.fields.description') }}</x-label>
                        <textarea id="test-bank-description" wire:model.defer="bankForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('bankForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="bankForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.is_active') }}
                    </label>
                    <x-button mode="black" wire:click="storeTestBank">{{ __('performance_evaluation::dashboard.actions.save_test_bank') }}</x-button>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_question_setup')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-question-bank"
                        wire:model.live="questionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                    @error('questionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.competency')" placeholder="---" mode="gray" class="w-full" instance="perf-question-competency"
                        wire:model.live="questionForm.training_competency_id" :model="$this->competencyOptions()" search-model="searchTestCompetency"></x-ui.select-dropdown>
                    @error('questionForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.question_type')" placeholder="---" mode="gray" class="w-full" instance="perf-question-type"
                        wire:model.live="questionForm.question_type"
                        :model="collect(['multiple_choice','open_answer','case_study','behavioral'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.question_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                    @error('questionForm.question_type') <x-validation>{{ $message }}</x-validation> @enderror
                    <div>
                        <x-label for="test-question-prompt">{{ __('performance_evaluation::dashboard.fields.prompt') }}</x-label>
                        <textarea id="test-question-prompt" wire:model.defer="questionForm.prompt" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('questionForm.prompt') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-label for="test-question-max-score">{{ __('performance_evaluation::dashboard.fields.max_score') }}</x-label>
                            <x-livewire-input mode="gray" id="test-question-max-score" type="number" step="0.01" wire:model.defer="questionForm.max_score" />
                            @error('questionForm.max_score') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="test-question-sort-order">{{ __('performance_evaluation::dashboard.fields.sort_order') }}</x-label>
                            <x-livewire-input mode="gray" id="test-question-sort-order" type="number" wire:model.defer="questionForm.sort_order" />
                            @error('questionForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>
                    <div>
                        <x-label for="test-question-options">{{ __('performance_evaluation::dashboard.fields.options_text') }}</x-label>
                        <textarea id="test-question-options" wire:model.defer="questionForm.options_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500" placeholder="{{ __('performance_evaluation::dashboard.placeholders.options_text') }}"></textarea>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.hints.options_text') }}</p>
                        @error('questionForm.options_text') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="questionForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.is_active') }}
                    </label>
                    <x-button mode="black" wire:click="storeTestQuestion">{{ __('performance_evaluation::dashboard.actions.save_test_question') }}</x-button>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_session_setup')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3">
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-cycle"
                        wire:model.live="sessionForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                    @error('sessionForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-bank"
                        wire:model.live="sessionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                    @error('sessionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-personnel"
                        wire:model.live="sessionForm.personnel_id" :model="$this->personnelOptions('searchTestPersonnel', 'personnel_id')" search-model="searchTestPersonnel"></x-ui.select-dropdown>
                    @error('sessionForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-reviewer"
                        wire:model.live="sessionForm.reviewer_id" :model="$this->evaluatorOptions('searchTestReviewer', 'reviewer_id')" search-model="searchTestReviewer"></x-ui.select-dropdown>
                    @error('sessionForm.reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-label for="test-session-scheduled-at">{{ __('performance_evaluation::dashboard.fields.scheduled_at') }}</x-label>
                            <x-livewire-input mode="gray" id="test-session-scheduled-at" type="date" wire:model.defer="sessionForm.scheduled_at" />
                            @error('sessionForm.scheduled_at') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="test-session-available-until">{{ __('performance_evaluation::dashboard.fields.available_until') }}</x-label>
                            <x-livewire-input mode="gray" id="test-session-available-until" type="date" wire:model.defer="sessionForm.available_until" />
                            @error('sessionForm.available_until') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <x-label for="test-session-pass-score">{{ __('performance_evaluation::dashboard.fields.pass_score') }}</x-label>
                            <x-livewire-input mode="gray" id="test-session-pass-score" type="number" step="0.01" wire:model.defer="sessionForm.pass_score" />
                        </div>
                        <div>
                            <x-label for="test-session-duration">{{ __('performance_evaluation::dashboard.fields.duration_minutes') }}</x-label>
                            <x-livewire-input mode="gray" id="test-session-duration" type="number" wire:model.defer="sessionForm.duration_minutes" />
                        </div>
                        <div>
                            <x-label for="test-session-max-attempts">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}</x-label>
                            <x-livewire-input mode="gray" id="test-session-max-attempts" type="number" wire:model.defer="sessionForm.max_attempts" />
                        </div>
                    </div>
                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-status" wire:model.live="sessionForm.status"
                        :model="collect(['assigned','in_progress','completed','closed'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.test_statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                    @error('sessionForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    <x-button mode="black" wire:click="storeTestSession">{{ __('performance_evaluation::dashboard.actions.save_test_session') }}</x-button>
                </div>
            </x-surface-card>
        </div>

        <div class="rounded-3xl border border-zinc-200 bg-zinc-50 px-4 py-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_taking_workspace') }}</p>
                    <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.test_taking_workspace_hint') }}</p>
                </div>
                <a href="{{ route('performance-evaluation.test-workspace') }}" target="_blank" class="inline-flex h-11 items-center justify-center rounded-2xl bg-zinc-900 px-4 text-sm font-medium text-white">
                    {{ __('performance_evaluation::dashboard.actions.open_test_workspace') }}
                </a>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-4">
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.attempt_capture')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_session')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-session"
                                direction="up"
                                wire:model.live="attemptAnswerForm.performance_test_session_id" :model="$this->testSessionOptions()" search-model="searchTestSession"></x-ui.select-dropdown>
                            @error('attemptAnswerForm.performance_test_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.question')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-question"
                                direction="up"
                                wire:model.live="attemptAnswerForm.performance_test_question_id" :model="$this->testQuestionOptions()" search-model="searchTestQuestion"></x-ui.select-dropdown>
                            @error('attemptAnswerForm.performance_test_question_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="attempt-no">{{ __('performance_evaluation::dashboard.fields.attempt_no') }}</x-label>
                            <x-livewire-input mode="gray" id="attempt-no" type="number" wire:model.defer="attemptAnswerForm.attempt_no" />
                            @error('attemptAnswerForm.attempt_no') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        @if (data_get($attemptAnswerForm, 'performance_test_question_id') && optional(\App\Models\PerformanceTestQuestion::find(data_get($attemptAnswerForm, 'performance_test_question_id')))->isAutoScored())
                            <div class="md:col-span-1">
                                <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.option')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-option"
                                    direction="up"
                                    wire:model.live="attemptAnswerForm.selected_option_id" :model="$this->testQuestionOptionChoices()"></x-ui.select-dropdown>
                                @error('attemptAnswerForm.selected_option_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        @else
                            <div class="md:col-span-1"></div>
                        @endif
                        <div class="md:col-span-2">
                            <x-label for="attempt-answer-text">{{ __('performance_evaluation::dashboard.fields.answer_text') }}</x-label>
                            <textarea id="attempt-answer-text" wire:model.defer="attemptAnswerForm.answer_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                            @error('attemptAnswerForm.answer_text') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2 flex flex-wrap gap-3">
                            <x-button mode="black" wire:click="storeAttemptAnswer">{{ __('performance_evaluation::dashboard.actions.save_attempt_answer') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.attempt_finalize')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.attempt')" placeholder="---" mode="gray" class="w-full" instance="perf-finalize-attempt"
                                direction="up"
                                wire:model.live="attemptSubmitForm.performance_test_attempt_id" :model="$this->attemptOptions()" search-model="searchTestAttempt"></x-ui.select-dropdown>
                            @error('attemptSubmitForm.performance_test_attempt_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-button mode="black" wire:click="finalizeAttempt">{{ __('performance_evaluation::dashboard.actions.submit_attempt') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.open_answer_review')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.answer')" placeholder="---" mode="gray" class="w-full" instance="perf-review-answer"
                                direction="up"
                                wire:model.live="reviewForm.performance_test_attempt_answer_id" :model="$this->reviewAnswerOptions()" search-model="searchReviewAnswer"></x-ui.select-dropdown>
                            @error('reviewForm.performance_test_attempt_answer_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="review-score">{{ __('performance_evaluation::dashboard.fields.review_score') }}</x-label>
                            <x-livewire-input mode="gray" id="review-score" type="number" step="0.01" wire:model.defer="reviewForm.score" />
                            @error('reviewForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div></div>
                        <div class="md:col-span-2">
                            <x-label for="review-feedback">{{ __('performance_evaluation::dashboard.fields.feedback') }}</x-label>
                            <textarea id="review-feedback" wire:model.defer="reviewForm.feedback" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                            @error('reviewForm.feedback') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-button mode="black" wire:click="reviewAttemptAnswer">{{ __('performance_evaluation::dashboard.actions.review_answer') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>
            </div>

            <livewire:performance-evaluation.tests-summary :key="'performance-evaluation-tests-summary-'.$testsSummaryVersion" lazy />
        </div>
    @endif

    @if ($activeTab === 'reports')
        <livewire:performance-evaluation.reports lazy />
    @endif

    @if ($activeTab === 'lists')
        <livewire:performance-evaluation.lists lazy />
    @endif

    <x-ui.delete-confirmation-modal />
</div>
