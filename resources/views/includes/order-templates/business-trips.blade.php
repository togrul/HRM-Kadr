<div class="flex flex-col space-y-2">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.start_date">{{ __('Trip date') }}</x-label>
            <x-pikaday-input mode="gray" name="components.{{$i}}.start_date" format="Y-MM-DD" wire:model.live="components.{{$i}}.start_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('components.{{$i}}.start_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("components.{$i}.start_date")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.end_date">{{ __('Return date') }}</x-label>
            <x-pikaday-input mode="gray" name="components.{{$i}}.end_date" format="Y-MM-DD" wire:model.live="components.{{$i}}.end_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('components.{{$i}}.end_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("components.{$i}.end_date")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.location">{{ __('Location') }}</x-label>
            <x-livewire-input mode="gray" name="components.{{$i}}.location" wire:model="components.{{$i}}.location"></x-livewire-input>
            @error("components.{$i}.location")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.meeting_hour">{{ __('Meeting hour') }}</x-label>
            <x-livewire-input mode="gray" name="components.{{$i}}.meeting_hour" wire:model="components.{{$i}}.meeting_hour"></x-livewire-input>
            @error("components.{$i}.meeting_hour")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.return_month">{{ __('Return month') }}</x-label>
            <x-livewire-input mode="gray" name="components.{{$i}}.return_month" wire:model="components.{{$i}}.return_month"></x-livewire-input>
            @error("components.{$i}.return_month")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.return_day">{{ __('Return day') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="components.{{$i}}.return_day" wire:model="components.{{$i}}.return_day"></x-livewire-input>
            @error("components.{$i}.return_day")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
    <hr>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-1 md:grid-cols-1">
        <div class="flex flex-col relative">
            <x-label for="personnel_name">{{ __('Search personnel') }}</x-label>
            <x-livewire-input @click.stop="showPersonnelList = {{ $i }}" mode="gray" name="personnel_name" wire:model.live="personnel_name"></x-livewire-input>
            <div x-show="showPersonnelList == {{ $i }}"
                 @click.away = "showPersonnelList = -1"
                 class="absolute z-[99] top-[60px] left-0 w-full px-1 py-2 bg-slate-50 rounded-lg border border-slate-100 drop-shadow-sm flex flex-col max-h-40 overflow-y-auto">
                    @forelse($_personnel_list_by_name as $pl)
                        <p @if(! $pl->inActiveBusinessTrip) wire:click="addToList('{{ $pl->tabel_no }}',{{ $i }})" @endif
                           class="cursor-pointer flex flex-col transition-all duration-300 hover:bg-white px-2 py-1 rounded-md text-slate-600 drop-shadow-sm"
                        >
                            <span>{{ $pl->fullname }}</span>
                            <span class="text-sm text-rose-400 font-medium">@if($pl->inActiveBusinessTrip) ({{ __('in business trip') }}) @endif</span>
                        </p>
                    @empty
                        <span class="text-sm font-medium text-slate-500 mx-auto">{{ __('Please search personnel') }}</span>
                    @endforelse
            </div>
        </div>

        <div class="px-2 py-3 bg-slate-100 rounded-lg flex flex-col space-y-2">
            @if(array_key_exists($i,$this->selected_personnel_list))
                @foreach($this->selected_personnel_list[$i] as $keyPerson => $selectPerson)
                    <div class="w-full bg-slate-50 border border-slate-200 gap-3 px-3 py-1 rounded-lg flex items-center justify-between">
                        <p class="flex-none flex flex-col text-sm text-slate-800">
                            <span class="text-slate-400">{{ $selectPerson['rank'] }}</span>
                            <span> {{ $selectPerson['fullname'] }} </span>
                            <span class="text-teal-500">{{ $selectPerson['structure'] }}</span>
                        </p>

                        <div class="flex flex-col">
{{--                            <x-label for="selected_personnel_list.{{$i}}.{{ $keyPerson }}.car">{{ __('Transportation') }}</x-label>--}}
                            <select
                                class="block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform text-gray-500 bg-white"
                                wire:model.live="selected_personnel_list.{{$i}}.{{ $keyPerson }}.transportation"
                                name="selected_personnel_list.{{$i}}.{{ $keyPerson }}.transportation"
                            >
                                <option value="">--{{ __('Select Transportation') }}--</option>
                                @foreach(\App\Enums\TransportationEnum::list() as $tKey => $tValue)
                                    <option value="{{ $tKey }}">{{ $tValue }}</option>
                                @endforeach
                            </select>
                            @if((isset($selected_personnel_list[$i][$keyPerson]['transportation'])
                                    && $selected_personnel_list[$i][$keyPerson]['transportation'] === \App\Enums\TransportationEnum::CAR->name))
                                <x-livewire-input
                                    mode="default"
                                    name="selected_personnel_list.{{$i}}.{{ $keyPerson }}.car"
                                    wire:model="selected_personnel_list.{{$i}}.{{ $keyPerson }}.car"
                                ></x-livewire-input>
                            @endif
                        </div>

                        <div class="flex flex-col">
                            <x-label for="selected_personnel_list.{{$i}}.{{ $keyPerson }}.weapon">{{ __('Weapon') }}</x-label>
                            <x-livewire-input
                                mode="default"
                                name="selected_personnel_list.{{$i}}.{{ $keyPerson }}.weapon"
                                wire:model="selected_personnel_list.{{$i}}.{{ $keyPerson }}.weapon"
                            ></x-livewire-input>
                        </div>

                        <div class="flex flex-col">
                            <x-checkbox name="hasServiceDog" model="selected_personnel_list.{{$i}}.{{ $keyPerson }}.service_dog">{{ __('Service dog?') }}</x-checkbox>
                        </div>

                        <button wire:click="removeFromList({{$keyPerson}},{{ $i }})"
                                class="appearance-none flex flex-none justify-center items-center w-6 h-6 rounded-lg drop-shadow-sm bg-rose-100 transition-all duration-300 hover:drop-shadow-none"
                        >
                            <svg class="w-5 h-5 text-rose-500" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
