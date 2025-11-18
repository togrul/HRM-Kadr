<div class="flex flex-col space-y-2">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
        <div class="flex flex-col">
            <x-label for="componentForms.{{$i}}.start_date">{{ __('Start date') }}</x-label>
            <x-pikaday-input mode="gray" name="componentForms.{{$i}}.start_date" format="Y-MM-DD" wire:model.live="componentForms.{{$i}}.start_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('componentForms.{{$i}}.start_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("componentForms.{$i}.start_date")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="componentForms.{{$i}}.end_date">{{ __('End date') }}</x-label>
            <x-pikaday-input mode="gray" name="componentForms.{{$i}}.end_date" format="Y-MM-DD" wire:model.live="componentForms.{{$i}}.end_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('componentForms.{{$i}}.end_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("componentForms.{$i}.end_date")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="componentForms.{{$i}}.days">{{ __('Days') }}</x-label>
            <x-livewire-input mode="gray" name="componentForms.{{$i}}.days" wire:model="componentForms.{{$i}}.days"></x-livewire-input>
            @error("componentForms.{$i}.days")
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

        <div class="px-2 py-3 bg-slate-100 rounded-lg flex flex-col space-y-2">
            @php($vacationRows = $selectedPersonnel->rows[$i] ?? [])
            @if(!empty($vacationRows))
                @foreach($vacationRows as $keyPerson => $selectPerson)
                    <div class="w-full bg-slate-50 border border-slate-200 gap-3 px-3 py-1 rounded-lg flex items-center justify-between">
                        <p class="flex-none flex flex-col text-sm text-slate-800">
                            <span class="text-slate-400">{{ $selectPerson['rank'] }}</span>
                            <span>{{ $selectPerson['fullname'] }}</span>
                            <span class="text-teal-500">{{ $selectPerson['structure'] }}</span>
                            @php
                                [$year, $month] = [intdiv($selectPerson['work_duration'], 12), $selectPerson['work_duration'] % 12];
                                $duration = $selectPerson['work_duration'] > 11 ? "{$year} il {$month} ay" : "{$month} ay";
                            @endphp
                            <span @class([
                                        'text-sm',
                                        'text-rose-500' => $selectPerson['work_duration'] < 6,
                                        'text-slate-900' => $selectPerson['work_duration'] >= 6
                            ])>{{ __('Seniority') }}: {{ $duration }}</span>
                        </p>
                        <div class="flex flex-col w-full">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <x-label>{{ __('Reserved month') }}: </x-label>
                                    <span class="text-sm text-sky-500">{{ $selectPerson['reserved_date_month'] }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @php
                                        $percentage = ($selectPerson['vacation_days_remaining'] * 100) / $selectPerson['vacation_days_total'];
                                        $color = match (true) {
                                             $percentage < 30 => 'rose',
                                             $percentage < 60 => 'blue',
                                             default => 'teal',
                                         };
                                    @endphp
                                    <span class="text-sm text-gray-600 flex-shrink-0">{{ __('Vacation days') }}: </span>
                                    <div class="rounded-lg h-3 bg-slate-200 relative w-28 overflow-hidden flex justify-center items-center">
                                        <div class="absolute left-0 h-full bg-{{ $color }}-500 shadow-sm" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-sm z-10 text-slate-600">{{ $selectPerson['vacation_days_remaining'] }}/{{ $selectPerson['vacation_days_total'] }}</span>
                                </div>
                            </div>

                            <x-livewire-input mode="default" name="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.location" wire:model="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.location"></x-livewire-input>
                        </div>
                        <button wire:click="removeFromList({{$keyPerson}},{{ $i }})"
                                class="appearance-none flex flex-none justify-center items-center w-6 h-6 rounded-lg drop-shadow-sm transition-all duration-300 hover:drop-shadow-none"
                        >
                            <x-icons.backspace-icon></x-icons.backspace-icon>
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
