@php
    // Refined, dependency-free outline icons (Apple-style thin stroke) reused across the manage lists.
    $editIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>';
    $trashIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';
    // Premium ghost icon-button styles (soft, rounded, tinted hover, active press).
    $editBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-[11px] text-zinc-400 transition-all duration-200 ease-out hover:bg-zinc-100 hover:text-zinc-900 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300/70';
    $deleteBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-[11px] text-zinc-400 transition-all duration-200 ease-out hover:bg-rose-50 hover:text-rose-600 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-200';
@endphp

@if ($activeTab === 'catalogs')
    <div class="grid gap-4 xl:grid-cols-2">
        {{-- ═════════════ COMPETENCY GROUPS ═════════════ --}}
        @php ($groups = $this->catalogGroups)
        <x-surface-card :title="__('training_needs::dashboard.cards.competency_groups')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            @if ($editingGroupId)
                <div class="mb-3 flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    <span class="truncate">{{ __('training_needs::dashboard.actions.edit') }}: <span class="font-semibold">{{ $groupForm['name'] }}</span></span>
                    <button type="button" wire:click="cancelGroupEdit" class="flex-none text-xs font-medium underline hover:no-underline">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                </div>
            @endif
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-label for="group-name">{{ __('training_needs::dashboard.fields.group_name') }}</x-label>
                    <x-livewire-input mode="gray" id="group-name" wire:model.defer="groupForm.name" />
                    @error('groupForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-label for="group-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                    <textarea id="group-description" wire:model.defer="groupForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                    @error('groupForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="group-sort-order">{{ __('training_needs::dashboard.fields.sort_order') }}</x-label>
                    <x-livewire-input mode="gray" id="group-sort-order" type="number" wire:model.defer="groupForm.sort_order" />
                    @error('groupForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="groupForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('training_needs::dashboard.fields.is_active') }}
                </label>
                <div class="md:col-span-2 flex items-center gap-2">
                    <x-button mode="black" wire:click="storeGroup">{{ __('training_needs::dashboard.actions.save_group') }}</x-button>
                    @if ($editingGroupId)
                        <button type="button" wire:click="cancelGroupEdit" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-t border-zinc-100 pt-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase text-zinc-400">
                        {{ __('training_needs::dashboard.cards.competency_groups') }}
                        <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-zinc-600">{{ $groups->total() }}</span>
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="groupListSearch" placeholder="{{ __('training_needs::dashboard.fields.search') }}" class="h-8 w-36 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs focus:border-blue-400 focus:ring-blue-400">
                </div>
                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-100">
                    @forelse ($groups as $group)
                        <div @class(['flex items-center justify-between gap-2 px-3 py-2', 'bg-amber-50/60' => $editingGroupId === $group->id])>
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-zinc-800">{{ $group->name }}</span>
                                <span class="block text-xs text-zinc-400">{{ $group->competencies_count }} · {{ $group->is_active ? __('training_needs::dashboard.fields.is_active') : '—' }}</span>
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button type="button" wire:click="editGroup({{ $group->id }})" title="{{ __('training_needs::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('training_needs::dashboard.confirmations.delete_group')), confirmText: @js(__('training_needs::dashboard.actions.delete')), run: () => $wire.deleteGroup({{ $group->id }}) })"
                                    title="{{ __('training_needs::dashboard.actions.delete') }}"
                                    class="{{ $deleteBtn }}">{!! $trashIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('training_needs::dashboard.empty.full_lists') }}</div>
                    @endforelse
                </div>
                @if ($groups->hasPages())
                    <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ $groups->firstItem() }}–{{ $groups->lastItem() }} / {{ $groups->total() }}</span>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="previousPage('groupsPage')" @disabled($groups->onFirstPage()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">‹</button>
                            <button type="button" wire:click="nextPage('groupsPage')" @disabled(! $groups->hasMorePages()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">›</button>
                        </div>
                    </div>
                @endif
            </div>
        </x-surface-card>

        {{-- ═════════════ COMPETENCY LEVELS ═════════════ --}}
        @php ($levels = $this->catalogLevels)
        <x-surface-card :title="__('training_needs::dashboard.cards.competency_levels')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            @if ($editingLevelId)
                <div class="mb-3 flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    <span class="truncate">{{ __('training_needs::dashboard.actions.edit') }}: <span class="font-semibold">{{ $levelForm['name'] }}</span></span>
                    <button type="button" wire:click="cancelLevelEdit" class="flex-none text-xs font-medium underline hover:no-underline">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                </div>
            @endif
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <x-label for="level-name">{{ __('training_needs::dashboard.fields.level_name') }}</x-label>
                    <x-livewire-input mode="gray" id="level-name" wire:model.defer="levelForm.name" />
                    @error('levelForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="level-score">{{ __('training_needs::dashboard.fields.score') }}</x-label>
                    <x-livewire-input mode="gray" id="level-score" type="number" wire:model.defer="levelForm.score" />
                    @error('levelForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="level-sort-order">{{ __('training_needs::dashboard.fields.sort_order') }}</x-label>
                    <x-livewire-input mode="gray" id="level-sort-order" type="number" wire:model.defer="levelForm.sort_order" />
                    @error('levelForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="levelForm.is_default" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('training_needs::dashboard.fields.is_default') }}
                </label>
                <div class="md:col-span-2">
                    <x-label for="level-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                    <textarea id="level-description" wire:model.defer="levelForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                    @error('levelForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <x-button mode="black" wire:click="storeLevel">{{ __('training_needs::dashboard.actions.save_level') }}</x-button>
                    @if ($editingLevelId)
                        <button type="button" wire:click="cancelLevelEdit" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-t border-zinc-100 pt-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase text-zinc-400">
                        {{ __('training_needs::dashboard.cards.competency_levels') }}
                        <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-zinc-600">{{ $levels->total() }}</span>
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="levelListSearch" placeholder="{{ __('training_needs::dashboard.fields.search') }}" class="h-8 w-36 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs focus:border-blue-400 focus:ring-blue-400">
                </div>
                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-100">
                    @forelse ($levels as $level)
                        <div @class(['flex items-center justify-between gap-2 px-3 py-2', 'bg-amber-50/60' => $editingLevelId === $level->id])>
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-zinc-800">{{ $level->name }}</span>
                                <span class="block text-xs text-zinc-400">{{ __('training_needs::dashboard.fields.score') }}: {{ $level->score }}{{ $level->is_default ? ' · '.__('training_needs::dashboard.fields.is_default') : '' }}</span>
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button type="button" wire:click="editLevel({{ $level->id }})" title="{{ __('training_needs::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('training_needs::dashboard.confirmations.delete_level')), confirmText: @js(__('training_needs::dashboard.actions.delete')), run: () => $wire.deleteLevel({{ $level->id }}) })"
                                    title="{{ __('training_needs::dashboard.actions.delete') }}"
                                    class="{{ $deleteBtn }}">{!! $trashIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('training_needs::dashboard.empty.full_lists') }}</div>
                    @endforelse
                </div>
                @if ($levels->hasPages())
                    <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ $levels->firstItem() }}–{{ $levels->lastItem() }} / {{ $levels->total() }}</span>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="previousPage('levelsPage')" @disabled($levels->onFirstPage()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">‹</button>
                            <button type="button" wire:click="nextPage('levelsPage')" @disabled(! $levels->hasMorePages()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">›</button>
                        </div>
                    </div>
                @endif
            </div>
        </x-surface-card>

        {{-- ═════════════ COMPETENCIES ═════════════ --}}
        @php ($competencies = $this->catalogCompetencies)
        <x-surface-card :title="__('training_needs::dashboard.cards.competencies')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            @if ($editingCompetencyId)
                <div class="mb-3 flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    <span class="truncate">{{ __('training_needs::dashboard.actions.edit') }}: <span class="font-semibold">{{ $competencyForm['name'] }}</span></span>
                    <button type="button" wire:click="cancelCompetencyEdit" class="flex-none text-xs font-medium underline hover:no-underline">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                </div>
            @endif
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.group')"
                        placeholder="---"
                        mode="gray"
                        direction="auto"
                        class="w-full"
                        wire:model.live="competencyForm.training_competency_group_id"
                        :model="$this->competencyGroupOptions()"
                        search-model="searchCompetencyGroup"
                    ></x-ui.select-dropdown>
                    @error('competencyForm.training_competency_group_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-label for="competency-name">{{ __('training_needs::dashboard.fields.competency_name') }}</x-label>
                    <x-livewire-input mode="gray" id="competency-name" wire:model.defer="competencyForm.name" />
                    @error('competencyForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-label for="competency-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                    <textarea id="competency-description" wire:model.defer="competencyForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                    @error('competencyForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="competencyForm.is_mandatory" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('training_needs::dashboard.fields.is_mandatory') }}
                </label>
                <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="competencyForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('training_needs::dashboard.fields.is_active') }}
                </label>
                <div class="md:col-span-2 flex items-center gap-2">
                    <x-button mode="black" wire:click="storeCompetency">{{ __('training_needs::dashboard.actions.save_competency') }}</x-button>
                    @if ($editingCompetencyId)
                        <button type="button" wire:click="cancelCompetencyEdit" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-t border-zinc-100 pt-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase text-zinc-400">
                        {{ __('training_needs::dashboard.cards.competencies') }}
                        <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-zinc-600">{{ $competencies->total() }}</span>
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="competencyListSearch" placeholder="{{ __('training_needs::dashboard.fields.search') }}" class="h-8 w-36 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs focus:border-blue-400 focus:ring-blue-400">
                </div>
                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-100">
                    @forelse ($competencies as $competency)
                        <div @class(['flex items-center justify-between gap-2 px-3 py-2', 'bg-amber-50/60' => $editingCompetencyId === $competency->id])>
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-zinc-800">{{ $competency->name }}</span>
                                <span class="block text-xs text-zinc-400">{{ $competency->group?->name ?? '—' }}{{ $competency->is_mandatory ? ' · '.__('training_needs::dashboard.fields.is_mandatory') : '' }}</span>
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button type="button" wire:click="editCompetency({{ $competency->id }})" title="{{ __('training_needs::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('training_needs::dashboard.confirmations.delete_competency')), confirmText: @js(__('training_needs::dashboard.actions.delete')), run: () => $wire.deleteCompetency({{ $competency->id }}) })"
                                    title="{{ __('training_needs::dashboard.actions.delete') }}"
                                    class="{{ $deleteBtn }}">{!! $trashIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('training_needs::dashboard.empty.full_lists') }}</div>
                    @endforelse
                </div>
                @if ($competencies->hasPages())
                    <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ $competencies->firstItem() }}–{{ $competencies->lastItem() }} / {{ $competencies->total() }}</span>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="previousPage('competenciesPage')" @disabled($competencies->onFirstPage()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">‹</button>
                            <button type="button" wire:click="nextPage('competenciesPage')" @disabled(! $competencies->hasMorePages()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">›</button>
                        </div>
                    </div>
                @endif
            </div>
        </x-surface-card>

        {{-- ═════════════ PROGRAMS ═════════════ --}}
        @php ($programs = $this->catalogPrograms)
        <x-surface-card :title="__('training_needs::dashboard.cards.programs')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            @if ($editingProgramId)
                <div class="mb-3 flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    <span class="truncate">{{ __('training_needs::dashboard.actions.edit') }}: <span class="font-semibold">{{ $programForm['title'] }}</span></span>
                    <button type="button" wire:click="cancelProgramEdit" class="flex-none text-xs font-medium underline hover:no-underline">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                </div>
            @endif
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-label for="program-title">{{ __('training_needs::dashboard.fields.program_title') }}</x-label>
                    <x-livewire-input mode="gray" id="program-title" wire:model.defer="programForm.title" />
                    @error('programForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="program-code">{{ __('training_needs::dashboard.fields.program_code') }}</x-label>
                    <x-livewire-input mode="gray" id="program-code" wire:model.defer="programForm.code" />
                    @error('programForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="program-hours">{{ __('training_needs::dashboard.fields.duration_hours') }}</x-label>
                    <x-livewire-input mode="gray" id="program-hours" type="number" step="0.25" wire:model.defer="programForm.duration_hours" />
                    @error('programForm.duration_hours') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="delivery-type">{{ __('training_needs::dashboard.fields.delivery_type') }}</x-label>
                    <select id="delivery-type" wire:model.defer="programForm.delivery_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="internal">{{ __('training_needs::dashboard.delivery_types.internal') }}</option>
                        <option value="external">{{ __('training_needs::dashboard.delivery_types.external') }}</option>
                        <option value="hybrid">{{ __('training_needs::dashboard.delivery_types.hybrid') }}</option>
                    </select>
                    @error('programForm.delivery_type') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="programForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('training_needs::dashboard.fields.is_active') }}
                </label>
                <div class="md:col-span-2">
                    <x-label for="program-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                    <textarea id="program-description" wire:model.defer="programForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                    @error('programForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <x-button mode="black" wire:click="storeProgram">{{ __('training_needs::dashboard.actions.save_program') }}</x-button>
                    @if ($editingProgramId)
                        <button type="button" wire:click="cancelProgramEdit" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('training_needs::dashboard.actions.cancel_edit') }}</button>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-t border-zinc-100 pt-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase text-zinc-400">
                        {{ __('training_needs::dashboard.cards.programs') }}
                        <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-zinc-600">{{ $programs->total() }}</span>
                    </span>
                    <input type="search" wire:model.live.debounce.300ms="programListSearch" placeholder="{{ __('training_needs::dashboard.fields.search') }}" class="h-8 w-36 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs focus:border-blue-400 focus:ring-blue-400">
                </div>
                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-100">
                    @forelse ($programs as $program)
                        <div @class(['flex items-center justify-between gap-2 px-3 py-2', 'bg-amber-50/60' => $editingProgramId === $program->id])>
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-zinc-800">{{ $program->title }}</span>
                                <span class="block text-xs text-zinc-400">{{ $program->code ? $program->code.' · ' : '' }}{{ __('training_needs::dashboard.delivery_types.'.$program->delivery_type) }}{{ $program->duration_hours ? ' · '.$program->duration_hours.' '.__('training_needs::dashboard.fields.duration_hours') : '' }}</span>
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button type="button" wire:click="editProgram({{ $program->id }})" title="{{ __('training_needs::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('training_needs::dashboard.confirmations.delete_program')), confirmText: @js(__('training_needs::dashboard.actions.delete')), run: () => $wire.deleteProgram({{ $program->id }}) })"
                                    title="{{ __('training_needs::dashboard.actions.delete') }}"
                                    class="{{ $deleteBtn }}">{!! $trashIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('training_needs::dashboard.empty.full_lists') }}</div>
                    @endforelse
                </div>
                @if ($programs->hasPages())
                    <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ $programs->firstItem() }}–{{ $programs->lastItem() }} / {{ $programs->total() }}</span>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="previousPage('programsPage')" @disabled($programs->onFirstPage()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">‹</button>
                            <button type="button" wire:click="nextPage('programsPage')" @disabled(! $programs->hasMorePages()) class="rounded-lg border border-zinc-200 px-2 py-1 hover:bg-zinc-50 disabled:opacity-40">›</button>
                        </div>
                    </div>
                @endif
            </div>
        </x-surface-card>
    </div>

    <x-surface-card :title="__('training_needs::dashboard.cards.program_competency_map')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-4">
            <div>
                <x-ui.select-dropdown
                    :label="__('training_needs::dashboard.fields.program')"
                    placeholder="---"
                    mode="gray"
                    direction="auto"
                    class="w-full"
                    wire:model.live="programMapForm.training_program_id"
                    :model="$this->trainingProgramOptions()"
                    search-model="searchTrainingProgram"
                ></x-ui.select-dropdown>
                @error('programMapForm.training_program_id') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
            <div>
                <x-ui.select-dropdown
                    :label="__('training_needs::dashboard.fields.competency')"
                    placeholder="---"
                    mode="gray"
                    direction="auto"
                    class="w-full"
                    wire:model.live="programMapForm.training_competency_id"
                    :model="$this->competencyOptions()"
                    search-model="searchCompetency"
                ></x-ui.select-dropdown>
                @error('programMapForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
            <div>
                <x-ui.select-dropdown
                    :label="__('training_needs::dashboard.fields.target_level')"
                    placeholder="---"
                    mode="gray"
                    direction="auto"
                    class="w-full"
                    wire:model.live="programMapForm.target_level_id"
                    :model="$this->competencyLevelOptions()"
                    search-model="searchCompetencyLevel"
                ></x-ui.select-dropdown>
                @error('programMapForm.target_level_id') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
            <div class="flex items-end">
                <x-button mode="black" wire:click="storeProgramMap">{{ __('training_needs::dashboard.actions.save_program_map') }}</x-button>
            </div>
        </div>
    </x-surface-card>
@endif
