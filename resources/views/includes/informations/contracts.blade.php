<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('personnel::information.fields.rank')"
                placeholder="---"
                mode="default"
                class="w-full"
                wire:model.live="contracts.rank_id"
                :model="$this->contractRankOptions"
            />
            @error('contracts.rank_id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_date">{{ __('personnel::information.fields.contract_date') }}</x-label>
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
            <x-label for="contracts.contract_refresh_date">{{ __('personnel::information.fields.contract_refresh_date') }}</x-label>
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
            <x-label for="contracts.contract_duration">{{ __('personnel::information.fields.contract_duration') }}</x-label>
            <x-livewire-input mode="default" type="number" name="contracts.contract_duration" wire:model="contracts.contract_duration"></x-livewire-input>
            @error('contracts.contract_duration')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="contracts.contract_ends_at">{{ __('personnel::information.fields.contract_end_date') }}</x-label>
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
        <x-button  mode="black" wire:click="addContract">{{ __('personnel::common.actions.add') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-visible">
                <x-table.tbl :headers="[__('personnel::information.fields.rank'),__('personnel::common.labels.duration'),__('personnel::information.fields.contract_date'),__('personnel::common.labels.action')]">
                    @forelse ($personnelModelData->contracts as $dataContract)
                        <tr>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $dataContract->rank->name }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $dataContract->contract_duration }} {{ __('personnel::common.labels.month') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex flex-col">
                                    <div class="flex space-x-1 items-center">
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ __('personnel::information.fields.contract_date') }}:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $dataContract->contract_date->format('d.m.Y') }}
                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium">{{ __('personnel::information.fields.contract_refresh_date') }}:</span>
                                        <span class="text-sm font-medium text-teal-500">
                                            {{ $dataContract->contract_refresh_date->format('d.m.Y') }}
                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium">{{ __('personnel::information.fields.contract_end_date') }}:</span>
                                        <span class="text-sm font-medium text-rose-500">
                                            {{ $dataContract->contract_ends_at->format('d.m.Y') }}
                                       </span>
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <button
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteContract({{ $dataContract->id }}) })"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                >
                                    <x-icons.force-delete></x-icons.force-delete>
                                </button>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex justify-center items-center py-4">
                                    <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

</div>
