<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <x-label for="education.education_place">{{ __('personnel::information.fields.education_place') }}</x-label>
            <x-livewire-input mode="default" name="education.education_place" wire:model="education.education_place"></x-livewire-input>
            @error('education.education_place')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="education.specialty">{{ __('personnel::information.fields.profession') }}</x-label>
            <x-livewire-input mode="default" name="education.specialty" wire:model="education.specialty"></x-livewire-input>
            @error('education.specialty')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="education.request_date">{{ __('personnel::information.fields.request_date') }}</x-label>
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
            <x-label for="education.description">{{ __('personnel::common.labels.description') }}</x-label>
            <x-textarea mode="default" name="education.description" placeholder=""
                        wire:model="education.description"></x-textarea>
        </div>
        <div class="flex flex-col">
            <x-label for="education.request_result">{{ __('personnel::information.fields.request_result') }}</x-label>
            <x-textarea mode="default" name="education.request_result" placeholder=""
                        wire:model="education.request_result"></x-textarea>
        </div>
    </div>
    <div class="flex justify-end space-x-2">
        @if($selectedRequest)
        <x-button mode="danger" wire:click="resetSelected">{{ __('personnel::common.actions.cancel') }}</x-button>
        @endif
        <x-button mode="black" wire:click="addEducationRequest">{{ __('personnel::common.actions.save') }}</x-button>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-visible">
                <x-table.tbl :headers="[__('personnel::information.fields.education_place'),__('personnel::common.labels.specialty'),__('personnel::information.fields.request_date'),__('personnel::common.labels.result'),__('personnel::common.labels.action')]">
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
                                        <x-icons.edit-icon></x-icons.edit-icon>
                                    </button>
                                    <button
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteEducationRequest({{ $request->id }}) })"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex justify-center items-center py-4">
                                    <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

</div>
