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
        {{-- ===================== Premium header ===================== --}}
        <x-page-header :title="__('vacation::common.titles.vacations')" :count="$this->vacations->total()">
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </x-slot:icon>
            <x-slot:actions>
                @can('review-self-service-requests')
                    <x-ui.self-service-review-link />
                @endcan
                @can('export-vacations')
                    <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel" title="{{ __('vacation::common.actions.export_excel') }}">
                        <x-icons.excel-icon />
                    </x-pill-button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="flex items-center">
            <div class="flex flex-row items-center justify-start px-2 py-2 space-x-2 rounded-xl">
                <x-label>{{ __('vacation::common.labels.year') }} </x-label>
                <select name="selectedYear" id="selectedYear" wire:model.live="selectedYear" @disabled(!empty($filter['date']['min'] ?? null) || !empty($filter['date']['max'] ?? null))
                    class="block w-full text-base text-white rounded-md bg-neutral-800 border-slate-600 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                    @endforeach
                </select>
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
                            @php
                                $startDate = \Carbon\Carbon::parse($_vacation->start_date);
                                $endDate = \Carbon\Carbon::parse($_vacation->end_date);
                                $returnWorkDate = \Carbon\Carbon::parse($_vacation->return_work_date);
                                $isActiveVacation = $_vacation->is_active_vacation;
                                $statusCardClasses = $isActiveVacation
                                    ? 'border-emerald-200/80 bg-gradient-to-r from-emerald-50 via-white to-teal-50 shadow-emerald-100/60'
                                    : 'border-zinc-200 bg-gradient-to-r from-zinc-50 via-white to-slate-50 shadow-zinc-100/70';
                                $statusDotClasses = $isActiveVacation
                                    ? 'bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.12)]'
                                    : 'bg-zinc-500 shadow-[0_0_0_4px_rgba(113,113,122,0.10)]';
                                $statusTextClasses = $isActiveVacation ? 'text-emerald-700' : 'text-zinc-700';
                                $statusPillClasses = $isActiveVacation
                                    ? 'ring-emerald-100 text-emerald-700'
                                    : 'ring-zinc-200 text-zinc-600';
                                $progressRailClasses = $isActiveVacation ? 'bg-white ring-emerald-100' : 'bg-white ring-zinc-200';
                            @endphp
                            <tr>
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
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span
                                            class="px-2 py-1 text-sm font-medium text-slate-600 rounded-lg bg-slate-100">
                                            {{ $_vacation->personnel?->structure?->name }}
                                        </span>
                                        <span
                                            class="px-2 py-1 text-sm font-medium rounded-lg text-teal-600 bg-slate-100">
                                            {{ $_vacation->personnel?->position_label }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="w-full max-w-sm rounded-2xl border px-3 py-1 shadow-sm {{ $statusCardClasses }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center gap-2 text-xs font-semibold {{ $statusTextClasses }}">
                                                <span class="h-2.5 w-2.5 rounded-full {{ $statusDotClasses }}"></span>
                                                {{ $isActiveVacation ? __('vacation::common.labels.in_vacation') : __('vacation::common.labels.at_work') }}
                                            </div>
                                            <span class="rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusPillClasses }}">
                                                {{ $_vacation->remaining_days }}/{{ $_vacation->vacation_days_total }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center gap-2 text-xs font-medium text-slate-500">
                                            <span>{{ $startDate->format('d.m.Y') }}</span>
                                            <span class="text-slate-300">→</span>
                                            <span>{{ $endDate->format('d.m.Y') }}</span>
                                        </div>

                                        <div class="mt-1 grid grid-cols-2 gap-2 text-[11px] font-medium text-slate-500">
                                            <div class="rounded-xl bg-white/80 px-2 py-1 ring-1 ring-black/5">
                                                <span class="block text-[10px] uppercase font-semibold tracking-tight text-slate-400">{{ __('vacation::common.labels.duration') }}</span>
                                                <span class="mt-0.5 block text-[13px] font-semibold text-slate-800">{{ $_vacation->duration }} {{ __('vacation::common.labels.day') }}</span>
                                            </div>
                                            <div class="rounded-xl bg-white/80 px-2 py-1 ring-1 ring-black/5">
                                                <span class="block text-[10px] uppercase font-semibold tracking-tight text-slate-400">{{ __('vacation::common.labels.return_work_date') }}</span>
                                                <span class="mt-0.5 block text-[13px] font-semibold text-slate-800">{{ $returnWorkDate->format('d.m.Y') }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full ring-1 {{ $progressRailClasses }}">
                                            <div class="h-full rounded-full bg-{{ $_vacation->remaining_color }}-500"
                                                style="width: {{ $_vacation->remaining_percentage }}%"></div>
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
