@php
    $iconBtn = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $editBtn = $iconBtn.' hover:bg-[#f4f4f5] hover:text-ink';
    $delBtn = $iconBtn.' hover:bg-rose-50 hover:text-rose-600';
    $restoreBtn = $iconBtn.' hover:bg-emerald-50 hover:text-emerald-600';
@endphp

<div class="flex flex-col" x-data>
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <x-filter.nav>
                <x-filter.item wire:click.prevent="setStatus(1)" :active="$status === 1">
                    {{ __('services::common.labels.active') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="setStatus(0)" :active="$status === 0">
                    {{ __('services::common.labels.inactive') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="setStatus(2)" :active="$status === 2">
                    {{ __('services::common.labels.deleted') }}
                </x-filter.item>
            </x-filter.nav>

            <div class="flex flex-wrap items-center gap-2">
                <div class="w-full sm:w-[240px]">
                    <x-ui.input icon="search" id="q" name="q" wire:model.live.debounce.300ms="q" autocomplete="off" placeholder="{{ __('services::users.fields.user_name_or_email') }}" />
                </div>

                <x-pill-button variant="secondary" wire:click.prevent="resetFilter">{{ __('services::common.actions.reset_filter') }}</x-pill-button>

                <x-pill-button variant="primary" wire:click.prevent="openSideMenu('add-user')">
                    <x-icons.add-user color="text-current" hover="text-current" size="w-4 h-4"></x-icons.add-user>
                    {{ __('services::users.actions.add_user') }}
                </x-pill-button>
            </div>
        </div>

        <x-table.tbl :headers="[
            __('services::common.labels.user'),
            __('services::common.labels.role'),
            __('services::common.labels.email'),
            __('services::common.labels.active_question'),
            __('services::common.labels.action'),
        ]">
            @forelse ($_users as $user)
                <tr wire:key="user-row-{{ $user->id }}">
                    <x-table.td standart-width>
                        <p class="truncate text-[13px] font-medium text-ink"><span class="hrm-num text-ink-faint">{{ $user->row_no }}.</span> {{ $user->name }}</p>
                        @if ($status == 2)
                            <p class="mt-0.5 truncate text-[11px] text-ink-faint">
                                {{ __('services::common.labels.deleted_date') }}: <span class="hrm-num">{{ $user->deleted_at_label }}</span>
                                <span class="px-0.5">·</span> {{ __('services::common.labels.deleted_by') }}: {{ $user->deleted_by_name }}
                            </p>
                        @endif
                    </x-table.td>

                    <x-table.td>
                        @if ($user->primary_role)
                            <span class="inline-flex items-center rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-medium uppercase text-ink-muted">{{ $user->primary_role }}</span>
                        @endif
                    </x-table.td>

                    <x-table.td><span class="text-[13px] text-ink-muted">{{ $user->email }}</span></x-table.td>

                    <x-table.td>
                        <span @class([
                            'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium',
                            'bg-emerald-50 text-emerald-700' => $user->is_active,
                            'bg-[#f4f4f5] text-ink-muted' => ! $user->is_active,
                        ])>{{ $user->is_active ? __('services::common.labels.active') : __('services::common.labels.inactive') }}</span>
                    </x-table.td>

                    <x-table.td :isButton="true">
                        <div class="flex items-center justify-end gap-1">
                            @if ($status == 2)
                                <button type="button" wire:click="restoreData({{ $user->id }})" title="{{ __('services::common.actions.restore') }}" class="{{ $restoreBtn }}">
                                    <x-icons.recover color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.recover>
                                </button>

                                {{-- a Blade directive is not compiled inside a component tag; Js::from through the echo tag is --}}
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: {{ \Illuminate\Support\Js::from(__('services::users.messages.force_delete_confirm')) }}, confirmText: {{ \Illuminate\Support\Js::from(__('services::common.actions.force_delete')) }}, run: () => $wire.forceDeleteData({{ $user->id }}) })"
                                    title="{{ __('services::common.actions.force_delete') }}" class="{{ $delBtn }}">
                                    <x-icons.force-delete color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.force-delete>
                                </button>
                            @else
                                <button type="button" wire:click.prevent="openSideMenu('edit-user',{{ $user->id }})" title="{{ __('services::users.titles.edit') }}" class="{{ $editBtn }}">
                                    <x-icons.edit-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.edit-icon>
                                </button>

                                <button type="button" wire:click.prevent="setDeleteUser({{ $user->id }})" title="{{ __('services::users.titles.delete') }}" class="{{ $delBtn }}">
                                    <x-icons.delete-icon color="text-current" hover="text-current" size="w-[17px] h-[17px]"></x-icons.delete-icon>
                                </button>
                            @endif
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-4">
                        <x-ui.empty-state icon="icons.users-icon" :title="__('services::common.labels.users')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>

        <x-pagination :paginator="$_users" :unit="__('services::common.labels.users')" />
    </section>

    <x-side-modal>
        @if ($showSideMenu == 'add-user')
            <livewire:services.users.add-user wire:key="services-user-add-modal" />
        @endif

        @if ($showSideMenu == 'edit-user')
            <livewire:services.users.edit-user :userModel="$modelName" :key="'services-user-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    @auth
        <livewire:services.users.delete-user wire:key="services-user-delete-modal" />
    @endauth
</div>
