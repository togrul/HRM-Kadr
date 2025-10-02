<div class="flex flex-col space-y-8"
     x-data="{}"
>
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
            {!!  $title ?? '' !!}
        </h2>
    </div>

    @if($selectedVacation)
    <div class="flex space-x-2 items-center">
        <div class="flex flex-col w-1/3">
            @php
                $selectedName = array_key_exists('reserved_date_month',$month) ? $month['reserved_date_month']['name'] : '---';
                $selectedId = array_key_exists('reserved_date_month',$month) ? $month['reserved_date_month']['id'] : -1;
            @endphp
            <x-select-list class="w-full" title="" mode="gray" :selected="$selectedName" name="reservedMonth">
                <x-select-list-item wire:click="setData('month','reserved_date_month',null,'---',null)" :selected="'---' ==  $selectedName"
                                    wire:model='month.reserved_date_month.id'>
                    ---
                </x-select-list-item>
                @foreach($months as $mValue => $mKey)
                    <x-select-list-item wire:click="setData('month','reserved_date_month',null,'{{ $mValue }}',{{ $mKey }})"
                                        :selected="$mKey === $selectedId" wire:model='month.reserved_date_month.id'>
                        {{ $mValue }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
        </div>
        <button wire:click="setMonth"
                class="appearance-none bg-slate-900 text-slate-100 px-4 py-2 mt-1 rounded-md shadow-sm text-sm font-medium flex items-center space-x-2 justify-end transition-all duration-300 hover:bg-slate-700 hover:text-slate-100 hover:shadow-none"
        >
            <span> {{ __('Save') }}</span>
        </button>
        <button wire:click="resetVacation"
                class="appearance-none bg-rose-500 text-rose-50 px-4 py-2 mt-1 rounded-md shadow-sm text-sm font-medium flex items-center space-x-2 justify-end transition-all duration-300 hover:bg-rose-100 hover:text-rose-500 hover:shadow-none"
        >
            <span> {{ __('Cancel') }}</span>
        </button>
    </div>
    @endif

    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Year'),__('Reserved month'),__('Total days'),__('Remaining days'), __('Used') ,'action']">
                    @forelse ($personnelModelData->yearlyVacation as $vacation)
                        @php
                            $currentYear = $vacation->year == \Carbon\Carbon::now()->year;
                        @endphp
                        <tr @class([
                            'rounded-sm',
                            'border-none' => ! $currentYear,
                            'border-l-4 border-teal-500' => $currentYear
                        ])>
                            <x-table.td>
                                <span class="text-sm bg-slate-100 rounded-md shadow-sm px-3 py-1 font-medium flex justify-center items-center text-slate-600">{{ $vacation->year }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center justify-center space-x-2">
                                     <span class="text-sm font-medium flex items-center text-slate-900">
                                         {{ array_search($vacation->reserved_date_month, $months) }}
                                    </span>
                                    <button wire:click="updateMonth({{ $vacation }})">
                                        <x-icons.edit-icon></x-icons.edit-icon>
                                    </button>
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-slate-900">{{ $vacation->vacation_days_total }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-green-500">{{ $vacation->remaining_days }}</span>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-sm font-medium flex items-center text-rose-500">{{ $vacation->used_days }}</span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="goToVacations('{{ $vacation->year }}')"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                    >
                                        @include('components.icons.document-icon')
                                    </button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
