<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <x-label for="disposals.disposal_date">{{ __('personnel::information.fields.disposal_date') }}</x-label>
            <x-pikaday-input mode="default" name="disposals.disposal_date" format="Y-MM-DD" wire:model.live="disposals.disposal_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('disposals.disposal_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('disposals.disposal_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="disposals.disposal_end_date">{{ __('personnel::information.fields.disposal_end_date') }}</x-label>
            <x-pikaday-input mode="default" name="disposals.disposal_end_date" format="Y-MM-DD" wire:model.live="disposals.disposal_end_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('disposals.disposal_end_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
        </div>
        <div class="flex flex-col">
            <x-label for="disposals.disposal_reason">{{ __('personnel::information.fields.disposal_reason') }}</x-label>
            <x-textarea mode="default" name="disposals.disposal_reason" placeholder=""
                        wire:model="disposals.disposal_reason"></x-textarea>
        </div>
    </div>
    <div class="flex justify-end space-x-2">
        @if($selectedDisposal)
            <x-button mode="danger" wire:click="resetSelected">{{ __('personnel::common.actions.cancel') }}</x-button>
        @endif
        <x-button mode="black" wire:click="addDisposal">{{ __('personnel::common.actions.save') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-visible">
                <x-table.tbl :headers="[__('personnel::information.fields.disposal_date'),__('personnel::information.fields.disposal_end_date'),__('personnel::information.fields.disposal_reason'),__('personnel::common.labels.action')]">
                    @forelse ($personnelModelData->disposals as $disposal)
                        <tr>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $disposal->disposal_date->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $disposal->disposal_end_date?->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $disposal->disposal_reason }}</span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <div class="flex items-center space-x-2">
                                    <button
                                        wire:click="updateDisposal({{ $disposal->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                    >
                                        <x-icons.edit-icon></x-icons.edit-icon>
                                    </button>
                                    <button
                                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeleteDisposal({{ $disposal->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </div>
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
