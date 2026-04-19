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
            @foreach(config('app.locales') as $localeName)
                <x-filter.item  wire:click.prevent="setLocale('{{$localeName}}')" :active="$selectedLocale === $localeName">
                    <span class="uppercase">{{ $localeName }}</span>
                </x-filter.item>
            @endforeach
        </x-filter.nav>

        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('admin::references.buttons.add_country') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:transition class="relative my-3 flex rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm">
            <x-action-button class="absolute right-2 top-2 h-9 w-9 hover:bg-zinc-100" wire:click="closeCrud()" :title="__('admin::references.actions.close')">
                <x-icons.close-icon></x-icons.close-icon>
            </x-action-button>
            <div class="mt-6 grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                <div class="flex flex-col">
                    <x-label for="form.id">{{ __('admin::references.fields.id') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.id" wire:model="form.id"></x-livewire-input>
                </div>
                <div class="flex flex-col">
                    <x-label for="form.code">{{ __('admin::references.fields.code') }}</x-label>
                    <x-livewire-input mode="default"  name="form.code" wire:model="form.code"></x-livewire-input>
                    @error('form.code')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.name">{{ __('admin::references.fields.name') }}</x-label>
                    <x-livewire-input mode="default" name="form.country_translations.title" wire:model="form.country_translations.title"></x-livewire-input>
                    @error('form.country_translations.title')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex items-end">
                    <x-modal-button mode="black">{{ __('admin::references.actions.save') }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('admin::references.fields.id'),__('admin::references.fields.locale'),__('admin::references.fields.code'),__('admin::references.fields.name'),__('admin::references.table.action')]">
                        @forelse ($countries as $country)
                            <tr wire:key="country-row-{{ $country->id }}">
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $country->id }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $country->locale_code }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $country->code }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-medium">
                                        {{ $country->locale_title }}
                                    </span>
                                </x-table.td>

                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <x-action-button
                                            wire:click.prevent="openCrud({{ $country->id }})"
                                            class="h-9 w-9 hover:bg-zinc-100"
                                            :title="__('admin::references.actions.edit')"
                                        >
                                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                        </x-action-button>
                                        <x-action-button
                                            wire:click.prevent = "deleteModel({{ $country->id }})"
                                            class="h-9 w-9 hover:bg-red-100"
                                            :title="__('admin::references.actions.delete')"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </x-action-button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
                <div>
                    {{ $countries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
