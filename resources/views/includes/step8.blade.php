<div class="flex flex-col space-y-4">
    <div class="grid gap-2 grid-cols-1 md:grid-cols-2">
        <x-form-card title="Languages">
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col">
                    <x-select-list class="w-full" :title="__('Languages')" mode="gray" :selected="$languageName" name="languageId">
                        <x-select-list-item wire:click="setData('language','language_id','language','---',null)" :selected="'---' == $languageName"
                                            wire:model='language.language_id.id'>
                            ---
                        </x-select-list-item>
                        @foreach($languageModel as $lng)
                            <x-select-list-item wire:click="setData('language','language_id','language','{{ $lng->name }}',{{ $lng->id }})"
                                                :selected="$lng->id === $languageId" wire:model='language.language_id.id'>
                                {{ $lng->name }}
                            </x-select-list-item>
                        @endforeach
                    </x-select-list>
                    @error('language.language_id.id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col space-y-1">
                    <x-label for="language.knowledge_status">{{ __('Knowledge status') }}</x-label>
                    <div class="flex flex-row">
                        @foreach($knowledges as $key => $knw)
                            <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                                <input type="radio" class="form-radio" name="language.knowledge_status" wire:model="language.knowledge_status" value="{{ __($knw) }}">
                                <span class="ml-2 text-sm font-normal">{{ __($knw) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('language.knowledge_status')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <x-button  mode="black" wire:click="addLanguage">{{ __('Add') }}</x-button>
            </div>

            <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                        <x-table.tbl :headers="[__('Language'),__('Knowledge status'),'action']">
                            @forelse ($language_list as $key => $lng)
                                <tr>
                                    <x-table.td>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $lng['language_id']['name'] }}
                                       </span>
                                    </x-table.td>
                                    <x-table.td>
                                       <span class="text-sm font-medium text-gray-700">
                                            {{ $lng['knowledge_status'] }}
                                        </span>
                                    </x-table.td>
                                    <x-table.td :isButton="true">
                                        <button
                                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                            wire:click="forceDeleteLanguage({{ $key }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.force-delete')
                                        </button>
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
        </x-form-card>

        <x-form-card title="Scientific works and inventions">
            <x-textarea class="h-max" mode="gray" name="personnel.scientific_works_inventions" placeholder="{{__('')}}"
                        wire:model="personnel.scientific_works_inventions"></x-textarea>
        </x-form-card>
    </div>

    <x-form-card title="Participation events">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="event.event_type">{{ __('Event type') }}</x-label>
                <x-livewire-input mode="gray" name="event.event_type" wire:model="event.event_type"></x-livewire-input>
                @error('event.event_type')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="event.event_name">{{ __('Event name') }}</x-label>
                <x-livewire-input mode="gray" name="event.event_name" wire:model="event.event_name"></x-livewire-input>
                @error('event.event_name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="event.event_date">{{ __('Event date') }}</x-label>
                <x-pikaday-input mode="gray" name="event.event_date" format="Y-MM-DD" wire:model.live="event.event_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('event.event_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('event.event_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="flex justify-end">
            <x-button  mode="black" wire:click="addEvent">{{ __('Add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('Event name'),__('Event type'),__('Date'),'action']">
                        @forelse ($event_list as $key => $evnt)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $evnt['event_name'] }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                   <span class="text-sm font-medium text-gray-700">
                                         {{ $evnt['event_type'] }}
                                   </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                          {{ $evnt['event_date'] }}
                                     </span>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeleteEvent({{ $key }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.force-delete')
                                    </button>
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
    </x-form-card>

    <x-form-card title="Scientific degree and names">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Degree')" mode="gray" :selected="$degreeName" name="degreeId">
                    <x-select-list-item wire:click="setData('degree','degree_and_name_id','degree','---',null)" :selected="'---' == $degreeName"
                                        wire:model='degree.degree_and_name_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach($degrees as $edu_dg)
                        <x-select-list-item wire:click="setData('degree','degree_and_name_id','degree','{{ $edu_dg->name }}',{{ $edu_dg->id }})"
                                            :selected="$edu_dg->id === $degreeId" wire:model='degree.degree_and_name_id.id'>
                            {{ $edu_dg->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('degree.degree_and_name_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="degree.science">{{ __('Science') }}</x-label>
                <x-livewire-input mode="gray" name="degree.science" wire:model="degree.science"></x-livewire-input>
                @error('degree.science')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="degree.given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input mode="gray" name="degree.given_date" format="Y-MM-DD" wire:model.live="degree.given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('degree.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('degree.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <x-label for="degree.subject">{{ __('Subject') }}</x-label>
                <x-livewire-input mode="gray" name="degree.subject" wire:model="degree.subject"></x-livewire-input>
                @error('degree.subject')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Education document')" mode="gray" :selected="$eduDocName" name="eduDocId">
                    <x-select-list-item wire:click="setData('degree','edu_doc_type_id','eduDoc','---',null)" :selected="'---' == $eduDocName"
                                        wire:model='degree.edu_doc_type_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach($document_types as $ed)
                        <x-select-list-item
                            wire:click="setData('degree','edu_doc_type_id','eduDoc','{{ $ed->name }}',{{ $ed->id }})"
                            :selected="$ed->id === $eduDocId" wire:model='degree.edu_doc_type_id.id'
                        >
                            {{ $ed->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('degree.edu_doc_type_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="degree.diplom_serie">{{ __('Diplom serie') }}</x-label>
                <x-livewire-input mode="gray" name="degree.diplom_serie" wire:model="degree.diplom_serie"></x-livewire-input>
                @error('degree.diplom_serie')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="degree.diplom_no">{{ __('Diplom') }}#</x-label>
                <x-livewire-input mode="gray" type="number" name="degree.diplom_no" wire:model="degree.diplom_no"></x-livewire-input>
                @error('degree.diplom_no')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="degree.diplom_given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input mode="gray" name="degree.diplom_given_date" format="Y-MM-DD" wire:model.live="degree.diplom_given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('degree.diplom_given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('degree.diplom_given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="degree.document_issued_by">{{ __('Issued by') }}</x-label>
                <x-livewire-input mode="gray" name="degree.document_issued_by" wire:model="degree.document_issued_by"></x-livewire-input>
                @error('degree.document_issued_by')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <x-button  mode="black" wire:click="addDegree">{{ __('Add') }}</x-button>
        </div>

        <div class="flex flex-col space-y-2">
            @forelse ($degree_list as $key => $degreeModel)
                <div class="flex flex-col space-y-2 bg-slate-100 shadow-sm rounded-lg px-4 py-2 relative overflow-hidden">
                    <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteDegree({{ $key }})"
                        class="flex items-center justify-center absolute right-1 top-1 bg-transparent rounded-lg transition-all duration-300 p-2 hover:bg-rose-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-rose-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </button>
                    <div class="flex items-center space-x-2 border-b w-max border-slate-400 border-dashed">
                        <p class="font-medium text-teal-500">
                            {{ $degreeModel['degree_and_name_id']['name'] }}
                        </p>
                        <span>-</span>
                        <span class="font-medium text-slate-500">{{ $degreeModel['science'] }}</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 w-full">
                        <div class="flex flex-col space-y-2">
                            <div class="flex flex-col space-y-1 border-b border-gray-300">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Given date') }}</span>
                                <span class="text-sm font-medium text-slate-800"> {{ $degreeModel['given_date'] }}</span>
                            </div>
                            <div class="flex flex-col space-y-1">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Subject') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $degreeModel['subject'] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <div class="flex flex-col space-y-1 border-b border-gray-300">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Document type') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $degreeModel['edu_doc_type_id']['name'] }}</span>
                            </div>
                            <div class="flex flex-col space-y-1">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Diplom serie') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $degreeModel['diplom_serie'] }}   {{ $degreeModel['diplom_no'] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <div class="flex flex-col space-y-1 border-b border-gray-300">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Given date') }}</span>
                                <span class="text-sm font-medium text-slate-800"> {{ $degreeModel['diplom_given_date'] }}</span>
                            </div>
                            <div class="flex flex-col space-y-1">
                                <span class="font-medium text-slate-500 text-sm">{{ __('Issued by') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $degreeModel['document_issued_by'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex justify-center items-center bg-slate-100 shadow-sm rounded-lg px-4 py-2 relative">
                    <h1 class="font-medium text-base text-gray-600">
                        {{ __('No information added') }}
                    </h1>
                </div>
            @endforelse
        </div>
    </x-form-card>

    <x-form-card
        title="Electorals"
        checkbox="hasElectedElectorals"
        checkboxTitle="Has elected on electorals?"
    >
        @if($hasElectedElectorals)
            <div class="grid grid-cols-3 gap-2">
                <div class="flex flex-col">
                    <x-label for="elections.election_type">{{ __('Election type') }}</x-label>
                    <x-livewire-input mode="gray" name="elections.election_type" wire:model="elections.election_type"></x-livewire-input>
                    @error('elections.election_type')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="elections.location">{{ __('Location') }}</x-label>
                    <x-livewire-input mode="gray" name="elections.location" wire:model="elections.location"></x-livewire-input>
                    @error('elections.location')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="elections.elected_date">{{ __('Elected date') }}</x-label>
                    <x-pikaday-input mode="gray" name="elections.elected_date" format="Y-MM-DD" wire:model.live="elections.elected_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('elections.elected_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    @error('elections.elected_date')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <x-button  mode="black" wire:click="addElection">{{ __('Add') }}</x-button>
            </div>

            <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                        <x-table.tbl :headers="[__('Type'),__('Location'),__('Date'),'action']">
                            @forelse ($election_list as $key => $electionModel)
                                <tr>
                                    <x-table.td>
                                      <span class="text-sm font-medium text-gray-700">
                                          {{ $electionModel['election_type'] }}
                                      </span>
                                    </x-table.td>
                                    <x-table.td>
                                      <span class="text-sm font-medium text-gray-700">
                                          {{ $electionModel['location'] }}
                                      </span>
                                    </x-table.td>
                                    <x-table.td>
                                      <span class="text-sm font-medium text-gray-700">
                                          {{ \Carbon\Carbon::parse($electionModel['elected_date'])->format('d.m.Y') }}
                                      </span>
                                    </x-table.td>

                                    <x-table.td :isButton="true">
                                        <button
                                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                            wire:click="forceDeleteElection({{ $key }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.force-delete')
                                        </button>
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
        @endif
    </x-form-card>
</div>
