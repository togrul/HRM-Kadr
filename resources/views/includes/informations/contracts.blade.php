<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            @php
                $selectedName = array_key_exists('rank_id',$contracts) ? $contracts['rank_id']['name'] : '---';
                $selectedId = array_key_exists('rank_id',$contracts) ? $contracts['rank_id']['id'] : -1;
            @endphp
            <x-select-list class="w-full" :title="__('Rank')" mode="default" :selected="$selectedName" name="rankId">
                <x-select-list-item wire:click="setData('contracts','rank_id',null,'---',null)" :selected="'---' ==  $selectedName"
                                    wire:model='contracts.rank_id.id'>
                    ---
                </x-select-list-item>
                @foreach($this->ranks->toArray() as $key => $rank)
                    <x-select-list-item wire:click="setData('contracts','rank_id',null,'{{ $rank }}',{{ $key }})"
                                        :selected="$key === $selectedId" wire:model='contracts.rank_id.id'>
                        {{ $rank }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('contracts.rank_id.id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_date">{{ __('Contract date') }}</x-label>
            <x-pikaday-input mode="default" name="contracts.contract_date" format="Y-MM-DD" wire:model.live="contracts.contract_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('contracts.contract_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('contracts.contract_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_refresh_date">{{ __('Contract refresh date') }}</x-label>
            <x-pikaday-input mode="default" name="contracts.contract_refresh_date" format="Y-MM-DD" wire:model.live="contracts.contract_refresh_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('contracts.contract_refresh_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('contracts.contract_refresh_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_duration">{{ __('Contract duration') }}</x-label>
            <x-livewire-input mode="default" type="number" name="contracts.contract_duration" wire:model="contracts.contract_duration"></x-livewire-input>
            @error('contracts.contract_duration')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_ends_at">{{ __('Contract end date') }}</x-label>
            <x-pikaday-input mode="default" name="contracts.contract_ends_at" format="Y-MM-DD" wire:model.live="contracts.contract_ends_at">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('contracts.contract_ends_at', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('contracts.contract_ends_at')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
    <div class="flex justify-end">
        <x-button  mode="black" wire:click="addContract">{{ __('Add') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Rank'),__('Duration'),__('Contract date'),'action']">
                    @forelse ($personnelModelData->contracts as $dataContract)
                        <tr>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $dataContract->rank->name }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $dataContract->contract_duration }} {{ __('month') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex flex-col">
                                    <div class="flex space-x-1 items-center">
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ __('Contract date') }}:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $dataContract->contract_date->format('d.m.Y') }}
                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium">{{ __('Contract refresh date') }}:</span>
                                        <span class="text-sm font-medium text-teal-500">
                                            {{ $dataContract->contract_refresh_date->format('d.m.Y') }}
                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium">{{ __('Contract end date') }}:</span>
                                        <span class="text-sm font-medium text-rose-500">
                                            {{ $dataContract->contract_ends_at->format('d.m.Y') }}
                                       </span>
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <button
                                    onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                    wire:click="forceDeleteContract({{ $dataContract->id }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                >
                                    @include('components.icons.force-delete')
                                </button>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex justify-center items-center py-4">
                                    <span class="font-medium">{{ __('No information added') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

</div>

