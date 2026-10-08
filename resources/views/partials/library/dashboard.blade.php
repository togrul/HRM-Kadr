{{--
    Shared page of the learning and onboarding libraries (Livewire view of
    App\Support\Livewire\AbstractLibraryDashboard). $library is the subclass's libraryConfig():
    translation namespace, property/method names, permissions and create-form fields.
--}}
@php
    $ns = $library['ns'];
    $form = $library['form'];
    $contextTabs = ['library', 'assignments', 'reports'];
@endphp

<div class="flex flex-col">
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel>
            <x-context-panel.section :title="__($ns.'.title')">
                @foreach ($contextTabs as $tab)
                    <x-context-panel.item
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                    >{{ __($ns.'.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    <x-page-header :title="__($ns.'.title')" :breadcrumb="$library['breadcrumb']">
        <x-slot:icon>
            @if ($library['icon'] === 'onboarding')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg>
            @else
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>
            @endif
        </x-slot:icon>

        @if ($library['can_manage'])
            <x-slot:actions>
                <x-pill-button variant="primary" wire:click="openCreate">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __($ns.'.catalog.add') }}
                </x-pill-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="space-y-5 px-4 py-4 sm:px-5">
        <div class="rounded-2xl border border-hairline bg-white p-3 shadow-card lg:hidden">
            <x-filter.nav class="min-w-0">
                @foreach ($contextTabs as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __($ns.'.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>

        @if ($activeTab === 'library')
            @php
                $catalog = $this->catalogPayload;
                $items = $catalog['items'];
                $libraryIsEmpty = $catalog['status_counts']['all'] === 0 && $catalog['status_counts']['archived'] === 0;
            @endphp

            <div class="grid gap-3 sm:grid-cols-3">
                <x-ui.metric-tile :label="__($ns.'.catalog.metrics.active')" :value="$catalog['metrics']['active']" tone="green" />
                <x-ui.metric-tile :label="__($ns.'.catalog.metrics.assigned_this_month')" :value="$catalog['metrics']['assigned_this_month']" tone="blue" />
                <x-ui.metric-tile :label="__($ns.'.catalog.metrics.completion')" :value="$catalog['metrics']['completion'].'%'" />
            </div>

            @if ($libraryIsEmpty)
                <div class="rounded-2xl border border-hairline bg-white px-6 py-10 shadow-card">
                    <x-ui.empty-state :title="__($ns.'.catalog.empty_title')" :message="__($ns.'.catalog.empty_message')" />
                    @if ($library['can_manage'])
                        <div class="flex justify-center">
                            <x-pill-button variant="primary" wire:click="openCreate">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                {{ __($ns.'.catalog.add') }}
                            </x-pill-button>
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-3 rounded-2xl border border-hairline bg-white p-3 shadow-card">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <x-ui.filter-input wire:model.live.debounce.300ms="{{ $library['search'] }}" type="search" :placeholder="__($ns.'.catalog.search_placeholder')" :aria-label="__($ns.'.catalog.search_placeholder')" />
                        </div>
                        <div class="sm:w-56">
                            <x-ui.filter-native-select wire:model.live="typeFilter" :aria-label="__($ns.'.catalog.type')">
                                <option value="">{{ __($ns.'.catalog.all_types') }}</option>
                                @foreach ($library['type_options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.filter-native-select>
                        </div>
                    </div>

                    <x-filter.nav wrap :aria-label="__($ns.'.catalog.status')">
                        @foreach ($catalog['status_counts'] as $status => $count)
                            <x-filter.item wire:click.prevent="$set('statusFilter', '{{ $status }}')" :active="$statusFilter === $status">
                                {{ __($ns.'.catalog.statuses.'.$status) }}
                                <span class="hrm-num ml-1.5 text-[12px] text-ink-faint">{{ $count }}</span>
                            </x-filter.item>
                        @endforeach
                    </x-filter.nav>
                </div>

                @if ($items->isEmpty())
                    <div class="rounded-2xl border border-hairline bg-white px-6 py-8 shadow-card">
                        <x-ui.empty-state :message="__($ns.'.catalog.no_results')" />
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($items as $item)
                            <div wire:key="library-item-{{ $item['id'] }}" class="flex flex-col rounded-2xl border border-hairline bg-white p-4 shadow-card">
                                <h3 class="text-[14px] font-semibold leading-5 tracking-[-0.01em] text-ink">{{ $item['title'] }}</h3>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[12px] text-ink-muted">
                                    <span class="inline-flex items-center rounded-full border border-hairline bg-[#fafafa] px-2 py-0.5">{{ $item['type'] }}</span>
                                    @if (filled($item['meta']))
                                        <span class="hrm-num">{{ $item['meta'] }}</span>
                                    @endif
                                    @if ($item['is_archived'])
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-amber-700">{{ __($ns.'.catalog.statuses.archived') }}</span>
                                    @elseif (! $item['is_active'])
                                        <span class="inline-flex items-center rounded-full border border-hairline bg-[#f4f4f5] px-2 py-0.5">{{ __($ns.'.catalog.statuses.inactive') }}</span>
                                    @endif
                                </div>

                                <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                                    @if ($library['can_assign'] && ! $item['is_archived'])
                                        <x-pill-button wire:click="openAssign({{ $item['id'] }})">{{ __($ns.'.catalog.assign') }}</x-pill-button>
                                    @else
                                        <span></span>
                                    @endif

                                    @if ($library['can_manage'] || $item['url'])
                                        <x-ui.row-menu>
                                            @if ($item['url'])
                                                <x-ui.row-menu.item :href="$item['url']" target="_blank" rel="noopener">{{ __($ns.'.catalog.open') }}</x-ui.row-menu.item>
                                            @endif
                                            @if ($library['can_manage'])
                                                <x-ui.row-menu.item wire:click="{{ $library['new_version'] }}({{ $item['id'] }})">{{ __($ns.'.actions.new_version') }}</x-ui.row-menu.item>
                                                <x-ui.row-menu.separator />
                                                @php
                                                    $activeAction = $item['is_active'] ? 'deactivate' : 'activate';
                                                    $archiveAction = $item['is_archived'] ? 'restore' : 'archive';
                                                @endphp
                                                <x-ui.row-menu.item
                                                    data-title="{{ __($ns.'.catalog.confirm.'.$activeAction.'_title') }}"
                                                    data-message="{{ __($ns.'.catalog.confirm.'.$activeAction.'_message', ['title' => $item['title']]) }}"
                                                    data-confirm="{{ __($ns.'.catalog.'.$activeAction) }}"
                                                    x-on:click="$dispatch('confirm-action', { title: $el.dataset.title, message: $el.dataset.message, confirmText: $el.dataset.confirm, tone: 'amber', run: () => $wire.{{ $library['toggle_active'] }}({{ $item['id'] }}) })"
                                                >{{ __($ns.'.catalog.'.$activeAction) }}</x-ui.row-menu.item>
                                                <x-ui.row-menu.item
                                                    :danger="$archiveAction === 'archive'"
                                                    data-title="{{ __($ns.'.catalog.confirm.'.$archiveAction.'_title') }}"
                                                    data-message="{{ __($ns.'.catalog.confirm.'.$archiveAction.'_message', ['title' => $item['title']]) }}"
                                                    data-confirm="{{ __($ns.'.catalog.'.$archiveAction) }}"
                                                    x-on:click="$dispatch('confirm-action', { title: $el.dataset.title, message: $el.dataset.message, confirmText: $el.dataset.confirm, tone: 'amber', run: () => $wire.{{ $library['toggle_archived'] }}({{ $item['id'] }}) })"
                                                >{{ __($ns.'.catalog.'.$archiveAction) }}</x-ui.row-menu.item>
                                                @if (($library['delete'] ?? null) && $item['can_delete'])
                                                    <x-ui.row-menu.item
                                                        :danger="true"
                                                        data-title="{{ __($ns.'.catalog.confirm.delete_title') }}"
                                                        data-message="{{ __($ns.'.catalog.confirm.delete_message', ['title' => $item['title']]) }}"
                                                        data-confirm="{{ __($ns.'.catalog.delete') }}"
                                                        x-on:click="$dispatch('confirm-action', { title: $el.dataset.title, message: $el.dataset.message, confirmText: $el.dataset.confirm, tone: 'rose', run: () => $wire.{{ $library['delete'] }}({{ $item['id'] }}) })"
                                                    >{{ __($ns.'.catalog.delete') }}</x-ui.row-menu.item>
                                                @endif
                                            @endif
                                        </x-ui.row-menu>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($items->hasPages())
                        <div>{{ $items->onEachSide(1)->links() }}</div>
                    @endif
                @endif
            @endif
        @elseif ($activeTab === 'assignments')
            @php
                $recent = $this->generalPayload['recent_assignments'];
            @endphp
            <div class="rounded-2xl border border-hairline bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __($ns.'.sections.recent_assignments') }}</x-ui.field-label>
                    @if ($library['can_assign'])
                        <x-pill-button wire:click="openAssign">{{ __($ns.'.catalog.assign') }}</x-pill-button>
                    @endif
                </div>

                @if ($recent->isEmpty())
                    <x-ui.empty-state class="mt-2" :message="__($ns.'.messages.empty_assignments')" />
                @else
                    <div class="mt-4 divide-y divide-hairline-subtle">
                        @foreach ($recent as $assignment)
                            @php
                                $doneAt = $assignment['completed_at'] ?? $assignment['acknowledged_at'] ?? '—';
                            @endphp
                            <div wire:key="library-assignment-{{ $assignment['id'] }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold text-ink">{{ $assignment['asset'] ?? $assignment['template'] }}</p>
                                    <p class="text-[12.5px] text-ink-muted">{{ $assignment['personnel'] }} · {{ $assignment['position'] }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-[12px]">
                                    <span class="hrm-num text-ink-faint">{{ $assignment['assigned_at'] }}</span>
                                    <span @class([
                                        'inline-flex items-center rounded-full border px-2 py-0.5 font-medium',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $assignment['status_mode'] === 'emerald',
                                        'border-rose-200 bg-rose-50 text-rose-700' => $assignment['status_mode'] === 'rose',
                                        'border-sky-200 bg-sky-50 text-sky-700' => $assignment['status_mode'] === 'sky',
                                        'border-hairline bg-[#f4f4f5] text-ink-soft' => ! in_array($assignment['status_mode'], ['emerald', 'rose', 'sky'], true),
                                    ])>{{ $assignment['status'] }}</span>
                                    @if ($doneAt !== '—')
                                        <span class="hrm-num text-ink-faint">{{ $doneAt }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($recent->hasPages())
                        <div class="mt-4">{{ $recent->onEachSide(1)->links() }}</div>
                    @endif
                @endif
            </div>
        @else
            <div class="rounded-2xl border border-hairline bg-white p-5 shadow-card">
                <x-ui.field-label as="div" class="tracking-tight">{{ __($ns.'.sections.reports') }}</x-ui.field-label>
                <p class="mt-1 max-w-2xl text-[13px] leading-6 text-ink-muted">{{ __($ns.'.messages.report_hint') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($this->exportActions as $action)
                        <x-pill-button wire:click="{{ $action['method'] }}">{{ __($ns.'.actions.'.$action['label']) }}</x-pill-button>
                    @endforeach
                </div>
                <div class="mt-5">
                    <x-library.analytics-grid :translation-ns="$ns" :analytics="$this->reportsPayload['analytics']" />
                </div>
            </div>
        @endif
    </div>

    <x-side-modal>
        @if ($showSideMenu === 'library-create' && $library['can_manage'])
            <h2 class="text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ __($ns.'.catalog.'.(($library['is_new_version'] ?? false) ? 'version_title' : 'create_title')) }}</h2>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach ($library['fields'] as $field)
                    @php
                        $model = $form.'.'.$field['key'];
                        $label = __($ns.'.fields.'.$field['label']);
                    @endphp
                    @if ($field['type'] !== 'checkbox')
                        <div @class(['md:col-span-2' => $field['wide'] ?? false])>
                            <x-ui.input-shell :label="$label" :error="$errors->first($model)">
                                @if ($field['type'] === 'select')
                                    <x-ui.filter-native-select wire:model="{{ $model }}">
                                        @foreach ($field['options'] as $value => $optionLabel)
                                            <option value="{{ $value }}">{{ $optionLabel }}</option>
                                        @endforeach
                                    </x-ui.filter-native-select>
                                @elseif ($field['type'] === 'textarea')
                                    <x-ui.filter-textarea wire:model="{{ $model }}" rows="4" />
                                @else
                                    <x-ui.filter-input wire:model="{{ $model }}" type="{{ $field['type'] }}" />
                                @endif
                            </x-ui.input-shell>
                        </div>
                    @endif
                @endforeach

                <div class="md:col-span-2">
                    <x-ui.file-upload-shell wire:model="{{ $library['upload'] }}" :label="__($ns.'.fields.file')" :error="$errors->first($library['upload'])" :upload="$this->{$library['upload']}" />
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3">
                @foreach ($library['fields'] as $field)
                    @if ($field['type'] === 'checkbox')
                        <label class="inline-flex items-center gap-2 text-[13px] text-ink-soft">
                            <input wire:model="{{ $form }}.{{ $field['key'] }}" type="checkbox" class="library-target-checkbox" />
                            {{ __($ns.'.fields.'.$field['label']) }}
                        </label>
                    @endif
                @endforeach
            </div>

            <div class="mt-6">
                <x-pill-button variant="primary" wire:click="{{ $library['save'] }}" wire:loading.attr="disabled" wire:target="{{ $library['save'] }}">{{ __($ns.'.catalog.save') }}</x-pill-button>
            </div>
        @endif

        @if ($showSideMenu === 'library-assign' && $library['can_assign'])
            @php
                $payload = $this->generalPayload;
                $pickerOptions = collect($payload[$library['assign_items']])
                    ->map(fn (array $option): array => [
                        'id' => $option['id'],
                        'label' => $option['title'].' · '.($option['type'] ?? 'v'.($option['version'] ?? '')),
                    ])
                    ->all();
                $assignModel = 'assignmentForm.'.$library['assign_key'];
            @endphp

            <h2 class="text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ __($ns.'.catalog.assign_title') }}</h2>
            <p class="mt-1 max-w-2xl text-[13px] leading-6 text-ink-muted">{{ __($ns.'.messages.rule_builder_hint') }}</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <x-ui.select-dropdown
                        :label="__($ns.'.catalog.picker_label')"
                        wire:model.live="{{ $assignModel }}"
                        :model="$pickerOptions"
                        instance="library-assign-picker"
                    >
                        <x-livewire-input
                            mode="gray"
                            name="library_picker_search"
                            x-model="localSearch"
                            :placeholder="__('ui::common.placeholders.search')"
                            x-on:click.stop
                            x-on:keydown.stop
                        />
                    </x-ui.select-dropdown>
                    @error($assignModel)
                        <p class="mt-1 text-[12px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input-shell :label="__($ns.'.fields.due_at')" :error="$errors->first('assignmentForm.due_at')">
                    <x-ui.filter-input wire:model="assignmentForm.due_at" type="date" />
                </x-ui.input-shell>
            </div>

            <x-library.bulk-target-builder
                :translation-ns="$ns"
                :payload="$payload"
                :selected-structure-ids="$selectedStructureIds"
                :selected-position-ids="$selectedPositionIds"
                :selected-personnel-ids="$selectedPersonnelIds"
                :assignment-form="$assignmentForm"
            />

            <div class="mt-6">
                <x-pill-button variant="primary" wire:click="assignSelected" wire:loading.attr="disabled" wire:target="assignSelected">{{ __($ns.'.actions.assign_selected') }}</x-pill-button>
            </div>
        @endif
    </x-side-modal>
</div>
