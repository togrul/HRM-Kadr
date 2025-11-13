<div class="flex flex-col space-y-4">
    <x-form-card title="Kinships">
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Kinship') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="kinshipForm.kinship.kinship_id"
                    :model="$this->kinshipOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchKinship"
                        wire:model.live.debounce.300ms="searchKinship"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('kinshipForm.kinship.kinship_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.fullname">{{ __('Fullname') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.fullname" wire:model="kinshipForm.kinship.fullname"></x-livewire-input>
                @error('kinshipForm.kinship.fullname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.birthdate">{{ __('Birthdate') }}</x-label>
                <x-pikaday-input mode="gray" name="kinshipForm.kinship.birthdate" format="Y-MM-DD" wire:model.live="kinshipForm.kinship.birthdate">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('kinshipForm.kinship.birthdate', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('kinshipForm.kinship.birthdate')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.birth_place">{{ __('Birth place') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.birth_place" wire:model="kinshipForm.kinship.birth_place"></x-livewire-input>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.company_name">{{ __('Company name') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.company_name" wire:model="kinshipForm.kinship.company_name"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.position">{{ __('Position') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.position" wire:model="kinshipForm.kinship.position"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.registered_address">{{ __('Registered address') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.registered_address" wire:model="kinshipForm.kinship.registered_address"></x-livewire-input>
                @error('kinshipForm.kinship.registered_address')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.residental_address">{{ __('Residental address') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.residental_address" wire:model="kinshipForm.kinship.residental_address"></x-livewire-input>
                @error('kinshipForm.kinship.residental_address')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.birth_certificate_number">{{ __('Birth certificate number') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.birth_certificate_number" wire:model="kinshipForm.kinship.birth_certificate_number"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.marriage_certificate_number">{{ __('Marriage certificate number') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.marriage_certificate_number" wire:model="kinshipForm.kinship.marriage_certificate_number"></x-livewire-input>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button mode="black" wire:click="addKinship">{{ __('Add') }}</x-button>
        </div>

        <div class="grid gap-2">
            @forelse ($kinshipForm->kinshipList ?? [] as $key => $knshModel)
                <div wire:key="kinship-{{ $key }}" class="flex flex-col p-4 space-y-2 rounded-lg shadow bg-neutral-100">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-base font-semibold text-gray-800">
                                {{ data_get($knshModel, 'kinship_name') ?? '---' }}
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ data_get($knshModel, 'fullname') ?? '---' }}
                            </span>
                        </div>
                        <button
                            onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                            wire:click="forceDeleteKinship({{ $key }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                        >
                            @include('components.icons.force-delete')
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-sm text-slate-800">
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Birthdate') }}</span>
                            <span>{{ data_get($knshModel, 'birthdate') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Birth place') }}</span>
                            <span>{{ data_get($knshModel, 'birth_place') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Birth certificate number') }}</span>
                            <span>{{ data_get($knshModel, 'birth_certificate_number') ?? '---' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm text-slate-800">
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Registered address') }}</span>
                            <span>{{ data_get($knshModel, 'registered_address') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Residental address') }}</span>
                            <span>{{ data_get($knshModel, 'residental_address') ?? '---' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm text-slate-800">
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Company') }}</span>
                            <span>{{ data_get($knshModel, 'company_name') ?? '---' }}</span>
                        </div>
                        <div class="flex flex-col space-y-1 border-b border-gray-300">
                            <span class="font-medium text-slate-500">{{ __('Position') }}</span>
                            <span>{{ data_get($knshModel, 'position') ?? '---' }}</span>
                        </div>
                    </div>

                    @if(data_get($knshModel, 'marriage_certificate_number'))
                        <div class="flex flex-col space-y-1 text-sm text-slate-800">
                            <span class="font-medium text-slate-500">{{ __('Marriage certificate number') }}</span>
                            <span>{{ data_get($knshModel, 'marriage_certificate_number') }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                    <h1 class="text-base font-medium text-gray-600">
                        {{ __('No information added') }}
                    </h1>
                </div>
            @endforelse
        </div>
    </x-form-card>
</div>
