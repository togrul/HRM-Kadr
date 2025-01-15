<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <x-label for="education.education_place">{{ __('Education place') }}</x-label>
            <x-livewire-input mode="default" name="education.education_place" wire:model="education.education_place"></x-livewire-input>
            @error('education.education_place')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="education.specialty">{{ __('Profession') }}</x-label>
            <x-livewire-input mode="default" name="education.specialty" wire:model="education.specialty"></x-livewire-input>
            @error('education.specialty')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="education.request_date">{{ __('Request date') }}</x-label>
            <x-pikaday-input mode="default" name="education.request_date" format="Y-MM-DD" wire:model.live="education.request_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('education.request_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('education.request_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="education.description">{{ __('Description') }}</x-label>
            <x-textarea mode="default" name="education.description" placeholder="{{__('')}}"
                        wire:model="education.description"></x-textarea>
        </div>
        <div class="flex flex-col">
            <x-label for="education.request_result">{{ __('Request result') }}</x-label>
            <x-textarea mode="default" name="education.request_result" placeholder="{{__('')}}"
                        wire:model="education.request_result"></x-textarea>
        </div>
    </div>
    <div class="flex justify-end space-x-2">
        @if($selectedRequest)
        <x-button mode="danger" wire:click="resetSelected">{{ __('Cancel') }}</x-button>
        @endif
        <x-button mode="black" wire:click="addEducationRequest">{{ __('Save') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Education place'),__('Specialty'),__('Request date'),__('Result'),'action']">
                    @forelse ($personnelModelData->educationRequests as $request)
                        <tr @class([
                            'transition-all duration-300',
                            'bg-teal-100' => $request->id === $selectedRequest
                        ])>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $request->education_place }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $request->specialty }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $request->request_date->format('d.m.Y') }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span @class([
                                    'text-sm font-medium flex items-center text-blue-500 bg-blue-100 rounded-sm px-3 py-1 flex items-center justify-center'
                                ])>{{ $request->request_result }}</span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <div class="flex items-center space-x-2">
                                    <button
                                        wire:click="updateEducationRequest({{ $request->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.edit-icon')
                                    </button>
                                    <button
                                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeleteEducationRequest({{ $request->id }})"
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

