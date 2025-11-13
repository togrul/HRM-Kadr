<div class="flex flex-col space-y-4">
    <div class="grid gap-2 grid-cols-1 md:grid-cols-2 items-stretch">
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
                            <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
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
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.force-delete')
                                        </button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
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

        <x-form-card title="Events">
            <div class="grid grid-cols-2 gap-2">
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
            </div>
            <div class="grid grid-cols-2 gap-2">
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
                <div class="flex items-end justify-end">
                    <x-button mode="black" wire:click="addEvent">{{ __('Add') }}</x-button>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                @forelse ($miscForm->eventList ?? [] as $key => $event)
                    <div class="flex items-center justify-between bg-slate-100 shadow-sm rounded-lg px-4 py-2">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-gray-700">{{ data_get($event, 'event_type') ?? '---' }}</span>
                            <span class="text-sm text-gray-500">{{ data_get($event, 'event_name') ?? '---' }}</span>
                            <span class="text-xs text-gray-400">{{ data_get($event, 'event_date') ?? '---' }}</span>
                        </div>
                        <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteEvent({{ $key }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                        >
                            @include('components.icons.force-delete')
                        </button>
                    </div>
                @empty
                    <div class="flex justify-center items-center bg-neutral-100 shadow-sm rounded-lg px-4 py-2 relative">
                        <span class="font-medium text-base text-gray-600">{{ __('No information added') }}</span>
                    </div>
                @endforelse
            </div>
        </x-form-card>
    </div>

    <div class="grid gap-2 grid-cols-1 md:grid-cols-2 items-stretch">
        <x-form-card title="Scientific degrees">
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col">
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
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.science">{{ __('Science') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.science" wire:model="miscForm.degree.science"></x-livewire-input>
                    @error('miscForm.degree.science')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
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
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.subject">{{ __('Subject') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.subject" wire:model="miscForm.degree.subject"></x-livewire-input>
                    @error('miscForm.degree.subject')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col">
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
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.diplom_serie">{{ __('Diplom serie') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.diplom_serie" wire:model="miscForm.degree.diplom_serie"></x-livewire-input>
                    @error('miscForm.degree.diplom_serie')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-4 gap-2">
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.diplom_no">{{ __('Diplom') }} #</x-label>
                    <x-livewire-input mode="gray" type="number" name="miscForm.degree.diplom_no" wire:model="miscForm.degree.diplom_no"></x-livewire-input>
                    @error('miscForm.degree.diplom_no')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
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
                <div class="flex flex-col">
                    <x-label for="miscForm.degree.document_issued_by">{{ __('Issued by') }}</x-label>
                    <x-livewire-input mode="gray" name="miscForm.degree.document_issued_by" wire:model="miscForm.degree.document_issued_by"></x-livewire-input>
                    @error('miscForm.degree.document_issued_by')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex items-end justify-end">
                    <x-button mode="black" wire:click="addDegree">{{ __('Add') }}</x-button>
                </div>
            </div>

            <div class="flex flex-col space-y-2">
                @forelse ($miscForm->degreeList ?? [] as $key => $degree)
                    <div class="flex flex-col space-y-2 bg-slate-100 shadow-sm rounded-lg px-4 py-2 relative overflow-hidden">
                        <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteDegree({{ $key }})"
                            class="flex items-center justify-center absolute right-1 top-1 bg-transparent rounded-lg transition-all duration-300 p-2 hover:bg-rose-100"
                        >
                            @include('components.icons.force-delete')
                        </button>
                        <div class="flex items-center space-x-2 border-b w-max border-slate-400 border-dashed">
                            <p class="font-medium text-teal-500">
                                {{ data_get($degree, 'degree_label') ?? '---' }}
                            </p>
                            <span>-</span>
                            <span class="font-medium text-slate-500">{{ data_get($degree, 'science') ?? '---' }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 w-full">
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Given date') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'given_date') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Subject') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'subject') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Document type') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'edu_doc_label') ?? '---' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 w-full">
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Diplom serie') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_serie') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Diplom') }} #</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_no') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <span class="text-xs text-slate-500">{{ __('Diplom given date') }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'diplom_given_date') ?? '---' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col space-y-1 border-t border-gray-300 pt-2">
                            <span class="text-xs text-slate-500">{{ __('Document issued by') }}</span>
                            <span class="text-sm font-medium text-slate-800">{{ data_get($degree, 'document_issued_by') ?? '---' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex justify-center items-center bg-neutral-100 shadow-sm rounded-lg px-4 py-2 relative">
                        <span class="font-medium text-base text-gray-600">{{ __('No information added') }}</span>
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
                <div class="grid grid-cols-2 gap-2">
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
                </div>
                <div class="grid grid-cols-2 gap-2">
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
                    <div class="flex items-end justify-end">
                        <x-button mode="black" wire:click="addElection">{{ __('Add') }}</x-button>
                    </div>
                </div>

                <div class="flex flex-col space-y-2">
                    @forelse ($miscForm->electionList ?? [] as $key => $election)
                        <div class="flex items-center justify-between bg-slate-100 shadow-sm rounded-lg px-4 py-2">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-700">{{ data_get($election, 'election_type') ?? '---' }}</span>
                                <span class="text-sm text-gray-500">{{ data_get($election, 'location') ?? '---' }}</span>
                                <span class="text-xs text-gray-400">{{ data_get($election, 'elected_date') ?? '---' }}</span>
                            </div>
                            <button
                                onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                wire:click="forceDeleteElection({{ $key }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                @include('components.icons.force-delete')
                            </button>
                        </div>
                    @empty
                        <div class="flex justify-center items-center bg-neutral-100 shadow-sm rounded-lg px-4 py-2 relative">
                            <span class="font-medium text-base text-gray-600">{{ __('No information added') }}</span>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="flex justify-center items-center bg-neutral-100 shadow-sm rounded-lg px-4 py-2 relative">
                    <span class="font-medium text-base text-gray-600">{{ __('No information added') }}</span>
                </div>
            @endif
        </x-form-card>
    </div>
</div>
