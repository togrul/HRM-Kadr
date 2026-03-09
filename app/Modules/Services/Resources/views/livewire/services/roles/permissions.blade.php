<div class="flex flex-col space-y-4 z-1" x-data="{ openPermissionModal: @entangle('showPermissionModal').live }" wire:key="permissions">
    <div class="flex items-center justify-between gap-4 px-2">
        <div class="w-full max-w-sm">
            <x-livewire-input
                mode="gray"
                id="permission_search"
                name="permission_search"
                type="text"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('services::roles.actions.search_permission')"
            />
        </div>
        <div class="shrink-0">
            <x-button mode="primary" class="space-x-2" wire:click="createPermission" type="button">
                <x-icons.permission-icon color="text-white" hover="text-gray-50"></x-icons.permission-icon>
                <span>{{ __('services::roles.actions.add_permission') }}</span>
            </x-button>
        </div>
    </div>

    <div
        x-cloak
        x-show="openPermissionModal"
        class="fixed inset-0 z-50 overflow-y-auto !mt-0"
        x-on:keydown.escape.window="openPermissionModal = false; $wire.closePermissionModal()"
        style="display: none;"
    >
        <div class="flex min-h-screen items-center justify-center px-4 pb-6 pt-8 md:pt-10">
            <div class="absolute inset-0 bg-zinc-900/50" @click="openPermissionModal = false; $wire.closePermissionModal()"></div>
            <div class="relative z-10 w-full max-w-3xl rounded-3xl border border-zinc-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3">
                    <div>
                        <h3 class="text-xl font-semibold text-zinc-800">
                            {{ $permission_id ? __('services::roles.titles.edit_permission') : __('services::roles.titles.add_permission') }}
                        </h3>
                    </div>
                    <button type="button" class="rounded-xl p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700" @click="openPermissionModal = false; $wire.closePermissionModal()">
                        <x-icons.close-icon color="text-zinc-500" hover="text-zinc-700"></x-icons.close-icon>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="space-y-5 px-6 py-5">
                    @if ($errors->any())
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <x-label for="permission_name" :value="__('services::common.labels.permission')" />
                            <x-livewire-input
                                mode="gray"
                                name="permission_name"
                                id="permission_name"
                                class="mt-2 block h-11 w-full text-sm font-medium outline-none {{ $errors->has('permission_name') ? 'border-red-600' : '' }}"
                                type="text"
                                :value="old('permission_name')"
                                wire:model.defer="permission_name"
                                autofocus
                            />
                            @error('permission_name')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>

                        <div>
                            <x-label for="permission_description" :value="__('services::roles.fields.permission_description')" />
                            <textarea
                                id="permission_description"
                                name="permission_description"
                                wire:model.defer="permission_description"
                                rows="5"
                                class="mt-2 block w-full rounded-3xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 outline-none transition focus:border-zinc-400"
                            ></textarea>
                            @error('permission_description')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-zinc-100 pt-4">
                        <button
                            type="button"
                            class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                            @click="openPermissionModal = false; $wire.closePermissionModal()"
                        >
                            {{ __('services::common.actions.cancel') }}
                        </button>
                        <x-button mode="primary" class="space-x-2" type="submit">
                            <x-icons.permission-icon color="text-white" hover="text-gray-50"></x-icons.permission-icon>
                            <span>{{ __('services::common.actions.save') }}</span>
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="relative min-h-[300px] overflow-x-auto px-2">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">

                <x-table.tbl :headers="[__('services::common.labels.name'), __('services::common.labels.description'), __('services::common.labels.action'), __('services::common.labels.action')]">
                    @foreach ($permissions as $permission)
                        <tr wire:key="permission-row-{{ $permission->id }}">
                            <x-table.td>
                                @php
                                    $moduleBadge = $this->moduleBadge($permission->name);
                                    $riskBadge = $this->riskBadge($permission->name);
                                    $adminBadge = $this->adminBadge($permission->name);
                                @endphp

                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-semibold text-zinc-800">
                                        {!! $this->highlightText($permission->name) !!}
                                    </span>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-small-badge :mode="$moduleBadge['mode']">{{ $moduleBadge['label'] }}</x-small-badge>
                                        <x-small-badge :mode="$riskBadge['mode']">{{ $riskBadge['label'] }}</x-small-badge>
                                        @if ($adminBadge)
                                            <x-small-badge :mode="$adminBadge['mode']">{{ $adminBadge['label'] }}</x-small-badge>
                                        @endif
                                    </div>
                                </div>
                            </x-table.td>

                            <x-table.td>
                                <p class="max-w-3xl text-sm leading-6 text-zinc-600">
                                    {!! $this->highlightText($permission->description) !!}
                                </p>
                            </x-table.td>


                            <x-table.td>
                                {{-- @can('manage-settings')  --}}
                                <button
                                    class="flex flex-row items-center space-x-1 text-blue-500 dark:text-blue-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none"
                                    wire:click="editPermission({{ $permission->id }})">
                                    <x-icons.edit-icon color="text-blue-500" hover="text-blue-600"></x-icons.edit-icon>
                                </button>
                                {{-- @endcan --}}
                            </x-table.td>

                            <x-table.td :isButton="true">
                                {{-- @can('manage-settings') --}}
                                <button wire:click.prevent = "setDeletePermission({{ $permission->id }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-semibold text-red-500 uppercase transition duration-300 rounded-lg hover:bg-red-100">
                                    <x-icons.delete-icon color="text-rose-400"
                                        hover="text-rose-300"></x-icons.delete-icon>
                                </button>
                                {{-- @endcan --}}
                            </x-table.td>

                        </tr>
                    @endforeach
                </x-table.tbl>
            </div>
            <div class="mt-3">
                {{ $permissions->links() }}
            </div>
        </div>
    </div>

    {{-- @can('manage-settings') --}}
    <div>
        @auth
            @livewire('services.roles.delete-permission')
        @endauth
    </div>
    {{-- @endcan --}}

</div>
