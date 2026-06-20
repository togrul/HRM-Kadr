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

