<div class="flex flex-col space-y-2">
    @php
        $sectionDefaults = collect([
            ['key' => 'row_fields', 'enabled' => true, 'order' => 10],
            ['key' => 'personnel_search', 'enabled' => true, 'order' => 20],
            ['key' => 'personnel_selected', 'enabled' => true, 'order' => 30],
        ])->keyBy('key');

        $resolvedSectionBlocks = collect($templateSectionBlocks ?? [])
            ->filter(fn ($block) => is_array($block) && !empty($block['key']))
            ->map(fn ($block) => array_merge([
                'title' => null,
                'enabled' => true,
                'order' => 100,
            ], $block))
            ->keyBy('key');

        foreach ($sectionDefaults as $key => $default) {
            if (! $resolvedSectionBlocks->has($key)) {
                $resolvedSectionBlocks->put($key, $default);
                continue;
            }

            $resolvedSectionBlocks->put($key, array_merge($default, $resolvedSectionBlocks->get($key)));
        }

        $orderedSections = $resolvedSectionBlocks
            ->values()
            ->sortBy('order')
            ->values();

        $firstNonRowSection = $orderedSections
            ->first(fn ($section) => ($section['key'] ?? '') !== 'row_fields' && (bool) ($section['enabled'] ?? true));
    @endphp

    @foreach($orderedSections as $section)
        @php
            $sectionKey = (string) ($section['key'] ?? '');
            $enabled = (bool) ($section['enabled'] ?? true);
        @endphp

        @continue(! $enabled)

        @if(($firstNonRowSection['key'] ?? null) === $sectionKey)
            <hr>
        @endif

        @if($sectionKey === 'row_fields')
            @if(! empty($templateRowFieldKeys))
                @include('includes.order-templates.partials.metadata-row-fields')
            @else
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                    <div class="flex flex-col">
                        <x-label for="componentForms.{{$i}}.start_date">{{ __('orders::order_form.fields.trip_date') }}</x-label>
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
                        <x-label for="componentForms.{{$i}}.end_date">{{ __('orders::order_form.fields.return_date') }}</x-label>
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
                        <x-label for="componentForms.{{$i}}.location">{{ __('orders::order_form.fields.location') }}</x-label>
                        <x-livewire-input mode="gray" name="componentForms.{{$i}}.location" wire:model="componentForms.{{$i}}.location"></x-livewire-input>
                        @error("componentForms.{$i}.location")
                            <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>

                    @if($selectedTemplate == \App\Models\PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP)
                        <div class="flex flex-col">
                            <x-label for="componentForms.{{$i}}.meeting_hour">{{ __('orders::order_form.fields.meeting_hour') }}</x-label>
                            <x-livewire-input mode="gray" name="componentForms.{{$i}}.meeting_hour" wire:model="componentForms.{{$i}}.meeting_hour"></x-livewire-input>
                            @error("componentForms.{$i}.meeting_hour")
                                <x-validation> {{ $message }} </x-validation>
                            @enderror
                        </div>
                        <div class="flex flex-col">
                            <x-label for="componentForms.{{$i}}.return_month">{{ __('orders::order_form.fields.return_month') }}</x-label>
                            <x-livewire-input mode="gray" name="componentForms.{{$i}}.return_month" wire:model="componentForms.{{$i}}.return_month"></x-livewire-input>
                            @error("componentForms.{$i}.return_month")
                                <x-validation> {{ $message }} </x-validation>
                            @enderror
                        </div>
                        <div class="flex flex-col">
                            <x-label for="componentForms.{{$i}}.return_day">{{ __('orders::order_form.fields.return_day') }}</x-label>
                            <x-livewire-input mode="gray" type="number" name="componentForms.{{$i}}.return_day" wire:model="componentForms.{{$i}}.return_day"></x-livewire-input>
                            @error("componentForms.{$i}.return_day")
                                <x-validation> {{ $message }} </x-validation>
                            @enderror
                        </div>
                    @endif
                </div>
            @endif
            @continue
        @endif

        @if($sectionKey === 'personnel_search')
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-1 md:grid-cols-1">
                <div class="flex flex-col relative">
                    <x-label for="personnel_name">{{ __('orders::order_form.fields.search_personnel') }}</x-label>
                    <x-livewire-input x-on:click.stop="showPersonnelList = {{ $i }}" mode="gray" name="personnel_name" wire:model.live="personnel_name"></x-livewire-input>
                    <div x-show="showPersonnelList == {{ $i }}"
                         x-on:click.away = "showPersonnelList = -1"
                         class="absolute z-[99] top-[60px] left-0 w-full px-1 py-2 bg-slate-50 rounded-lg border border-slate-100 drop-shadow-sm flex flex-col max-h-40 overflow-y-auto">
                        @forelse($_personnel_list_by_name as $pl)
                            <p @if(! $pl->inActiveBusinessTrip) wire:click="addToList('{{ $pl->tabel_no }}',{{ $i }})" @endif
                               class="cursor-pointer flex flex-col transition-all duration-300 hover:bg-white px-2 py-1 rounded-md text-slate-600 drop-shadow-sm"
                            >
                                <span>{{ $pl->fullname }}</span>
                                <span class="text-sm text-rose-400 font-medium">@if($pl->inActiveBusinessTrip) ({{ __('orders::order_form.messages.in_business_trip') }}) @endif</span>
                            </p>
                        @empty
                            <span class="text-sm font-medium text-slate-500 mx-auto">{{ __('orders::order_form.messages.please_search_personnel') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
            @continue
        @endif

        @if($sectionKey === 'personnel_selected')
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-1 md:grid-cols-1">
                <div class="px-2 py-3 bg-slate-100 rounded-lg flex flex-col space-y-2">
                    @php
                        $businessRows = $selectedPersonnel->rows[$i] ?? [];
                    @endphp
                    @if(!empty($businessRows))
                        @foreach($businessRows as $keyPerson => $selectPerson)
                            <div class="w-full bg-slate-50 border border-slate-200 gap-3 px-3 py-1 rounded-lg flex items-center justify-between">
                                <p
                                   @class([
                                        'flex-none flex text-sm text-slate-800',
                                        'flex-col' => $selectedTemplate == \App\Models\PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP,
                                        'space-x-4' => $selectedTemplate == \App\Models\PersonnelBusinessTrip::FOREIGN_BUSINESS_TRIP,
                                   ])
                                >
                                    <span class="text-slate-400">{{ $selectPerson['rank'] }}</span>
                                    <span>{{ $selectPerson['fullname'] }}</span>
                                    <span class="text-teal-500">{{ $selectPerson['structure'] }}</span>
                                </p>
                                @if($selectedTemplate == \App\Models\PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP)
                                    <div class="flex flex-col">
                                        <select
                                            class="block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform text-gray-500 bg-white"
                                            wire:model.live="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.transportation"
                                            name="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.transportation"
                                        >
                                            <option value="">--{{ __('orders::order_form.transportation.placeholder') }}--</option>
                                            @foreach(\App\Enums\TransportationEnum::list() as $tKey => $tValue)
                                                <option value="{{ $tKey }}">{{ __('orders::order_form.transportation.'.$tValue) }}</option>
                                            @endforeach
                                        </select>
                                        @if((($selectPerson['transportation'] ?? null) === \App\Enums\TransportationEnum::CAR->name))
                                            <x-livewire-input
                                                mode="default"
                                                name="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.car"
                                                wire:model="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.car"
                                            ></x-livewire-input>
                                        @endif
                                    </div>

                                    <div class="flex flex-col">
                                        <x-label for="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.weapon">{{ __('orders::order_form.fields.weapon') }}</x-label>
                                        <x-livewire-input
                                            mode="default"
                                            name="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.weapon"
                                            wire:model="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.weapon"
                                        ></x-livewire-input>
                                    </div>

                                    <div class="flex flex-col">
                                        <div class="flex flex-col">
                                            <x-label for="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.bullet">{{ __('orders::order_form.fields.bullet') }}</x-label>
                                            <x-livewire-input
                                                mode="default"
                                                name="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.bullet"
                                                wire:model="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.bullet"
                                            ></x-livewire-input>
                                        </div>
                                        <x-checkbox name="hasServiceDog" model="selectedPersonnel.rows.{{$i}}.{{ $keyPerson }}.service_dog">{{ __('orders::order_form.fields.service_dog') }}</x-checkbox>
                                    </div>
                                @endif
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
        @endif
    @endforeach
</div>
