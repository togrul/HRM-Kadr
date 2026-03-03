<div class="flex flex-col">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="personnel-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    <div class="">
        <div class="flex flex-col px-6 py-4 space-y-4">
            @php
                $personnels = $this->personnels;
                $status = $this->status;
                $rowStart = ($personnels->currentPage() - 1) * $personnels->perPage();
            @endphp
            {{-- header section --}}
            <div class="flex items-center justify-between">
                @include('partials.personnel.status-filters')
                @include('partials.personnel.action-buttons')
            </div>

            {{-- Position Filters --}}
            @include('partials.personnel.position-filters')

            <div class="relative min-h-[300px] overflow-x-auto">
                <div class="inline-block min-w-full align-middle sm:px-1">
                    <x-table.tbl :headers="$this->getTableHeaders()" title="{{ __('Personnels') }}">
                        @forelse ($personnels as $personnel)
                            @php
                                $rowNumber = $rowStart + $loop->iteration;
                                $rowActions = $this->rowActions($personnel);
                            @endphp
                            <tr wire:key="personnel-row-{{ $personnel->id }}-{{ $status ?? 'all' }}"
                                @class([
                                    'relative bg-white',
                                    'bg-rose-50/70' => !empty($personnel->leave_work_date),
                                ])>
                                <x-table.td>
                                    <div class="absolute top-0 left-0 flex flex-col justify-between h-full">
                                        @if ($personnel->active_vacation)
                                            <x-progress :startDate="$personnel->active_vacation_start" :endDate="$personnel->active_vacation_end" color="emerald">
                                                {{ __('In vacation') }}
                                            </x-progress>
                                        @endif
                                        @if ($personnel->active_business_trip)
                                            <x-progress :startDate="$personnel->active_business_trip_start" :endDate="$personnel->active_business_trip_end" color="rose">
                                                {{ __('In business trip') }}
                                            </x-progress>
                                        @endif
                                    </div>

                                    <span class="text-sm font-mono font-medium text-zinc-600">
                                        {{ $rowNumber }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-mono font-medium text-blue-500 w-max">
                                            {{ $personnel->tabel_no }}
                                        </span>

                                        @if ($personnel->is_pending)
                                            <div
                                                class="flex items-center px-2 py-0 space-x-2 text-xs font-medium text-amber-500 border border-amber-200 rounded-lg shadow-sm bg-amber-50">
                                                <x-icons.clock-icon size="w-5 h-5"
                                                    color="text-amber-600"></x-icons.clock-icon>
                                                <span class="uppercase">{{ __('Waiting for approval') }}</span>
                                            </div>
                                        @endif

                                        @if ($status == 'deleted')
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                    <span class="text-black">{{ $personnel->deleted_at_fmt }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                    <span class="text-black">{{ $personnel->deleted_by_name }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center space-x-2">
                                        <img src="{{ $personnel->photo_url }}" alt=""
                                            class="flex-none object-cover border shadow-sm rounded-md w-12 h-12 border-zinc-200">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-sm font-medium text-zinc-900">
                                                {{ $personnel->fullname }}
                                            </span>
                                            <div class="flex items-center space-x-1">
                                                <x-small-badge> {{ $personnel->gender_label }}</x-small-badge>
                                                @if ($personnel->rank_label !== '')
                                                    <x-small-badge
                                                        mode="green">{{ $personnel->rank_label }}</x-small-badge>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-zinc-900">
                                            {{ $personnel->structure_path }}
                                        </span>
                                        <span
                                            class="text-sm font-medium text-zinc-600">{{ $personnel->position_label }}</span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-table.cell-vertical title="Join date">
                                        {{ $personnel->join_work_date_fmt }}
                                    </x-table.cell-vertical>
                                    @if (!empty($personnel->leave_work_date))
                                        <x-table.cell-vertical title="Leave date" text-color="text-rose-500">
                                            {{ $personnel->leave_work_date_fmt }}
                                        </x-table.cell-vertical>
                                    @endif
                                </x-table.td>

                                <x-personnel.row-actions :actions="$rowActions" :force-up="$loop->last" />
                            </tr>
                        @empty
                            <x-table.empty :rows="count($this->getTableHeaders())"></x-table.empty>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
        <div class="border-t border-zinc-200 px-6 py-3">
            {{ $personnels->links() }}
        </div>
    </div>

    @include('partials.personnel.modals')

    <x-datepicker :auto=false></x-datepicker>
</div>
