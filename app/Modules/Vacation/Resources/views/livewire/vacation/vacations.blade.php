<div
    class="flex flex-col"
    x-data
    x-init="
        const root = $el;
        const applyPaginatorTheme = (isUpdate = false) => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };

        applyPaginatorTheme(false);
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(() => applyPaginatorTheme(true)));
            });
        }
    "
>
    <div class="flex flex-col px-6 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-row items-center justify-start px-2 py-2 space-x-2 rounded-xl">
                <x-label>{{ __('vacation::common.labels.year') }} </x-label>
                <select name="selectedYear" id="selectedYear" wire:model.live="selectedYear" @disabled(!empty($filter['date']['min'] ?? null) || !empty($filter['date']['max'] ?? null))
                    class="block w-full text-base text-white rounded-md bg-neutral-800 border-slate-600 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('review-self-service-requests')
                        <a href="{{ route('self-service-reviews') }}"
                            class="inline-flex items-center justify-center px-4 h-12 text-sm font-semibold text-zinc-700 transition-all duration-300 bg-white border border-zinc-200 rounded-xl hover:border-zinc-300 hover:bg-zinc-50">
                            {{ __('ui::menu.items.self_service_reviews') }}
                        </a>
                    @endcan
                    @can('export-vacations')
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="flex flex-col xl:col-span-2">
                <x-ui.select-dropdown
                    :label="__('vacation::common.labels.structure')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.structure_id"
                    :model="$this->structureOptions"
                    search-model="searchStructure"
                >
                </x-ui.select-dropdown>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.fullname">{{ __('vacation::common.labels.fullname') }}</x-label>
                <x-livewire-input mode="gray" name="filter.fullname"
                    wire:model.defer="filter.fullname"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.order_no">{{ __('vacation::common.labels.order_hash') }}</x-label>
                <x-livewire-input mode="gray" name="filter.order_no"
                    wire:model.defer="filter.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col lg:col-span-2">
                <x-label for="filter.date_range">{{ __('vacation::common.labels.date_range') }}</x-label>
                <div class="flex items-center space-x-1">
                    <x-pikaday-input mode="gray" name="filter.date.min" format="Y-MM-DD"
                        wire:model.defer="filter.date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            if ($el.value === '') {
                            @this.set('filter.date.min', null);
                            } else {
                            @this.set('filter.date.min', $el.value);
                            }
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="filter.date.max" format="Y-MM-DD"
                        wire:model.defer="filter.date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            if ($el.value === '') {
                            @this.set('filter.date.max', null); // Explicitly set to null when cleared
                            } else {
                            @this.set('filter.date.max', $el.value);
                            }
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.vacation_places">{{ __('vacation::common.labels.location') }}</x-label>
                <x-livewire-input mode="gray" name="filter.vacation_places"
                    wire:model.defer="filter.vacation_places"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.duration">{{ __('vacation::common.labels.duration') }}</x-label>
                <x-livewire-input type="number" mode="gray" name="filter.duration"
                    wire:model.defer="filter.duration"></x-livewire-input>
            </div>
            <div class="flex flex-col w-full space-y-1 lg:col-span-2">
                <x-label for="filter.gender">{{ __('vacation::common.labels.status') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="all">
                        <span class="ml-2 text-sm font-normal">{{ __('vacation::common.labels.all') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="at_work">
                        <span class="ml-2 text-sm font-normal">{{ __('vacation::common.labels.at_work') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="in_vacation">
                        <span class="ml-2 text-sm font-normal">{{ __('vacation::common.labels.in_vacation') }}</span>
                    </label>
                </div>
            </div>
            <div class="flex items-end space-x-2">
                <x-button mode="primary" wire:click="searchFilter()">{{ __('vacation::common.labels.search') }}</x-button>
                <x-button mode="black" wire:click="resetFilter">{{ __('vacation::common.labels.reset') }}</x-button>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">

                    <x-table.tbl :headers="$this->getTableHeaders()" title="{{ __('vacation::common.titles.vacations') }}">
                        @forelse ($this->vacations as $_vacation)
                            <tr @class([
                                'bg-teal-50' => $_vacation->is_active_vacation,
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $_vacation->row_no }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span
                                            class="flex items-center justify-center text-sm font-medium rounded-lg text-slate-500 drop-shadow-2xl">
                                            {{ $_vacation->personnel?->latestRank?->rank->name }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_vacation->personnel?->fullname }}
                                        </span>
                                        @if ($_vacation->is_active_vacation)
                                            <span
                                                class="flex items-center justify-center px-2 py-1 text-sm font-medium text-green-700 bg-green-200 rounded-lg">
                                                {{ __('vacation::common.labels.in_vacation') }}
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span
                                            class="px-2 py-1 text-sm font-medium text-blue-500 rounded-lg bg-slate-100">
                                            {{ $_vacation->personnel?->structure?->name }}
                                        </span>
                                        <span
                                            class="px-2 py-1 text-sm font-medium rounded-lg text-rose-500 bg-slate-100">
                                            {{ $_vacation->personnel?->position_label }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.duration') }}:</span>
                                            <span class="text-teal-500">{{ $_vacation->duration }}
                                                {{ __('vacation::common.labels.day') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.start_date') }}:</span>
                                            <span
                                                class="text-sky-500">{{ \Carbon\Carbon::parse($_vacation->start_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.end_date') }}:</span>
                                            <span
                                                class="text-rose-500">{{ \Carbon\Carbon::parse($_vacation->end_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.return_work_date') }}:</span>
                                            <span
                                                class="text-green-500">{{ \Carbon\Carbon::parse($_vacation->return_work_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span
                                                class="flex-shrink-0 text-sm text-gray-500">{{ __('vacation::common.labels.vacation_days') }}:
                                            </span>
                                            <div
                                                class="relative flex items-center justify-center w-20 h-2 overflow-hidden rounded-lg bg-slate-200">
                                                <div class="absolute left-0 h-full bg-{{ $_vacation->remaining_color }}-500 shadow-sm"
                                                    style="width: {{ $_vacation->remaining_percentage }}%"></div>
                                            </div>
                                            <span
                                                class="z-10 text-sm font-medium text-slate-900">{{ $_vacation->remaining_days }}/{{ $_vacation->vacation_days_total }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-medium text-slate-900">
                                        {{ $_vacation->vacation_places }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.order_hash') }}:</span>
                                            @if (filled($_vacation->order_no))
                                                <a href="{{ route('orders', ['search' => ['order_no' => $_vacation->order_no]]) }}"
                                                    class="text-blue-600">{{ $_vacation->order_no }}</a>
                                            @elseif ($_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved')
                                                <button
                                                    wire:click="bindOperationalOrder('{{ $_vacation->id }}')"
                                                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-100">
                                                    {{ __('vacation::common.actions.bind_order') }}
                                                </button>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.given_by') }}:</span>
                                            <span class="text-black">{{ $_vacation->order_given_by }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('vacation::common.labels.given_date') }}:</span>
                                            <span class="text-black">{{ $_vacation->order_date ? \Carbon\Carbon::parse($_vacation->order_date)->format('d.m.Y') : '—' }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved' && blank($_vacation->order_no))
                                        <button wire:click="bindOperationalOrder('{{ $_vacation->id }}')"
                                            class="inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-amber-600 uppercase transition duration-300 rounded-lg bg-amber-50 hover:bg-amber-100 hover:text-amber-700"
                                            title="{{ __('vacation::common.actions.bind_order') }}">
                                            <x-icons.document-icon color="text-amber-500" hover="text-amber-700"></x-icons.document-icon>
                                        </button>
                                    @endif
                                    @if (filled($_vacation->order_no))
                                        <a href="{{ route('orders', ['search' => ['order_no' => $_vacation->order_no]]) }}"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-slate-100 hover:bg-slate-200 hover:text-gray-700">
                                            <x-icons.edit-icon color="text-slate-500" hover="text-slate-700"></x-icons.edit-icon>
                                        </a>
                                    @endif
                                    @can('export-vacations')
                                        @if (filled($_vacation->order_no))
                                        <button wire:click="printVacationDocument('{{ $_vacation->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-teal-50 hover:bg-teal-50 hover:text-gray-700">
                                            <x-icons.document-icon color="text-teal-500" hover="text-teal-600"></x-icons.document-icon>
                                        </button>
                                        @endif
                                    @endcan
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="count($this->getTableHeaders())"></x-table.empty>
                        @endforelse
                    </x-table.tbl>

                </div>
            </div>
        </div>

        <div class="mt-2">
            {{ $this->vacations->links() }}
        </div>
    </div>

    <x-datepicker :auto=false></x-datepicker>
</div>
