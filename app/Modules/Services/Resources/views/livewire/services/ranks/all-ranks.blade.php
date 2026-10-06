@php
    $iconBtn = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $editBtn = $iconBtn.' hover:bg-[#f4f4f5] hover:text-ink';
    $delBtn = $iconBtn.' hover:bg-rose-50 hover:text-rose-600';
@endphp

<div class="flex flex-col" x-data>
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <x-filter.nav>
                <x-filter.item wire:click.prevent="setStatus(1)" :active="$status === 1">
                    {{ __('services::common.labels.active') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="setStatus(0)" :active="$status === 0">
                    {{ __('services::common.labels.inactive') }}
                </x-filter.item>
            </x-filter.nav>

            <x-pill-button variant="primary" wire:click.prevent="openSideMenu('add-rank')">
                <x-icons.add-icon color="text-current" hover="text-current" size="w-4 h-4"></x-icons.add-icon>
                {{ __('services::ranks.actions.add_rank') }}
            </x-pill-button>
        </div>

        <x-table.tbl :headers="[
            __('services::common.labels.id'),
            __('services::common.labels.category'),
            __('services::common.labels.name'),
            __('services::common.labels.duration'),
            __('services::common.labels.active_question'),
            __('services::common.labels.action'),
        ]">
            @forelse ($_ranks as $rank)
                <tr wire:key="rank-row-{{ $rank->id }}">
                    <x-table.td><span class="hrm-num text-[13px] text-ink-faint">{{ $rank->id }}</span></x-table.td>

                    <x-table.td>
                        @if ($rank->rankCategory)
                            <span class="inline-flex items-center rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-medium text-ink-muted">{{ $rank->rankCategory->name }}</span>
                        @else
                            <span class="text-[13px] text-ink-faint">—</span>
                        @endif
                    </x-table.td>

                    <x-table.td standart-width>
                        <p class="truncate text-[13px] font-medium text-ink">{{ $rank->name_az }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $rank->name_en }} <span class="px-0.5">·</span> {{ $rank->name_ru }}</p>
                    </x-table.td>

                    <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $rank->duration ?? '—' }}</span></x-table.td>

                    <x-table.td>
                        <span @class([
                            'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium',
                            'bg-emerald-50 text-emerald-700' => $rank->is_active,
                            'bg-[#f4f4f5] text-ink-muted' => ! $rank->is_active,
                        ])>{{ $rank->is_active ? __('services::common.labels.active') : __('services::common.labels.inactive') }}</span>
                    </x-table.td>

                    <x-table.td :isButton="true">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" wire:click.prevent="openSideMenu('edit-rank',{{ $rank->id }})" title="{{ __('services::ranks.titles.edit') }}" class="{{ $editBtn }}">
                                <x-icons.edit-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.edit-icon>
                            </button>
                            <button type="button" wire:click.prevent="setDeleteRank({{ $rank->id }})" title="{{ __('services::ranks.titles.delete') }}" class="{{ $delBtn }}">
                                <x-icons.delete-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.delete-icon>
                            </button>
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4">
                        <x-ui.empty-state icon="icons.double-arrow-icon" :title="__('services::common.labels.ranks')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>

        <x-pagination :paginator="$_ranks" :unit="__('services::common.labels.ranks')" />
    </section>

    <x-side-modal>
        @if($showSideMenu == 'add-rank')
            <livewire:services.ranks.add-rank wire:key="services-rank-add-modal" />
        @endif

        @if($showSideMenu == 'edit-rank')
            <livewire:services.ranks.edit-rank :rankModel="$modelName" :key="'services-rank-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    @auth
        <livewire:services.ranks.delete-rank wire:key="services-rank-delete-modal" />
    @endauth
</div>
