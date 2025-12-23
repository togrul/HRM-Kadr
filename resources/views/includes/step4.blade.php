<div class="flex flex-col space-y-4">
    <x-form-card title="Labor activities">
        <div class="grid grid-cols-5 gap-2">
            <div class="flex flex-col">
                <x-label for="laborActivityForm.laborActivity.company_name">{{ __('Company name') }}</x-label>
                <x-livewire-input mode="gray" name="laborActivityForm.laborActivity.company_name" wire:model="laborActivityForm.laborActivity.company_name"></x-livewire-input>
                @error('laborActivityForm.laborActivity.company_name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="laborActivityForm.laborActivity.position">{{ __('Position') }}</x-label>
                <x-livewire-input mode="gray" name="laborActivityForm.laborActivity.position" wire:model="laborActivityForm.laborActivity.position"></x-livewire-input>
                @error('laborActivityForm.laborActivity.position')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="laborActivityForm.laborActivity.join_date">{{ __('Join date') }}</x-label>
                <x-pikaday-input mode="gray" name="laborActivityForm.laborActivity.join_date" format="Y-MM-DD" wire:model.live="laborActivityForm.laborActivity.join_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('laborActivityForm.laborActivity.join_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('laborActivityForm.laborActivity.join_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="laborActivityForm.laborActivity.leave_date">{{ __('Leave date') }}</x-label>
                <x-pikaday-input mode="gray" name="laborActivityForm.laborActivity.leave_date" format="Y-MM-DD" wire:model.live="laborActivityForm.laborActivity.leave_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('laborActivityForm.laborActivity.leave_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('laborActivityForm.laborActivity.leave_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="laborActivityForm.laborActivity.coefficient">{{ __('Coefficient') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="laborActivityForm.laborActivity.coefficient" wire:model="laborActivityForm.laborActivity.coefficient"></x-livewire-input>
                @error('laborActivityForm.laborActivity.coefficient')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if($isSpecialService)
            <div class="grid grid-cols-3 gap-2">
                <div class="flex flex-col">
                    <x-label for="laborActivityForm.laborActivity.order_given_by">{{ __('Order issued by') }}</x-label>
                    <x-livewire-input mode="gray" name="laborActivityForm.laborActivity.order_given_by" wire:model="laborActivityForm.laborActivity.order_given_by"></x-livewire-input>
                    @error('laborActivityForm.laborActivity.order_given_by')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="laborActivityForm.laborActivity.order_no">{{ __('Order number') }}</x-label>
                    <x-livewire-input mode="gray" name="laborActivityForm.laborActivity.order_no" wire:model="laborActivityForm.laborActivity.order_no"></x-livewire-input>
                    @error('laborActivityForm.laborActivity.order_no')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex items-start justify-between w-full">
                    <div class="flex flex-col">
                        <x-label for="laborActivityForm.laborActivity.order_date">{{ __('Order date') }}</x-label>
                        <x-pikaday-input mode="gray" name="laborActivityForm.laborActivity.order_date" format="Y-MM-DD" wire:model.live="laborActivityForm.laborActivity.order_date">
                            <x-slot name="script">
                                $el.onchange = function () {
                                @this.set('laborActivityForm.laborActivity.order_date', $el.value);
                                }
                            </x-slot>
                        </x-pikaday-input>
                        @error('laborActivityForm.laborActivity.order_date')
                        <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                    <div class="flex flex-col w-20">
                        <x-label for="laborActivityForm.laborActivity.time">{{ __('Time') }}</x-label>
                        <x-livewire-input mode="gray" name="laborActivityForm.laborActivity.time" wire:model="laborActivityForm.laborActivity.time" placeholder="12:00"></x-livewire-input>
                    </div>
                </div>

            </div>
        @endif
        <div class="flex justify-end space-x-4">
            <x-checkbox
                name="laborActivityForm.laborActivity.is_current"
                model="laborActivityForm.laborActivity.is_current"
            >
                {{ __('Is current?') }}
            </x-checkbox>
            <x-checkbox
                name="isSpecialService"
                model="isSpecialService"
            >
                {{ __('Military forces or law enforcement?') }}
            </x-checkbox>
            <x-button  mode="black" wire:click="addLaborActivity">{{ __('Add') }}</x-button>
        </div>
        {{--is yerleri siyahisi--}}
        @forelse ($laborActivityForm->laborActivityList as $key => $laModel)
        <div class="relative flex flex-col px-4 py-2 space-y-2 rounded-lg shadow-sm bg-slate-100">
            <button
                onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                wire:click="forceDeleteLaborActivity({{ $key }})"
                class="absolute flex items-center justify-center p-2 transition-all duration-300 bg-transparent rounded-lg right-1 top-1 hover:bg-rose-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-rose-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
            </button>
            <div class="flex items-center space-x-2 border-b border-dashed w-max border-slate-400">
                <p class="font-medium text-gray-700">
                    {{ $laModel['company_name'] }}
                </p>
                @if($laModel['is_current'] ??= false)
                <span class="flex items-center justify-center w-4 h-4 bg-green-500 border-4 border-green-200 rounded-full">
                </span>
                @endif
                <span>-</span>
                <span class="text-sm font-medium text-slate-500">{{ $laModel['position_label'] ?? ($laModel['position'] ?? '') }}</span>
            </div>
            <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-4">
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 2.994v2.25m10.5-2.25v2.25m-14.252 13.5V7.491a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v11.251m-18 0a2.25 2.25 0 0 0 2.25 2.25h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-6.75-6h2.25m-9 2.25h4.5m.002-2.25h.005v.006H12v-.006Zm-.001 4.5h.006v.006h-.006v-.005Zm-2.25.001h.005v.006H9.75v-.006Zm-2.25 0h.005v.005h-.006v-.005Zm6.75-2.247h.005v.005h-.005v-.005Zm0 2.247h.006v.006h-.006v-.006Zm2.25-2.248h.006V15H16.5v-.005Z" />
                        </svg>
                        <span class="text-sm font-medium text-slate-500">{{ __('Date') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-slate-900">{{ \Carbon\Carbon::parse($laModel['join_date'])->format('d.m.Y') }}</span>
                        <span>-</span>
                        @if(!empty($laModel['leave_date']))
                            <span class="text-sm font-medium text-rose-500">{{ \Carbon\Carbon::parse($laModel['leave_date'])->format('d.m.Y') }}</span>
                        @else
                            <span class="text-sm font-medium text-green-500">aktiv</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-sm font-medium text-slate-500">{{ __('Duration') }}</span>
                    </div>
                    <span class="text-sm font-medium text-slate-900">
                        {{ $calculatedData['data'][$key]['duration']['year'] }} {{ __('year') }}
                        {{ $calculatedData['data'][$key]['duration']['month'] }} {{ __('month') }}
                        ({{ $calculatedData['data'][$key]['duration']['diff'] }} {{ __('month') }})
                    </span>
                </div>

                @if(!empty($laModel['coefficient']))
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.99 14.993 6-6m6 3.001c0 1.268-.63 2.39-1.593 3.069a3.746 3.746 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043 3.745 3.745 0 0 1-3.068 1.593c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.746 3.746 0 0 1-1.043-3.297 3.746 3.746 0 0 1-1.593-3.068c0-1.268.63-2.39 1.593-3.068a3.746 3.746 0 0 1 1.043-3.297 3.745 3.745 0 0 1 3.296-1.042 3.745 3.745 0 0 1 3.068-1.594c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.297 3.746 3.746 0 0 1 1.593 3.068ZM9.74 9.743h.008v.007H9.74v-.007Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-sm font-medium text-slate-500">{{ __('Coefficient') }}</span>
                    </div>
                    <span class="text-sm font-medium text-slate-900">x{{ $laModel['coefficient'] }}</span>
                </div>


                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                        <span class="text-sm font-medium text-slate-500">{{ __('Total') }}</span>
                    </div>
                    <span class="text-sm font-medium text-slate-900">
                        {{ $calculatedData['data'][$key]['duration']['duration'] }} {{ __('month') }}
                    </span>
                </div>
                @endif
            </div>

            @if(array_key_exists('is_special_service',$laModel) && $laModel['is_special_service'])
                <hr class="border-slate-300" >

                <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span class="text-sm font-medium text-slate-500">{{ __('Order given by') }}</span>
                        </div>
                        <span class="text-sm font-medium text-slate-900">{{ $laModel['order_given_by'] }}</span>
                    </div>

                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                            <span class="text-sm font-medium text-slate-500">{{ __('Order number') }} #</span>
                        </div>
                        <span class="text-sm font-medium text-slate-900">{{ $laModel['order_no'] }}</span>
                    </div>

                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-slate-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <span class="text-sm font-medium text-slate-500">{{ __('Order date') }} #</span>
                        </div>
                        <span class="text-sm font-medium text-slate-900">
                             {{ \Carbon\Carbon::parse($laModel['order_date'])->format('d.m.Y') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
        @empty
        @endforelse

        @if (!empty($laModel))
            <hr>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="flex flex-col items-start px-4 py-2 space-y-1 rounded-lg shadow-md bg-slate-50">
                    <h1 class="font-medium border-b border-dashed text-slate-500">
                        {{ __('Old seniority') }}
                    </h1>
                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-teal-500">{{ __('Property') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_old'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_old']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_old']['month'] }} {{ __('month') }})
                        </span>
                    </div>

                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-yellow-400">{{ __('Military') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_military_old'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_old_military']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_old_military']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                </div>

                <div class="flex flex-col items-start px-4 py-2 space-y-1 rounded-lg shadow-md bg-slate-50">
                    <h1 class="font-medium border-b border-dashed text-slate-500">
                        {{ __('Current seniority') }}
                    </h1>
                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-teal-500">{{ __('Standart') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_current_diff'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_current_diff']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_current_diff']['month'] }} {{ __('month') }})
                    </span>
                    </div>
                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-blue-500">{{ __('Coefficient') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_current'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_current']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_current']['month'] }} {{ __('month') }})
                    </span>
                    </div>
                </div>

                <div class="flex flex-col items-start px-4 py-2 space-y-1 rounded-lg shadow-md bg-slate-50">
                    <h1 class="font-medium border-b border-dashed text-slate-500">
                        {{ __('Total seniority') }}
                    </h1>
                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-teal-500">{{ __('Property') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_total'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_total_full']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_total_full']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                    <div class="flex items-center self-start space-x-2 text-sm">
                        <span class="font-medium text-yellow-400">{{ __('Military') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_total_military'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_total_military_full']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_total_military_full']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </x-form-card>
</div>

<x-form-card title="Ranks">
    <div class="grid grid-cols-4 gap-3">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Ranks') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="laborActivityForm.rank.rank_id"
                :model="$this->rankOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchRank"
                    wire:model.live.debounce.300ms="searchRank"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
            @error('laborActivityForm.rank.rank_id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Rank reasons') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="laborActivityForm.rank.rank_reason_id"
                :model="$this->rankReasonOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchRankReason"
                    wire:model.live.debounce.300ms="searchRankReason"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
            <x-label for="laborActivityForm.rank.name">{{ __('Name') }}</x-label>
            <x-livewire-input mode="gray" name="laborActivityForm.rank.name" wire:model="laborActivityForm.rank.name"></x-livewire-input>
            @error('laborActivityForm.rank.name')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="laborActivityForm.rank.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="gray" name="laborActivityForm.rank.given_date" format="Y-MM-DD" wire:model.live="laborActivityForm.rank.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('laborActivityForm.rank.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('laborActivityForm.rank.given_date')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="laborActivityForm.rank.order_given_by">{{ __('Order issued by') }}</x-label>
            <x-livewire-input mode="gray" name="laborActivityForm.rank.order_given_by" wire:model="laborActivityForm.rank.order_given_by"></x-livewire-input>
            @error('laborActivityForm.rank.order_given_by')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="laborActivityForm.rank.order_no">{{ __('Order number') }}</x-label>
            <x-livewire-input mode="gray" name="laborActivityForm.rank.order_no" wire:model="laborActivityForm.rank.order_no"></x-livewire-input>
            @error('laborActivityForm.rank.order_no')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="laborActivityForm.rank.order_date">{{ __('Order date') }}</x-label>
            <x-pikaday-input mode="gray" name="laborActivityForm.rank.order_date" format="Y-MM-DD" wire:model.live="laborActivityForm.rank.order_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('laborActivityForm.rank.order_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('laborActivityForm.rank.order_date')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="flex justify-end">
        <x-button  mode="black" wire:click="addRank">{{ __('Add') }}</x-button>
    </div>

    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Rank'),__('Given date'),__('Info'),'action']">
                    @forelse ($laborActivityForm->rankList as $keyRank => $rModel)
                        <tr>
                            <x-table.td>
                                <div class="flex flex-col space-y-1">
                                    <span class="text-sm font-medium border-b border-dashed text-emerald-600 w-max border-slate-400">
                                        {{ $this->rankLabel(data_get($rModel, 'rank_id')) ?? '---' }}
                                   </span>
                                    <span class="text-sm font-medium text-gray-500 w-max">
                                        {{ $this->rankReasonLabel(data_get($rModel, 'rank_reason_id')) ?? '---' }}
                                   </span>
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ data_get($rModel, 'given_date') }}
                               </span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center space-x-6">
                                    <div class="flex flex-col items-start space-y-1">
                                         <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                                {{ __('Issued by') }}:
                                         </span>
                                        <span class="text-sm font-medium text-gray-900">
                                                {{ data_get($rModel, 'order_given_by', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Number') }} #:
                                        </span>
                                        <span class="text-sm font-medium text-blue-500">
                                            {{ data_get($rModel, 'order_no', '---') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">
                                            {{ __('Date') }}:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            @if(! empty($rModel['order_date']))
                                                {{ \Carbon\Carbon::parse($rModel['order_date'])->format('d.m.Y') }}
                                            @else
                                                ---
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <button
                                    onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                    wire:click="forceDeleteRank({{ $keyRank }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                >
                                    <x-icons.force-delete></x-icons.force-delete>
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
