<div class="flex flex-col space-y-4">
    <x-form-card title="{{ __('personnel::common.steps.kinships') }}">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-small-badge mode="{{ $kinshipForm->isEditingKinship() ? 'blue' : 'green' }}">
                    {{ $kinshipForm->isEditingKinship()
                        ? __('personnel::common.labels.editing_kinship')
                        : __('personnel::common.labels.new_kinship') }}
                </x-small-badge>
                <p class="text-sm text-neutral-500">
                    {{ $kinshipForm->isEditingKinship()
                        ? __('personnel::common.labels.editing_kinship_hint')
                        : __('personnel::common.labels.new_kinship_hint') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown label="{{ __('personnel::common.labels.kinship') }}" placeholder="---" mode="gray" class="w-full"
                    wire:model.live="kinshipForm.kinship.kinship_id" :model="$this->kinshipOptions" :search-model="data_get($stepSearchModels, 'searchKinship', 'searchKinship')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchKinship', __('personnel::common.placeholders.search'))">
                </x-ui.select-dropdown>
                @error('kinshipForm.kinship.kinship_id')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.fullname">{{ __('personnel::common.labels.fullname') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.fullname"
                    wire:model="kinshipForm.kinship.fullname"></x-livewire-input>
                @error('kinshipForm.kinship.fullname')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.birthdate">{{ __('personnel::common.labels.birthdate') }}</x-label>
                <x-pikaday-input mode="gray" name="kinshipForm.kinship.birthdate" format="Y-MM-DD"
                    wire:model.live="kinshipForm.kinship.birthdate">
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
                <x-label for="kinshipForm.kinship.birth_place">{{ __('personnel::common.labels.birth_place') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.birth_place"
                    wire:model="kinshipForm.kinship.birth_place"></x-livewire-input>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.company_name">{{ __('personnel::common.labels.company_name') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.company_name"
                    wire:model="kinshipForm.kinship.company_name"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.position">{{ __('personnel::common.labels.position') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.position"
                    wire:model="kinshipForm.kinship.position"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.registered_address">{{ __('personnel::common.labels.registered_address') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.registered_address"
                    wire:model="kinshipForm.kinship.registered_address"></x-livewire-input>
                @error('kinshipForm.kinship.registered_address')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="kinshipForm.kinship.residental_address">{{ __('personnel::common.labels.residental_address') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.residental_address"
                    wire:model="kinshipForm.kinship.residental_address"></x-livewire-input>
                @error('kinshipForm.kinship.residental_address')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <x-label
                    for="kinshipForm.kinship.birth_certificate_number">{{ __('personnel::common.labels.birth_certificate_number') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.birth_certificate_number"
                    wire:model="kinshipForm.kinship.birth_certificate_number"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label
                    for="kinshipForm.kinship.marriage_certificate_number">{{ __('personnel::common.labels.marriage_certificate_number') }}</x-label>
                <x-livewire-input mode="gray" name="kinshipForm.kinship.marriage_certificate_number"
                    wire:model="kinshipForm.kinship.marriage_certificate_number"></x-livewire-input>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            @if($kinshipForm->isEditingKinship())
                <x-button mode="danger" wire:click="cancelKinshipEdit">{{ __('personnel::common.actions.cancel') }}</x-button>
            @endif

            <x-button mode="black" wire:click="saveKinship">
                {{ $kinshipForm->isEditingKinship()
                    ? __('personnel::common.actions.update')
                    : __('personnel::common.actions.add') }}
            </x-button>
        </div>

        <div class="grid gap-2">
            @forelse ($kinshipForm->kinshipList ?? [] as $key => $knshModel)
                @php
                    $rowKey = data_get($knshModel, 'row_key') ?? ('kinships-'.$key);
                    $isEditing = $kinshipForm->editingKinshipKey === $rowKey;
                @endphp

                <x-surface-card
                    wire:key="kinships-{{ $rowKey }}"
                    @class([
                        'transition-all duration-300 hover:border-zinc-300 hover:bg-zinc-100/80' => ! $isEditing,
                        '!border-sky-300 !bg-sky-50/70 shadow-[0_18px_40px_-28px_rgba(14,165,233,0.45)] ring-1 ring-sky-200' => $isEditing,
                    ])
                >
                    <x-slot name="title">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-neutral-500 shadow-sm">
                                    {{ data_get($knshModel, 'kinship_name') ?? '---' }}
                                </span>
                                <span class="text-base font-semibold text-emerald-600 border-b border-dotted border-emerald-400/80">
                                    {{ data_get($knshModel, 'fullname') ?? '---' }}
                                </span>

                                @if($isEditing)
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">
                                        {{ __('personnel::common.actions.edit') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="editKinship('{{ $rowKey }}')"
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white text-slate-300 shadow-[0_12px_28px_-18px_rgba(15,23,42,0.22)] transition hover:text-sky-500">
                                    <x-icons.edit-icon color="{{ $isEditing ? 'text-sky-500' : 'text-slate-300' }}" hover="{{ $isEditing ? 'text-sky-600' : 'text-sky-500' }}"></x-icons.edit-icon>
                                </button>
                                <button
                                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteKinship('{{ $rowKey }}') })"
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white text-rose-300 shadow-[0_12px_28px_-18px_rgba(15,23,42,0.22)] transition hover:text-rose-500">
                                    <x-icons.force-delete color="text-rose-300" hover="text-rose-500"></x-icons.force-delete>
                                </button>
                            </div>
                        </div>
                    </x-slot>
                    <div class="flex flex-col w-full space-y-3">
                        <div class="grid grid-cols-3 gap-4 text-sm text-slate-600">
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.birthdate') }}</span>
                                <span>{{ data_get($knshModel, 'birthdate') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.birth_place') }}</span>
                                <span>{{ data_get($knshModel, 'birth_place') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.birth_certificate_number') }}</span>
                                <span>{{ data_get($knshModel, 'birth_certificate_number') ?? '---' }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm text-slate-800">
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.registered_address') }}</span>
                                <span>{{ data_get($knshModel, 'registered_address') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.residental_address') }}</span>
                                <span>{{ data_get($knshModel, 'residental_address') ?? '---' }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm text-neutral-800">
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.company') }}</span>
                                <span>{{ data_get($knshModel, 'company_name') ?? '---' }}</span>
                            </div>
                            <div class="flex flex-col p-2 space-y-1 rounded-md bg-neutral-100/90">
                                <span class="font-medium text-neutral-500">{{ __('personnel::common.labels.position') }}</span>
                                <span>{{ data_get($knshModel, 'position') ?? '---' }}</span>
                            </div>
                        </div>

                        @if (data_get($knshModel, 'marriage_certificate_number'))
                            <div
                                class="flex flex-col p-2 space-y-1 text-sm rounded-md bg-neutral-100/90">
                                <span
                                    class="font-medium text-neutral-500">{{ __('personnel::common.labels.marriage_certificate_number') }}</span>
                                <span>{{ data_get($knshModel, 'marriage_certificate_number') }}</span>
                            </div>
                        @endif
                    </div>
                </x-surface-card>
            @empty
                <div class="relative flex items-center justify-center px-4 py-2 rounded-lg shadow-sm bg-neutral-100">
                    <h1 class="text-base font-medium text-gray-600">
                        {{ __('personnel::common.labels.no_information_added') }}
                    </h1>
                </div>
            @endforelse
        </div>
    </x-form-card>
</div>
