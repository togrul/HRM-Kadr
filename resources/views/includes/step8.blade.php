<div class="flex flex-col space-y-4">
    <div class="grid items-stretch grid-cols-1 gap-2">
        <x-form-card title="Languages">
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col">
                    <x-ui.select-dropdown
                        label="{{ __('Languages') }}"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model.live="miscForm.language.language_id"
                        :model="$this->languageOptions"
                    >
                        <x-livewire-input
                            mode="gray"
                            name="searchLanguage"
                            wire:model.live.debounce.300ms="searchLanguage"
                            @click.stop="isOpen = true"
                            x-on:input.stop="null"
                            x-on:keyup.stop="null"
                            x-on:keydown.stop="null"
                            x-on:change.stop="null"
                        />
                    </x-ui.select-dropdown>
                    @error('miscForm.language.language_id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col space-y-1">
                    <x-label for="miscForm.language.knowledge_status">{{ __('Knowledge status') }}</x-label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($knowledges as $knw)
                            <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                                <input type="radio"
                                       class="form-radio"
                                       name="miscForm.language.knowledge_status"
                                       wire:model="miscForm.language.knowledge_status"
                                       value="{{ __($knw) }}">
                                <span class="ml-2 text-sm font-normal">{{ __($knw) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('miscForm.language.knowledge_status')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <x-button mode="black" wire:click="addLanguage">{{ __('Add') }}</x-button>
            </div>

            <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                        <x-table.tbl :headers="[__('Language'),__('Knowledge status'),'action']">
                            @forelse ($miscForm->languageList ?? [] as $key => $language)
                                <tr>
                                    <x-table.td>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ data_get($language, 'language_label') ?? '---' }}
                                        </span>
                                    </x-table.td>
                                    <x-table.td>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ data_get($language, 'knowledge_status') ?? '---' }}
                                        </span>
                                    </x-table.td>
                                    <x-table.td :isButton="true">
                                        <button
                                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                            wire:click="forceDeleteLanguage({{ $key }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.force-delete')
                                        </button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="flex items-center justify-center py-4">
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

        <x-form-card title="Events">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                <div class="flex flex-col">
                    <x-label for="miscForm.event.event_type">{{ __('Event type') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.event.event_type" wire:model="miscForm.event.event_type"></x-livewire-input>
                    @error('miscForm.event.event_type')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="miscForm.event.event_name">{{ __('Event name') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.event.event_name" wire:model="miscForm.event.event_name"></x-livewire-input>
                    @error('miscForm.event.event_name')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="miscForm.event.event_date">{{ __('Event date') }}</x-label>
                    <x-pikaday-input mode="gray" name="miscForm.event.event_date" format="Y-MM-DD" wire:model.live="miscForm.event.event_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('miscForm.event.event_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    @error('miscForm.event.event_date')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="flex items-end justify-end">
                <x-button mode="black" wire:click="addEvent">{{ __('Add') }}</x-button>
            </div>

            <div class="flex flex-col space-y-2">
                @forelse ($miscForm->eventList ?? [] as $key => $event)
                  <div class="flex items-center gap-2">
                    <span class="flex-none text-neutral-600">{{ $loop->iteration }}.</span>
                    <div class="grid w-full grid-cols-3 gap-4 text-sm text-slate-800">
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Event type') }}</span>
                            <span>{{ data_get($event, 'event_type') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Event name') }}</span>
                            <span>{{ data_get($event, 'event_name') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Event date') }}</span>
                            <span>{{ data_get($event, 'event_date') ?? '---' }}</span>
                        </div>
                    </div>
                    <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteEvent({{ $key }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                        >
                            @include('components.icons.force-delete')
                    </button>
                  </div>
                @empty
                    <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                        <span class="text-base font-medium text-gray-600">{{ __('No information added') }}</span>
                    </div>
                @endforelse
            </div>
        </x-form-card>
    </div>

    <div class="grid items-stretch grid-cols-1">
        <x-form-card title="Scientific degrees">
            <div class="grid grid-cols-3 gap-2 md:grid-cols-9">
                <div class="flex flex-col md:col-span-2">
                    <x-ui.select-dropdown
                        label="{{ __('Scientific degrees') }}"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model.live="miscForm.degree.degree_and_name_id"
                        :model="$this->scientificDegreeOptions"
                    >
                        <x-livewire-input
                            mode="gray"
                            name="searchDegree"
                            wire:model.live.debounce.300ms="searchDegree"
                            @click.stop="isOpen = true"
                            x-on:input.stop="null"
                            x-on:keyup.stop="null"
                            x-on:keydown.stop="null"
                            x-on:change.stop="null"
                        />
                    </x-ui.select-dropdown>
                    @error('miscForm.degree.degree_and_name_id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col md:col-span-3">
                    <x-label for="miscForm.degree.science">{{ __('Science') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.science" wire:model="miscForm.degree.science"></x-livewire-input>
                    @error('miscForm.degree.science')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                 <div class="flex flex-col md:col-span-3">
                    <x-label for="miscForm.degree.subject">{{ __('Subject') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.subject" wire:model="miscForm.degree.subject"></x-livewire-input>
                    @error('miscForm.degree.subject')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                 <div class="flex flex-col">
                    <x-label for="miscForm.degree.given_date">{{ __('Given date') }}</x-label>
                    <x-pikaday-input mode="gray" name="miscForm.degree.given_date" format="Y-MM-DD" wire:model.live="miscForm.degree.given_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('miscForm.degree.given_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    @error('miscForm.degree.given_date')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="grid items-start grid-cols-3 gap-2 md:grid-cols-9">   
                <div class="flex flex-col md:col-span-2">
                    <x-ui.select-dropdown
                        label="{{ __('Education document') }}"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model.live="miscForm.degree.edu_doc_type_id"
                        :model="$this->step8DocumentTypeOptions"
                    >
                        <x-livewire-input
                            mode="gray"
                            name="searchDegreeDocumentType"
                            wire:model.live.debounce.300ms="searchDegreeDocumentType"
                            @click.stop="isOpen = true"
                            x-on:input.stop="null"
                            x-on:keyup.stop="null"
                            x-on:keydown.stop="null"
                            x-on:change.stop="null"
                        />
                    </x-ui.select-dropdown>
                    @error('miscForm.degree.edu_doc_type_id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                
                <div class="flex items-start gap-2 md:col-span-3">
                    <div class="flex flex-col w-[100px] flex-none">
                        <x-label for="miscForm.degree.diplom_serie">{{ __('Diplom serie') }}</x-label>
                        <x-livewire-input mode="gray" name="miscForm.degree.diplom_serie" wire:model="miscForm.degree.diplom_serie"></x-livewire-input>
                        @error('miscForm.degree.diplom_serie')
                          <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                    <div class="flex flex-col w-full">
                      <x-label for="miscForm.degree.diplom_no">{{ __('Diplom') }} #</x-label>
                      <x-livewire-input mode="gray" type="number" name="miscForm.degree.diplom_no" wire:model="miscForm.degree.diplom_no"></x-livewire-input>
                      @error('miscForm.degree.diplom_no')
                      <x-validation> {{ $message }} </x-validation>
                      @enderror
                  </div>
                </div>
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.diplom_given_date">{{ __('Given date') }}</x-label>
                    <x-pikaday-input mode="gray" name="miscForm.degree.diplom_given_date" format="Y-MM-DD" wire:model.live="miscForm.degree.diplom_given_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('miscForm.degree.diplom_given_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    @error('miscForm.degree.diplom_given_date')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                 <div class="flex flex-col md:col-span-3">
                    <x-label for="miscForm.degree.document_issued_by">{{ __('Issued by') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.document_issued_by" wire:model="miscForm.degree.document_issued_by"></x-livewire-input>
                    @error('miscForm.degree.document_issued_by')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="flex items-end justify-end">
                <x-button mode="black" wire:click="addDegree">{{ __('Add') }}</x-button>
            </div>

            <div class="flex flex-col space-y-2">
                @forelse ($miscForm->degreeList ?? [] as $key => $degree)
                    <div class="relative flex flex-col px-4 py-2 space-y-2 overflow-hidden rounded-lg shadow-sm bg-neutral-100">
                        <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteDegree({{ $key }})"
                            class="absolute flex items-center justify-center p-2 transition-all duration-300 bg-transparent rounded-lg right-1 top-1 hover:bg-rose-100"
                        >
                            @include('components.icons.force-delete')
                        </button>
                        <div class="flex items-center space-x-2 border-b border-dashed w-max border-slate-400">
                            <p class="font-medium text-teal-500">
                                {{ data_get($degree, 'degree_label') ?? '---' }}
                            </p>
                            <span>-</span>
                            <span class="font-medium text-slate-500">{{ data_get($degree, 'science') ?? '---' }}</span>
                        </div>
                        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Given date') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'given_date') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Subject') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'subject') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Document type') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'edu_doc_label') ?? '---' }}</span>
                            </div>
                        </div>
                        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Diplom serie') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_serie') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Diplom') }} #</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_no') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-sm text-slate-500">{{ __('Diplom given date') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_given_date') ?? '---' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col pt-2 space-y-1 border-t border-gray-300">
                            <span class="text-sm text-slate-500">{{ __('Document issued by') }}</span>
                            <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'document_issued_by') ?? '---' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                        <span class="text-base font-medium text-gray-600">{{ __('No information added') }}</span>
                    </div>
                @endforelse
            </div>
        </x-form-card>

        <x-form-card
            title="Electorals"
            checkbox="miscForm.hasElectedElectorals"
            checkboxTitle="Has elected on electorals?"
        >
            @if($miscForm->hasElectedElectorals)
                <div class="grid grid-cols-3 gap-2">
                    <div class="flex flex-col">
                        <x-label for="miscForm.election.election_type">{{ __('Election type') }}</x-label>
                        <x-livewire-input mode="gray" name="miscForm.election.election_type" wire:model="miscForm.election.election_type"></x-livewire-input>
                        @error('miscForm.election.election_type')
                        <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="miscForm.election.location">{{ __('Location') }}</x-label>
                        <x-livewire-input mode="gray" name="miscForm.election.location" wire:model="miscForm.election.location"></x-livewire-input>
                        @error('miscForm.election.location')
                        <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="miscForm.election.elected_date">{{ __('Elected date') }}</x-label>
                        <x-pikaday-input mode="gray" name="miscForm.election.elected_date" format="Y-MM-DD" wire:model.live="miscForm.election.elected_date">
                            <x-slot name="script">
                                $el.onchange = function () {
                                @this.set('miscForm.election.elected_date', $el.value);
                                }
                            </x-slot>
                        </x-pikaday-input>
                        @error('miscForm.election.elected_date')
                        <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                </div>
               <div class="flex items-end justify-end">
                  <x-button mode="black" wire:click="addElection">{{ __('Add') }}</x-button>
               </div>

                <div class="flex flex-col space-y-2">
                  @forelse ($miscForm->electionList ?? [] as $key => $election)
                    <div class="flex items-center gap-2">
                    <span class="flex-none text-neutral-600">{{ $loop->iteration }}.</span>
                    <div class="grid w-full grid-cols-3 gap-4 text-sm text-slate-800">
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Election type') }}</span>
                            <span>{{ data_get($election, 'election_type') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Location') }}</span>
                            <span>{{ data_get($election, 'location') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col p-2 space-y-1 border border-gray-300 rounded-md bg-neutral-200/50">
                            <span class="font-medium text-neutral-500">{{ __('Election date') }}</span>
                            <span>{{ data_get($election, 'elected_date') ?? '---' }}</span>
                        </div>
                    </div>
                    <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteElection({{ $key }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                    >
                            @include('components.icons.force-delete')
                    </button>
                  </div>
                    @empty
                        <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                            <span class="text-base font-medium text-gray-600">{{ __('No information added') }}</span>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                    <span class="text-base font-medium text-gray-600">{{ __('No information added') }}</span>
                </div>
            @endif
        </x-form-card>
    </div>
</div>
