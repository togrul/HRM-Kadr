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
            <svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
            </svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                            <button
                                wire:click.prevent = "openSideMenu('set-type',{{ $template->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-emerald-100 hover:text-gray-700"
                            >
                                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6 text-emerald-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z"></path>
                                </svg>
                            </button>
                            <button
                                wire:click.prevent = "setDeleteTemplate({{ $template->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        {{--@endcan--}}
                        @else
                            <button
                                wire:click="restoreData({{ $template->id  }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-teal-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                                </svg>
                            </button>
                            <button
                                wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                wire:click="forceDeleteData({{ $template->id  }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
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
