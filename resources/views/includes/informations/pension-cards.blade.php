<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <x-label for="pensionCards.card_no">{{ __('Card number') }}</x-label>
            <x-livewire-input mode="default" name="pensionCards.card_no" wire:model="pensionCards.card_no"></x-livewire-input>
            @error('pensionCards.card_no')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="pensionCards.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="default" name="pensionCards.given_date" format="Y-MM-DD" wire:model.live="pensionCards.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('pensionCards.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('pensionCards.given_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="pensionCards.expiry_date">{{ __('Expiry date') }}</x-label>
            <x-pikaday-input mode="default" name="pensionCards.expiry_date" format="Y-MM-DD" wire:model.live="pensionCards.expiry_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('pensionCards.expiry_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('pensionCards.expiry_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
    <div class="flex justify-end space-x-2">
        <x-button mode="black" wire:click="addPensionCard">{{ __('Save') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-visible">
                <x-table.tbl :headers="[__('Card number'),__('Given date'),__('Expiry date'),'action']">
                    @forelse ($personnelModelData->pensionCards as $pension)
                        @php
                            $activeCard = (\Carbon\Carbon::parse($pension->given_date) <= \Carbon\Carbon::now())
                                    && (\Carbon\Carbon::parse($pension->expiry_date) >= \Carbon\Carbon::now());
                        @endphp
                        <tr>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md shadow-sm px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $pension->card_no }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $pension->given_date->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium flex items-center text-teal-500">{{ $pension->expiry_date->format('d.m.Y') }}</span>
                                    <div @class([
                                        'flex justify-center items-center w-4 h-4 rounded-full',
                                        'bg-green-200' => $activeCard,
                                        'bg-rose-200' => ! $activeCard
                                    ])>
                                        <span @class([
                                            'flex justify-center items-center w-2 h-2 rounded-full shadow-sm transition duration-300',
                                            'bg-green-500' => $activeCard,
                                            'bg-rose-500' => ! $activeCard
                                        ])></span>
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <div class="flex items-center space-x-2">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeletePensionCard({{ $pension->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.force-delete')
                                    </button>
                                </div>
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

