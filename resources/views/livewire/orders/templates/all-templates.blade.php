<div class="flex flex-col space-y-8"
     x-data
>
    <div class="flex items-center justify-between space-x-2 action-section py-2">
        <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
            <x-filter.nav>
                <x-filter.item  wire:click.prevent="setStatus('active')" :active="$status === 'active'">
                    {{ __('Active') }}
                </x-filter.item>
                {{-- @can('manage-orders') --}}
                <x-filter.item  wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                    {{ __('Deleted') }}
                </x-filter.item>
                {{-- @endcan --}}
            </x-filter.nav>
        </div>
        {{-- @can('manage-templates') --}}
        <x-button mode="primary" wire:click="openSideMenu('add-template')" class="space-x-2">
            <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
            <span>{{ __('Add template') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-4">
            @forelse ($templates as $template)
                <div class="flex flex-col space-y-2 bg-slate-50 border border-slate-100 shadow-sm rounded-lg w-full px-3 py-4">
                    <div class="flex flex-col space-y-2 items-center justify-center">
                        <h1 class="font-medium text-sm text-center bg-blue-100 rounded-xl px-3 py-1 flex justify-center items-center text-blue-500">
                            {{ $template->category->{"name_".config('app.locale')} }}
                        </h1>
                        <h2 class="font-medium text-base text-slate-900">
                            {{ $template->name }}
                        </h2>
                    </div>
                    <div class="flex justify-between items-center border-t border-dashed border-slate-300 py-2">
                        @if($status != 'deleted')
                        {{--@can('manage-templates')--}}
                            <a href="javascript:void(0)" wire:click.prevent="openSideMenu('edit-template',{{ $template->id }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-200 hover:text-gray-700">
                                <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                            </a>
                            <button
                                wire:click.prevent = "openSideMenu('set-type',{{ $template->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-emerald-100 hover:text-gray-700"
                            >
                                <x-icons.components-icon color="text-emerald-500" hover="text-emerald-600"></x-icons.components-icon>
                            </button>
                            <button
                                wire:click.prevent = "setDeleteTemplate({{ $template->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                            </button>
                        {{--@endcan--}}
                        @else
                            <button
                                wire:click="restoreData({{ $template->id  }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                            >
                                <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                            </button>
                            <button
                                wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                wire:click="forceDeleteData({{ $template->id  }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <x-icons.force-delete color="text-rose-400" hover="text-rose-500"></x-icons.force-delete>
                            </button>
                        @endif
                    </div>

                </div>
            @empty
                <div class="flex flex-col space-y-2 justify-center items-center py-8 sm:col-span-2 md:col-span-3 lg:col-span-4 bg-slate-50">
                    <svg class="w-12 h-12 text-slate-400 drop-shadow-lg" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"></path>
                    </svg>
                    <span class="font-medium drop-shadow-lg">{{ __('No information added') }}</span>
                </div>
            @endforelse

        </div>
        <div>
            {{ $templates->links() }}
        </div>
    </div>


    {{-- @can('manage-templates') --}}
    <div>
        <x-side-modal>
                @if($showSideMenu == 'add-template')
                    <livewire:orders.templates.add-template />
                @endif

                @if($showSideMenu == 'edit-template')
                    <livewire:orders.templates.edit-template :templateModel="$modelName" />
                @endif

                @if($showSideMenu == 'set-type')
                    <livewire:orders.templates.set-type :templateModel="$modelName" />
                @endif
        </x-side-modal>
    </div>
    {{-- @endcan --}}

    <div class="">
        @auth
            @livewire('orders.templates.delete-template')
        @endauth
    </div>
</div>
