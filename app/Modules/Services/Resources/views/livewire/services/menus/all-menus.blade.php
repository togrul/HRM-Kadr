@php
    $iconBtn = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $editBtn = $iconBtn.' hover:bg-[#f4f4f5] hover:text-ink';
    $delBtn = $iconBtn.' hover:bg-rose-50 hover:text-rose-600';
@endphp

<div class="flex flex-col" x-data>
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::common.labels.menus') }}</h2>

            <x-pill-button variant="primary" wire:click.prevent="openSideMenu('add-menu')">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('services::menus.actions.add_menu') }}
            </x-pill-button>
        </div>

        <x-table.tbl :headers="[
            __('services::common.labels.name'),
            __('services::common.labels.color'),
            __('services::common.labels.order'),
            __('services::common.labels.url'),
            __('services::common.labels.active_question'),
            __('services::common.labels.action'),
        ]">
            @forelse ($_menus as $menu)
                <tr wire:key="services-menu-{{ $menu->id }}">
                    <x-table.td standart-width>
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted">
                                <x-dynamic-component :component="$this->displayMenuIconComponent($menu)" color="text-current" size="w-[17px] h-[17px]" />
                            </span>
                            <span class="truncate text-[13px] font-medium text-ink">{{ $this->displayMenuName($menu) }}</span>
                        </div>
                    </x-table.td>

                    <x-table.td>
                        <span class="inline-flex items-center gap-2 text-[13px] text-ink-soft">
                            <span class="h-2.5 w-2.5 rounded-full bg-{{ $menu->color }}-500"></span>
                            {{ $menu->color }}
                        </span>
                    </x-table.td>

                    <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $menu->order }}</span></x-table.td>
                    <x-table.td><span class="text-[13px] text-ink-muted">{{ $menu->url }}</span></x-table.td>

                    <x-table.td>
                        <span @class([
                            'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium',
                            'bg-emerald-50 text-emerald-700' => $menu->is_active,
                            'bg-[#f4f4f5] text-ink-muted' => ! $menu->is_active,
                        ])>{{ $menu->is_active ? __('services::common.labels.active') : __('services::common.labels.inactive') }}</span>
                    </x-table.td>

                    <x-table.td :isButton="true">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" wire:click.prevent="openSideMenu('edit-menu',{{ $menu->id }})" title="{{ __('services::menus.titles.edit') }}" class="{{ $editBtn }}">
                                <x-icons.edit-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.edit-icon>
                            </button>
                            <button type="button" wire:click.prevent="setDeleteMenu({{ $menu->id }})" title="{{ __('services::menus.titles.delete') }}" class="{{ $delBtn }}">
                                <x-icons.delete-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.delete-icon>
                            </button>
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4">
                        <x-ui.empty-state icon="icons.menu-icon" :title="__('services::common.labels.menus')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>
    </section>

    <x-side-modal>
        @if ($showSideMenu == 'add-menu')
            <livewire:services.menus.add-menu wire:key="services-menu-add-modal" />
        @endif

        @if ($showSideMenu == 'edit-menu')
            <livewire:services.menus.edit-menu :menuModel="$modelName" :key="'services-menu-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    @auth
        <livewire:services.menus.delete-menu wire:key="services-menu-delete-modal" />
    @endauth
</div>
