<div class="flex flex-col" x-data="personnelManager()" x-init="init()">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        @livewire('structure.sidebar')
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col space-y-4 px-6 py-4">
        {{-- header section --}}
        <div class="flex justify-between items-center">
            @include('partials.personnel.status-filters')
            @include('partials.personnel.action-buttons')
        </div>
        {{-- Position Filters --}}
        @include('partials.personnel.position-filters')

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-inherit border-b border-gray-200 shadow sm:rounded-xl">
                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($this->personnels as $key => $personnel)
                            <tr @class([
                                'relative',
                                'bg-rose-100' => !empty($personnel->leave_work_date),
                                'bg-white' => $personnel->hasActiveVacation || $personnel->hasActiveBusinessTrip,
                            ])>
                                <x-table.td>
                                    <div class="flex flex-col justify-between h-full absolute top-0 left-0">
                                        @if ($personnel->hasActiveVacation)
                                            @php
                                                $activeVacation = $personnel->hasActiveVacation;
                                                $vacationStart = $activeVacation->start_date;
                                                $vacationEnd = $activeVacation->return_work_date;
                                            @endphp
                                            <x-progress :startDate="$vacationStart" :endDate="$vacationEnd" color="emerald">
                                                {{ __('In vacation') }}
                                            </x-progress>
                                        @endif
                                        @if ($personnel->hasActiveBusinessTrip)
                                            @php
                                                $businessTrip = $personnel->hasActiveBusinessTrip;
                                                $startDate = $businessTrip->start_date;
                                                $endDate = $businessTrip->end_date;
                                            @endphp
                                            <x-progress :$startDate :$endDate color="rose">
                                                {{ __('In business trip') }}
                                            </x-progress>
                                        @endif
                                    </div>

                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($this->personnels->currentpage() - 1) * $this->personnels->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-blue-500">
                                            {{ $personnel->tabel_no }}
                                        </span>

                                        @if ($personnel->is_pending)
                                            <div
                                                class="text-xs font-medium rounded-lg shadow-sm px-4 py-1 flex space-x-2 items-center bg-teal-50 border border-teal-200 text-teal-500">
                                                <svg class="h-5 w-5 text-teal-500" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                <span class="">{{ __('Waiting for approval') }}</span>
                                            </div>
                                        @endif

                                        @if ($status == 'deleted')
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                    <span
                                                        class="text-black">{{ \Carbon\Carbon::parse($personnel->deleted_at)->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                    <span
                                                        class="text-black">{{ $personnel->personDidDelete->name }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center space-x-2 px-2">
                                        @if (!empty($personnel->photo))
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($personnel->photo) }}"
                                                alt=""
                                                class="flex-none rounded-xl object-cover w-14 h-14 border-2 shadow-lg border-zinc-200">
                                        @else
                                            <img src="{{ asset('assets/images/no-image.png') }}" alt=""
                                                class="flex-none rounded-xl object-cover w-14 h-14 border-2 shadow-lg border-zinc-200">
                                        @endif
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-sm font-medium text-zinc-900">
                                                {{ $personnel->fullname }}
                                            </span>
                                            <span
                                                class="text-sm w-max font-medium text-neutral-600 rounded-xl px-3 py-1 shadow-sm bg-neutral-200/70">
                                                {{ $personnel->gender == 1 ? __('Man') : __('Woman') }}
                                            </span>
                                            @if (!empty($personnel->latestRank))
                                                <span
                                                    class="text-sm font-medium rounded-xl px-3 py-1 shadow-sm w-max bg-green-950 text-yellow-400">
                                                    {{ $personnel->latestRank?->rank->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span
                                            class="text-zinc-900 text-sm font-medium">{{ $personnel->structure->name }}</span>
                                        <span
                                            class="text-zinc-600 text-sm font-medium">{{ $personnel->position->name }}</span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-table.cell-vertical title="Join date">
                                        {{ \Carbon\Carbon::parse($personnel->join_work_date)->format('d.m.Y') }}
                                    </x-table.cell-vertical>
                                    @if (!empty($personnel->leave_work_date))
                                        <x-table.cell-vertical title="Leave date" text-color="text-rose-500">
                                            {{ \Carbon\Carbon::parse($personnel->leave_work_date)->format('d.m.Y') }}
                                        </x-table.cell-vertical>
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true" style="text-align: center !important;">
                                    <div class="flex space-x-2 items-center">
                                        @if ($status != 'deleted')
                                            @can('edit-personnels')
                                                <a href="#"
                                                    wire:click="openSideMenu('edit-personnel',{{ $personnel->id }})"
                                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                                    <x-icons.profile-icon></x-icons.profile-icon>
                                                </a>
                                            @endcan
                                        @else
                                            @can('edit-personnels')
                                                <button wire:click="restoreData('{{ $personnel->tabel_no }}')"
                                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                                                    <x-icons.recover color="text-teal-500"
                                                        hover="text-teal-600"></x-icons.recover>
                                                </button>
                                            @endcan
                                        @endif

                                        @can('edit-personnels')
                                            <div class="relative inline-block text-left" x-data="{ showContextMenu: false, showTooltip: '' }">
                                                <div>
                                                    <button @click="showContextMenu = !showContextMenu"
                                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
                                                        <x-icons.settings-icon></x-icons.settings-icon>
                                                    </button>
                                                </div>
                                                <div x-show="showContextMenu"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 scale-90"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-100"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-90"
                                                    @click.outside="showContextMenu = false" @class([
                                                        'absolute right-0 z-10 mt-2 origin-bottom-right w-max rounded-md bg-white shadow-lg shadow-black/5 ring-1 ring-black ring-opacity-5 focus:outline-none',
                                                        'bottom-full' => $loop->index >= 1,
                                                        'top-full' => $loop->index < 1,
                                                    ])
                                                    role="menu" aria-orientation="vertical" aria-labelledby="menu-button"
                                                    tabindex="-1"
                                                >
                                                    <div class="flex items-center divide-x divide-neutral-100" role="none">
                                                        <button
                                                            wire:click="openSideMenu('show-files','{{ $personnel->tabel_no }}')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'files'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <x-icons.files-icon hover="text-blue-500"  />
                                                            <div x-show="showTooltip == 'files'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                {{ __('Files') }}
                                                            </div>
                                                        </button>
                                                        <a href="{{ route('print.personnel', $personnel->id) }}"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium  hover:bg-slate-100"
                                                            target="_blank"
                                                            @mouseover="showTooltip = 'print'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <x-icons.print-outline-icon hover="text-blue-500"  />
                                                            <div x-show="showTooltip == 'print'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                {{ __('Print') }}
                                                            </div>
                                                        </a>
                                                        <button
                                                            wire:click="openSideMenu('show-information','{{ $personnel->tabel_no }}')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'information'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <x-icons.profile-outline-icon hover="text-blue-500"  />
                                                            <div x-show="showTooltip == 'information'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                {{ __('Information') }}
                                                            </div>
                                                        </button>
                                                        <button
                                                            wire:click="printInfo('{{ $personnel->id }}')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'orders'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <x-icons.orders-icon hover="text-blue-500"  />
                                                            <div x-show="showTooltip == 'orders'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                {{ __('Orders') }}
                                                            </div>
                                                        </button>
                                                        <button
                                                            wire:click="openSideMenu('show-vacations','{{ $personnel->tabel_no }}')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'vacations'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <x-icons.vacation-outline-icon hover="text-blue-500"  />
                                                            <div x-show="showTooltip == 'vacations'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                {{ __('Vacations') }}
                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        @if ($status != 'deleted')
                                            @can('delete-personnels')
                                                <button wire:click="setDeletePersonnel('{{ $personnel->tabel_no }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700">
                                                    <x-icons.delete-icon></x-icons.delete-icon>
                                                </button>
                                            @endcan
                                        @else
                                            @can('edit-personnels')
                                                <button
                                                    wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                    wire:click="forceDeleteData('{{ $personnel->tabel_no }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700">
                                                    <x-icons.force-delete></x-icons.force-delete>
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
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
            {{ $this->personnels->links() }}
        </div>
    </div>

    @include('partials.personnel.modals')

    <x-datepicker :auto=false></x-datepicker>
</div>

@push('js')
    {{-- Alpine.js Component --}}
    <script>
        function personnelManager() {
            return {
                init() {
                    this.initializePaginator();
                    this.setupLivewireHooks();
                },

                initializePaginator() {
                    const paginator = document.querySelector('span[aria-current=page]>span');
                    if (paginator) {
                        paginator.classList.add('bg-blue-50', 'text-blue-600');
                    }
                },

                setupLivewireHooks() {
                    Livewire.hook('message.processed', (message, component) => {
                        const paginator = document.querySelector('span[aria-current=page]>span');
                        const updateMethods = [
                            'gotoPage', 'previousPage', 'nextPage', 'filterSelected'
                        ];
                        const updateEvents = [
                            'openSideMenu', 'closeSideMenu', 'personnelAdded',
                            'filterResetted', 'personnelWasDeleted'
                        ];
                        const updateNames = ['search'];

                        const hasUpdate = updateMethods.includes(message.updateQueue[0]?.payload?.method) ||
                            updateEvents.includes(message.updateQueue[0]?.payload?.event) ||
                            updateNames.includes(message.updateQueue[0]?.name);

                        if (hasUpdate && paginator) {
                            paginator.classList.add('bg-green-100', 'text-green-600');
                        }
                    });
                }
            }
        }
    </script>
@endpush
