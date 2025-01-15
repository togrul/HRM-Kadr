<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-4 gap-3">
        <div class="flex flex-col">
            <x-label for="masterDegrees.degree">{{ __('Degree') }}</x-label>
            <x-livewire-input mode="default" name="masterDegrees.degree" wire:model="masterDegrees.degree"></x-livewire-input>
            @error('masterDegrees.degree')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="masterDegrees.approved_date">{{ __('Approved date') }}</x-label>
            <x-pikaday-input mode="default" name="masterDegrees.approved_date" format="Y-MM-DD" wire:model.live="masterDegrees.approved_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('masterDegrees.approved_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('masterDegrees.approved_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="masterDegrees.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="default" name="masterDegrees.given_date" format="Y-MM-DD" wire:model.live="masterDegrees.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('masterDegrees.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('masterDegrees.given_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="masterDegrees.redemption_date">{{ __('Redemption date') }}</x-label>
            <x-pikaday-input mode="default" name="masterDegrees.redemption_date" format="Y-MM-DD" wire:model.live="masterDegrees.redemption_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('masterDegrees.redemption_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
        </div>
    </div>
    <div class="flex justify-end space-x-2">
        @if($selectedRequest)
            <x-button mode="danger" wire:click="resetSelected">{{ __('Cancel') }}</x-button>
        @endif
        <x-button mode="black" wire:click="addMasterDegree">{{ __('Save') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Degree'),__('Given date'),__('Approved date'),__('Redemption date'),'action']">
                    @forelse ($personnelModelData->masterDegrees as $master)
                        <tr @class([
                            'transition-all duration-300',
                            'bg-teal-100' => $master->id === $selectedDegree
                        ])>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md shadow-sm px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $master->degree }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $master->given_date->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-teal-500">{{ $master->approved_date->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-rose-500">{{ $master->redemption_date?->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <div class="flex items-center space-x-2">
                                    <button
                                        wire:click="updateMasterDegree({{ $master->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.edit-icon')
                                    </button>
                                    <button
                                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeleteMasterDegree({{ $master->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.force-delete')
                                    </button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
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

