<div class="flex flex-col space-y-4">
    <x-form-card title="Awards">
        <div class="grid grid-cols-6 gap-2">
            <div class="flex flex-col col-span-2">
                <x-ui.select-dropdown
                    label="{{ __('Awards') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="awardsPunishmentsForm.award.award_id"
                    :model="$this->awardOptions"
                    :search-model="data_get($stepSearchModels, 'searchAward', 'searchAward')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchAward', __('Search...'))"
                >
                </x-ui.select-dropdown>
                @error('awardsPunishmentsForm.award.award_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.award.reason">{{ __('Reason') }}</x-label>
                <x-livewire-input
                    mode="gray"
                    name="awardsPunishmentsForm.award.reason"
                    wire:model="awardsPunishmentsForm.award.reason"
                ></x-livewire-input>
                @error('awardsPunishmentsForm.award.reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="awardsPunishmentsForm.award.given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input
                    mode="gray"
                    name="awardsPunishmentsForm.award.given_date"
                    format="Y-MM-DD"
                    wire:model.live="awardsPunishmentsForm.award.given_date"
                >
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('awardsPunishmentsForm.award.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('awardsPunishmentsForm.award.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex items-end space-x-3">
                <x-label class="mb-1" for="awardsPunishmentsForm.award.is_old">{{ __('Is old?') }}</x-label>
                <x-checkbox
                    name="awardsPunishmentsForm.award.is_old"
                    model="awardsPunishmentsForm.award.is_old"
                ></x-checkbox>
            </div>

            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.award.order_given_by">{{ __('Order issued by') }}</x-label>
                <x-livewire-input mode="gray" name="awardsPunishmentsForm.award.order_given_by" wire:model="awardsPunishmentsForm.award.order_given_by"></x-livewire-input>
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.award.order_no">{{ __('Order number') }}</x-label>
                <x-livewire-input mode="gray" name="awardsPunishmentsForm.award.order_no" wire:model="awardsPunishmentsForm.award.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.award.order_date">{{ __('Order date') }}</x-label>
                <x-pikaday-input
                    mode="gray"
                    name="awardsPunishmentsForm.award.order_date"
                    format="Y-MM-DD"
                    wire:model.live="awardsPunishmentsForm.award.order_date"
                >
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('awardsPunishmentsForm.award.order_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
          </div>
        </div>

        <div class="flex justify-end w-full">
            <x-button mode="black" wire:click="addAward">{{ __('Add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('Award'), __('Reason'), __('Date'), __('Info') ,'action', 'action']">
                        @forelse ($awardsPunishmentsForm->awardList ?? [] as $key => $awdModel)
                            <tr>
                                <x-table.td>
                                    <span class="px-2 py-1 text-sm font-medium text-gray-700 rounded-md bg-zinc-200">
                                        {{ $this->awardLabel(data_get($awdModel, 'award_id')) ?? '---' }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                   <span class="text-sm font-medium text-gray-700 truncate whitespace-normal line-clamp-3">
                                        {{ data_get($awdModel, 'reason') }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ data_get($awdModel, 'given_date') }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                <div class="flex items-start space-x-6">
                                    <div class="flex flex-col items-start space-y-1">
                                         <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                                {{ __('Issued by') }}:
                                         </span>
                                        <span class="text-sm font-medium text-gray-900">
                                                {{ data_get($awdModel, 'order_given_by', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Number') }} #:
                                        </span>
                                        <span class="text-sm font-medium text-blue-500">
                                            {{ data_get($awdModel, 'order_no', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Date') }}:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            @if(! empty($awdModel['order_date']))
                                                {{ \Carbon\Carbon::parse($awdModel['order_date'])->format('d.m.Y') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </x-table.td>
                                <x-table.td>
                                    <span @class([
                                        'w-3 h-3 rounded-full shadow-sm flex',
                                        'bg-gray-300' => data_get($awdModel, 'is_old'),
                                        'bg-green-400' => ! data_get($awdModel, 'is_old'),
                                    ])>
                                    </span>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeleteAward({{ $key }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.force-delete')
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
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

    <x-form-card title="Punishments">
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col col-span-2">
                <x-ui.select-dropdown
                    label="{{ __('Punishments') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="awardsPunishmentsForm.punishment.punishment_id"
                    :model="$this->punishmentOptions"
                    :search-model="data_get($stepSearchModels, 'searchPunishment', 'searchPunishment')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchPunishment', __('Search...'))"
                >
                </x-ui.select-dropdown>
                @error('awardsPunishmentsForm.punishment.punishment_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.punishment.reason">{{ __('Reason') }}</x-label>
                <x-livewire-input
                    mode="gray"
                    name="awardsPunishmentsForm.punishment.reason"
                    wire:model="awardsPunishmentsForm.punishment.reason"
                ></x-livewire-input>
                @error('awardsPunishmentsForm.punishment.reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <div class="flex flex-col">
                <x-label for="awardsPunishmentsForm.punishment.given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input
                    mode="gray"
                    name="awardsPunishmentsForm.punishment.given_date"
                    format="Y-MM-DD"
                    wire:model.live="awardsPunishmentsForm.punishment.given_date"
                >
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('awardsPunishmentsForm.punishment.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('awardsPunishmentsForm.punishment.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="awardsPunishmentsForm.punishment.expired_date">{{ __('Expired date') }}</x-label>
                <x-pikaday-input
                    mode="gray"
                    name="awardsPunishmentsForm.punishment.expired_date"
                    format="Y-MM-DD"
                    wire:model.live="awardsPunishmentsForm.punishment.expired_date"
                >
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('awardsPunishmentsForm.punishment.expired_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.punishment.order_given_by">{{ __('Order issued by') }}</x-label>
                <x-livewire-input mode="gray" name="awardsPunishmentsForm.punishment.order_given_by" wire:model="awardsPunishmentsForm.punishment.order_given_by"></x-livewire-input>
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="awardsPunishmentsForm.punishment.order_no">{{ __('Order number') }}</x-label>
                <x-livewire-input mode="gray" name="awardsPunishmentsForm.punishment.order_no" wire:model="awardsPunishmentsForm.punishment.order_no"></x-livewire-input>
            </div>
             <div class="flex flex-col">
                <x-label for="awardsPunishmentsForm.punishment.order_date">{{ __('Order date') }}</x-label>
                <x-pikaday-input
                    mode="gray"
                    name="awardsPunishmentsForm.punishment.order_date"
                    format="Y-MM-DD"
                    wire:model.live="awardsPunishmentsForm.punishment.order_date"
                >
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('awardsPunishmentsForm.punishment.order_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button mode="black" wire:click="addPunishment">{{ __('Add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('Punishment'), __('Reason'), __('Date'), __('Info') ,'action']">
                        @forelse ($awardsPunishmentsForm->punishmentList ?? [] as $key => $pnshModel)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $this->punishmentLabel(data_get($pnshModel, 'punishment_id')) ?? '---' }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                   <span class="text-sm font-medium text-gray-700">
                                        {{ data_get($pnshModel, 'reason') }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex items-center space-x-6">
                                        <div class="flex flex-col items-start space-y-1">
                                            <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('Given date') }}:</span>
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ data_get($pnshModel, 'given_date') }}
                                            </span>
                                        </div>

                                        <div class="flex flex-col items-start space-y-1">
                                            <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('Expired date') }}:</span>
                                            <span class="text-sm font-medium text-rose-500">
                                                {{ data_get($pnshModel, 'expired_date') ?: '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </x-table.td>
                                <x-table.td>
                                <div class="flex items-center space-x-6">
                                    <div class="flex flex-col items-start space-y-1">
                                         <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                                {{ __('Issued by') }}:
                                         </span>
                                        <span class="text-sm font-medium text-gray-900">
                                                {{ data_get($pnshModel, 'order_given_by', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Number') }} #:
                                        </span>
                                        <span class="text-sm font-medium text-blue-500">
                                            {{ data_get($pnshModel, 'order_no', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Date') }}:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            @if(! empty($pnshModel['order_date']))
                                                {{ \Carbon\Carbon::parse($pnshModel['order_date'])->format('d.m.Y') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                        wire:click="forceDeletePunishment({{ $key }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.force-delete')
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
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

    <x-form-card title="Discrediting information">
        <div class="flex flex-col">
            <x-label for="personalForm.personnelExtra.discrediting_information">{{ __('Description') }}</x-label>
            <x-textarea
                mode="gray"
                name="personalForm.personnelExtra.discrediting_information"
                placeholder=""
                wire:model="personalForm.personnelExtra.discrediting_information"
            ></x-textarea>
        </div>
    </x-form-card>
</div>
