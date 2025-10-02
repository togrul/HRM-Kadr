<div
    class="flex flex-col"
    x-data
    x-init="paginator = document.querySelector('span[aria-current=page]>span');
    if (paginator != null) {
        paginator.classList.add('bg-blue-50', 'text-blue-600')
    }
    Livewire.hook('message.processed', (message, component) => {
        const paginator = document.querySelector('span[aria-current=page]>span')
        if (
            ['gotoPage', 'previousPage', 'nextPage', 'filterSelected'].includes(message.updateQueue[0].payload.method) || ['openSideMenu', 'closeSideMenu', 'candidateAdded', 'filterResetted', 'candidateWasDeleted'].includes(message.updateQueue[0].payload.event) || ['search'].includes(message.updateQueue[0].name)
        ) {
            if (paginator != null) {
                paginator.classList.add('bg-green-100', 'text-green-600')
            }
        }
    })"
>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 lg:grid-cols-4 px-6 py-4">
        <div class="flex flex-col">
            <x-label for="filter.fullname">{{ __('Fullname') }}</x-label>
            <x-livewire-input mode="gray" name="filter.fullname" wire:model="filter.fullname"></x-livewire-input>
        </div>
        <div class="flex flex-col space-y-1 w-full">
            <x-label for="filter.gender">{{ __('Gender') }}</x-label>
            <div class="flex space-x-2">
                @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2 w-full">
                        <input type="radio" class="form-radio" name="filter.gender" wire:model="filter.gender"
                            value="{{ $value }}">
                        <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex space-x-2 items-center">
            <div class="flex flex-col">
                <x-label for="filter.results">{{ __('Test results') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="filter.results"
                    wire:model="filter.results"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.age">{{ __('Age') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="filter.age"
                    wire:model="filter.age"></x-livewire-input>
            </div>
        </div>

        <div class="flex flex-col">
            <x-label for="filter.appeal_date">{{ __('Appeal date') }}</x-label>
            <div class="flex space-x-1 items-center">
                <x-pikaday-input mode="gray" name="filter.appeal_date.min" format="Y-MM-DD"
                    wire:model="filter.appeal_date.min">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('filter.appeal_date.min', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.appeal_date.max" format="Y-MM-DD"
                    wire:model="filter.appeal_date.max">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('filter.appeal_date.max', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>
        <div class="flex items-end space-x-2">
            <x-button mode="primary" wire:click="searchFilter">{{ __('Search') }}</x-button>
            <x-button mode="black" wire:click="resetFilter">{{ __('Reset') }}</x-button>
        </div>
    </div>

    <div class="flex flex-col space-y-4 px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
                <x-filter.nav>
                    <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    @foreach ($_appeal_statuses as $_status)
                        <x-filter.item wire:click.prevent="setStatus({{ $_status->id }})" :active="$status === $_status->id">
                            {{ $_status->name }}
                        </x-filter.item>
                    @endforeach
                    {{-- @can('manage-candidates') --}}
                    <x-filter.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('Deleted') }}
                    </x-filter.item>
                    {{-- @endcan --}}
                </x-filter.nav>
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('add-candidates')
                        <button wire:click="openSideMenu('add-candidate')"
                            class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50"
                            type="button">
                            @include('components.icons.add-file')
                        </button>
                    @endcan
                    @can('export-candidates')
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                        <button
                            class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50"
                            type="button">
                            @include('components.icons.print-file', [
                                'color' => 'text-rose-500',
                                'hover' => 'text-rose-600',
                                'size' => 'w-8 h-8',
                            ])
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="relative min-h-[300px] overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($permits as $key => $leave)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($permits->currentpage() - 1) * $permits->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-0">
                                        <span class="text-sm font-medium text-neutral-900">
                                            {{ $leave->personnel->fullname_max }}
                                        </span>
                                        <span class="text-sm font-medium text-neutral-600/80">{{ $leave->personnel->structure->name }}</span>
                                          <span class="text-sm font-medium text-emerald-600">{{ $leave->personnel->position->name }}</span>
                                        @if (!empty($leave->deleted_at))
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                    <span class="text-black">{{ \Carbon\Carbon::parse($leave->deleted_at)->format('d-m-Y H:i') }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-status :status-id="$leave->leave_type_id * 10" :label="$leave->leaveType->name"></x-status>
                                </x-table.td>

                                  <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                      <div class="flex items-center text-sm font-medium whitespace-normal flex-wrap">
                                            <span class="text-neutral-600">{{ \Carbon\Carbon::parse($leave->starts_at)->format('d.m.Y') }}</span>
                                            <span>-</span>
                                            <span>{{ \Carbon\Carbon::parse($leave->ends_at)->format('d.m.Y') }}</span>
                                      </div>
                                      <span class="text-sm font-medium text-neutral-700/80">({{ $leave->total_days }} {{ __('day') }})</span>
                                    </div>
                                </x-table.td>

                               <x-table.td>
                                    <span class="text-sm font-medium text-gray-700 whitespace-normal flex w-[160px] bg-white rounded-xl shadow-lg px-3 py-2">
                                        {{ $leave->reason }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <x-status
                                            :status-id="$leave->status_id"
                                            :label="$leave->status->name"
                                            type="order"
                                            design="modern"
                                        ></x-status>
                                        @if ($leave->status_id <> 10)
                                        <div class="flex flex-col">
                                             <div class="flex items-center space-x-1 text-sm">
                                                <x-icons.user-simple-icon size="w-5 h-5" color="text-neutral-500"/>
                                                <span class="text-black">{{ $leave->latestLog->changedBy->fullname }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1 text-sm">
                                                <x-icons.clock-icon size="w-5 h-5" color="text-neutral-500" />
                                                <span class="text-black">
                                                    {{ \Carbon\Carbon::parse($leave->latestLog->changed_at)->format('d.m.Y H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-2">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $leave->document_path }}
                                        </span>
                                        @if($leave->status_id == \App\Enums\OrderStatusEnum::PENDING->value )
                                        <div class="flex items-center space-x-2">
                                            <button class="appearance-none" wire:click="approvePermit({{ $leave->id }})">
                                                <x-icons.check-icon
                                                    color="text-green-500"
                                                    hover="text-green-600"
                                                    size="w-8 h-8"
                                                />
                                            </button>
                                            <button class="appearance-none" wire:click="rejectPermit({{ $leave->id }})">
                                                <x-icons.x-circle-icon
                                                    color="text-rose-500"
                                                    hover="text-rose-600"
                                                    size="w-8 h-8"
                                                />
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        {{-- @can('edit-candidates') --}}
                                            <button
                                                wire:click="openSideMenu('edit-leave',{{ $leave->id }})"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                                @include('components.icons.document-icon')
                                            </button>
                                        {{-- @endcan --}}
                                    @else
                                        @role('Admin')
                                            <button wire:click="restoreData('{{ $leave->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700">
                                                @include('components.icons.recover', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ])
                                            </button>
                                        @endrole
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        {{-- @can('delete-leaves') --}}
                                            <button wire:click="setDeleteLeave('{{ $leave->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700">
                                                @include('components.icons.delete-icon')
                                            </button>
                                        {{-- @endcan --}}
                                    @else
                                        {{-- @can('delete-leaves') --}}
                                            <button wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                wire:click="forceDeleteData('{{ $leave->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700">
                                                @include('components.icons.force-delete')
                                            </button>
                                        {{-- @endcan --}}
                                    @endif
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
            {{ $permits->links() }}
        </div>
    </div>

    {{-- <x-side-modal>
        @can('add-candidates')
            @if ($showSideMenu == 'add-candidate')
                @livewire('candidates.add-candidate')
            @endif
        @endcan

        @can('edit-candidates')
            @if ($showSideMenu == 'edit-candidate')
                <livewire:candidates.edit-candidate :candidateModel="$modelName" />
            @endif
        @endcan
    </x-side-modal>

    @can('delete-candidates')
        <div>
            @livewire('candidates.delete-candidate')
        </div>
    @endcan

    <x-datepicker :auto=false></x-datepicker> --}}
</div>
