<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.components.title') }}</h2>
            <div class="w-full sm:max-w-[280px]">
                <x-ui.input icon="search" wire:model.live.debounce.300ms="componentSearch" placeholder="{{ __('compensation::dashboard.actions.search') }}" />
            </div>
        </div>

        <x-table.tbl :headers="[
            __('compensation::dashboard.columns.component'),
            __('compensation::dashboard.columns.type'),
            __('compensation::dashboard.columns.calc_type'),
            __('compensation::dashboard.columns.flags'),
            __('compensation::dashboard.columns.actions'),
        ]">
            @forelse ($this->components as $component)
                <tr wire:key="compensation-component-{{ $component->id }}">
                    <x-table.td standart-width>
                        <p class="max-w-[260px] truncate text-[13px] font-medium text-ink">{{ $component->name }}</p>
                        <p class="hrm-num truncate text-[11px] uppercase tracking-[0.04em] text-ink-faint">{{ $component->code }}</p>
                    </x-table.td>
                    <x-table.td>
                        <x-small-badge :mode="$component->type === 'earning' ? 'green' : 'rose'" dot>
                            {{ __('compensation::dashboard.types.'.$component->type) }}
                        </x-small-badge>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-[13px] text-ink-soft">{{ __('compensation::dashboard.calc_types.'.$component->calc_type) }}</span>
                    </x-table.td>
                    <x-table.td>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($component->is_statutory)
                                <x-small-badge mode="sky">{{ __('compensation::dashboard.fields.is_statutory') }}</x-small-badge>
                            @endif
                            @if ($component->taxable)
                                <x-small-badge mode="secondary">{{ __('compensation::dashboard.fields.taxable') }}</x-small-badge>
                            @endif
                            @if ($component->affects_social)
                                <x-small-badge mode="secondary">{{ __('compensation::dashboard.fields.affects_social') }}</x-small-badge>
                            @endif
                        </div>
                    </x-table.td>
                    <x-table.td :isButton="true">
                        @if ($canManage)
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" wire:click="editComponent({{ $component->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button" x-on:click="{{ $confirmDelete('deleteComponent('.$component->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            </div>
                        @endif
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.components.empty')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>

        <x-pagination :paginator="$this->components" :unit="__('compensation::dashboard.summary.components')" />
    </section>

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::dashboard.components.title')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.code')" :error="$errors->first('componentForm.code')">
                        <x-ui.input wire:model="componentForm.code" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.name')" :error="$errors->first('componentForm.name')">
                        <x-ui.input wire:model="componentForm.name" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.type')">
                        <x-ui.select wire:model.live="componentForm.type">
                            <option value="earning">{{ __('compensation::dashboard.types.earning') }}</option>
                            <option value="deduction">{{ __('compensation::dashboard.types.deduction') }}</option>
                        </x-ui.select>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.calc_type')">
                        <x-ui.select wire:model.live="componentForm.calc_type">
                            @foreach (['fixed', 'percent', 'formula', 'per_diem', 'rate'] as $calcType)
                                <option value="{{ $calcType }}">{{ __('compensation::dashboard.calc_types.'.$calcType) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.gl_code')">
                        <x-ui.input wire:model="componentForm.gl_code" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.sort')">
                        <x-ui.input type="number" wire:model="componentForm.sort" />
                    </x-ui.input-shell>
                    <div class="flex flex-wrap gap-4 pt-1 sm:col-span-2">
                        @foreach (['taxable', 'affects_social', 'is_statutory', 'is_active'] as $flag)
                            <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                                <input type="checkbox" wire:model="componentForm.{{ $flag }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                {{ __('compensation::dashboard.fields.'.$flag) }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveComponent'])
        </x-ui.side-panel>
    @endif
</div>
