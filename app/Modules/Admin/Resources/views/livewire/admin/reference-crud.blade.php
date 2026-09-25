{{-- Shared view for every ReferenceCrudComponent: inline add/edit form above a flat table. --}}
<div class="flex flex-col">
    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-zinc-50"></x-icons.add-icon>
                <span>{{ $addLabel }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:transition class="flex border border-zinc-300 rounded-md bg-zinc-50 relative px-3 py-2 my-3">
            <button type="button" class="appearance-none absolute top-2 right-2 flex h-10 w-10 items-center justify-center rounded-lg text-ink-muted hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400" aria-label="{{ __('admin::references.actions.close') }}" title="{{ __('admin::references.actions.close') }}" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mt-4 w-full">
                @foreach ($fields as $name => $field)
                    @if (($field['type'] ?? 'text') === 'checkbox')
                        <div class="flex items-end">
                            <x-checkbox :name="'form.'.$name" :model="'form.'.$name">{{ $field['label'] }}</x-checkbox>
                        </div>
                    @else
                        <div class="flex flex-col">
                            <x-label :for="'form.'.$name">{{ $field['label'] }}</x-label>
                            <x-livewire-input mode="default" :type="$field['type'] ?? 'text'" :name="'form.'.$name" wire:model="form.{{ $name }}"></x-livewire-input>
                            @error('form.'.$name)
                                <x-validation> {{ $message }} </x-validation>
                            @enderror
                        </div>
                    @endif
                @endforeach
                <div class="flex items-end">
                    <x-modal-button mode="black">{{ $saveLabel }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[...array_column($columns, 'label'), $actionsLabel]">
                        @forelse ($items as $item)
                            <tr wire:key="reference-row-{{ $item->getKey() }}">
                                @foreach ($columns as $column)
                                    <x-table.td>
                                        @if (isset($column['lines']))
                                            <div class="flex flex-col space-y-1">
                                                @foreach ($column['lines'] as $prefix => $attr)
                                                    @if (filled($item->{$attr}))
                                                        <span class="text-sm font-medium"><b>{{ $prefix }}</b> - {{ $item->{$attr} }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @elseif (isset($column['check']))
                                            <x-icons.check-icon :color="$item->{$column['check']} ? 'text-emerald-500' : 'text-zinc-500'"></x-icons.check-icon>
                                        @else
                                            <span class="{{ $column['class'] ?? 'text-sm text-zinc-500 font-medium' }}">
                                                {{ $item->{$column['attr']} }} {{ $column['unit'] ?? '' }}
                                            </span>
                                        @endif
                                    </x-table.td>
                                @endforeach

                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <button type="button"
                                            aria-label="{{ __('admin::references.actions.edit') }}"
                                            title="{{ __('admin::references.actions.edit') }}"
                                            wire:click.prevent="openCrud({{ $item->getKey() }})"
                                            class="appearance-none flex items-center justify-center w-10 h-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 text-xs font-medium uppercase rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700"
                                        >
                                            <x-icons.edit-icon color="text-zinc-400" hover="text-zinc-500"></x-icons.edit-icon>
                                        </button>
                                        <button type="button"
                                            aria-label="{{ __('admin::references.actions.delete') }}"
                                            title="{{ __('admin::references.actions.delete') }}"
                                            wire:click.prevent="deleteModel({{ $item->getKey() }})"
                                            class="flex items-center justify-center w-10 h-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 text-xs font-medium uppercase transition duration-300 rounded-lg text-zinc-500 hover:bg-red-100 hover:text-zinc-700"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}"></td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </div>
</div>
