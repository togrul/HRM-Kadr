<div
    class="flex flex-col"
    x-data
    x-init="
        const root = $el;
        const paintPaginator = () => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (paginator) {
                paginator.classList.add('bg-blue-50', 'text-blue-600');
            }
        };
        paintPaginator();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(paintPaginator));
            });
        }
    "
>

    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
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


        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="gray" wire:click.prevent="resetFilter">
                <x-icons.refresh-icon color="text-gray-400" hover="text-gray-200"></x-icons.refresh-icon>
                <span>{{ __('services::common.actions.reset_filter') }}</span>
            </x-button>
            {{-- @can('manage-settings') --}}
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openSideMenu('add-user')">
                <x-icons.add-user color="text-white" hover="text-gray-50"></x-icons.add-user>
                <span>{{ __('services::users.actions.add_user') }}</span>
            </x-button>
            {{-- @endcan --}}
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 my-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <div>
            <x-label for="q">{{ __('services::users.fields.user_name_or_email') }}</x-label>
            <x-livewire-input id="q" name="q" mode="gray" wire:model.live="q"
                autocomplete="off"></x-livewire-input>
        </div>
    </div>

    <div class="flex flex-col space-y-2 mt-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('services::common.labels.user'), __('services::common.labels.role'), __('services::common.labels.email'), __('services::common.labels.active_question'), __('services::common.labels.action'), __('services::common.labels.action')]">
                        @forelse ($_users as $user)
                            <tr wire:key="user-row-{{ $user->id }}">
                                <x-table.td>
                                    <span class="text-sm font-medium">
                                        {{ $user->row_no }}. {{ $user->name }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if ($user->primary_role)
                                        <span
                                            class="bg-blue-100 text-blue-500 rounded-lg px-2 py-1 text-xs font-medium uppercase font-mono whitespace-no-wrap">
                                            {{ $user->primary_role }}
                                        </span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-normal text-gray-700">
                                        {{ $user->email }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center justify-start">
                                        <x-icons.check-icon size="w-8 h-8" :color="$user->is_active ? 'text-green-400' : 'text-gray-300'"
                                            :hover="$user->is_active ? 'text-green-500' : 'text-gray-400'"></x-icons.check-icon>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status == 2)
                                        <div class="flex flex-col text-xs font-medium">
                                            <div class="flex items-center space-x-1">
                                                <span class="text-gray-500">{{ __('services::common.labels.deleted_date') }}:</span>
                                                <span class="text-black">{{ $user->deleted_at_label }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <span class="text-gray-500">{{ __('services::common.labels.deleted_by') }}:</span>
                                                <span class="text-black">{{ $user->deleted_by_name }}</span>
                                            </div>
                                        </div>
                                    @else
                                        {{-- @can('manage-settings') --}}
                                        <x-action-button
                                            wire:click.prevent="openSideMenu('edit-user',{{ $user->id }})"
                                            class="h-9 w-9 bg-zinc-100 hover:bg-zinc-200"
                                            :title="__('services::users.titles.edit')">
                                            <x-icons.edit-icon color="text-slate-400"
                                                hover="text-slate-500"></x-icons.edit-icon>
                                        </x-action-button>
                                        {{-- @endcan --}}
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status == 2)
                                        <x-action-button wire:click="restoreData({{ $user->id }})"
                                            class="h-9 w-9 hover:bg-teal-50"
                                            :title="__('services::common.actions.restore')">
                                            <x-icons.recover color="text-teal-500"
                                                hover="text-teal-600"></x-icons.recover>
                                        </x-action-button>
                                        {{-- @role('admin') --}}
                                        <x-action-button
                                            onclick="confirm('{{ __('services::users.messages.force_delete_confirm') }}') || event.stopImmediatePropagation()"
                                            wire:click.prevent="forceDeleteData({{ $user->id }})"
                                            class="h-9 w-9 hover:bg-red-50"
                                            :title="__('services::common.actions.force_delete')">
                                            <x-icons.force-delete color="text-rose-400"
                                                hover="text-rose-500"></x-icons.force-delete>
                                        </x-action-button>
                                        {{-- @endrole --}}
                                    @else
                                        {{-- @can('manage-settings') --}}
                                        <x-action-button wire:click.prevent = "setDeleteUser({{ $user->id }})"
                                            class="h-9 w-9 bg-rose-50 hover:bg-red-100"
                                            :title="__('services::users.titles.delete')">
                                            <x-icons.delete-icon color="text-rose-500"
                                                hover="text-rose-600"></x-icons.delete-icon>
                                        </x-action-button>
                                        {{-- @endcan --}}
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>

                </div>
            </div>
        </div>

        <div>
            {{ $_users->links() }}
        </div>
    </div>

    {{-- @can('manage-settings') --}}
    <x-side-modal>
        @if ($showSideMenu == 'add-user')
            <livewire:services.users.add-user wire:key="services-user-add-modal" />
        @endif

        @if ($showSideMenu == 'edit-user')
            <livewire:services.users.edit-user :userModel="$modelName" :key="'services-user-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>
    {{-- @endcan --}}

    <div class="">
        @auth
            <livewire:services.users.delete-user wire:key="services-user-delete-modal" />
        @endauth
    </div>
</div>
