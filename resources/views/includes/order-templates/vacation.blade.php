<div class="flex flex-col space-y-2">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
        <div class="flex flex-col">
            <x-label for="components.{{$i}}.start_date">{{ __('Start date') }}</x-label>
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
            <x-label for="components.{{$i}}.end_date">{{ __('End date') }}</x-label>
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
            <x-label for="components.{{$i}}.days">{{ __('Days') }}</x-label>
            <x-livewire-input mode="gray" name="components.{{$i}}.days" wire:model="components.{{$i}}.days"></x-livewire-input>
            @error("components.{$i}.days")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
    <hr>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
        <div class="flex flex-col relative">
            <x-label for="personnel_name">{{ __('Search personnel') }}</x-label>
            <x-livewire-input @click.stop="showPersonnelList = {{ $i }}" mode="gray" name="personnel_name" wire:model.live="personnel_name"></x-livewire-input>
            <div x-show="showPersonnelList == {{ $i }}"
                 @click.away = "showPersonnelList = -1"
                 class="absolute z-[99] top-[60px] left-0 w-full px-1 py-2 bg-slate-50 rounded-lg border border-slate-100 drop-shadow-sm flex flex-col max-h-40 overflow-y-auto">
                    @forelse($_personnel_list_by_name as $pl)
                        <p @if(!$pl->inActiveVacation) wire:click="addToList('{{ $pl->tabel_no }}',{{ $i }})" @endif
                           class="cursor-pointer flex flex-col transition-all duration-300 hover:bg-white px-2 py-1 rounded-md text-slate-600 drop-shadow-sm"
                        >
                            <span>{{ $pl->fullname }}</span>
                            <span class="text-sm text-rose-400 font-medium">@if($pl->inActiveVacation) ({{ __('in vacation') }}) @endif</span>
                        </p>
                    @empty
                        <span class="text-sm font-medium text-slate-500 mx-auto">{{ __('Please search personnel') }}</span>
                    @endforelse
            </div>
        </div>

        <div class="md:col-span-2 px-2 py-3 bg-slate-100 rounded-lg flex flex-col space-y-2">
            @if(array_key_exists($i,$this->selected_personnel_list))
                @foreach($this->selected_personnel_list[$i] as $keyPerson => $selectPerson)
                    <div class="w-full bg-slate-50 border border-slate-200 gap-3 px-3 py-1 rounded-lg flex items-center justify-between">
                        <p class="flex-none flex flex-col text-sm text-slate-800">
                            <span class="text-slate-400">{{ $selectPerson['rank'] }}</span>
                            <span> {{ $selectPerson['fullname'] }} </span>
                            <span class="text-teal-500">{{ $selectPerson['structure'] }}</span>
                        </p>
                        <x-livewire-input mode="default" name="selected_personnel_list.{{$i}}.{{ $keyPerson }}.location" wire:model="selected_personnel_list.{{$i}}.{{ $keyPerson }}.location"></x-livewire-input>
                        <button wire:click="removeFromList({{$keyPerson}},{{ $i }})"
                                class="appearance-none flex flex-none justify-center items-center w-6 h-6 rounded-lg drop-shadow-sm transition-all duration-300 hover:drop-shadow-none"
                        >
                           @include('components.icons.backspace-icon')
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
