<div class="flex flex-col" x-data x-init="paginator = document.querySelector('span[aria-current=page]>span');
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
})">
    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col">
            <x-label for="filter.fullname">{{ __('Fullname') }}</x-label>
            <x-livewire-input mode="gray" name="filter.fullname" wire:model="filter.fullname"></x-livewire-input>
        </div>
        <div class="flex flex-col w-full space-y-1">
            <x-label for="filter.gender">{{ __('Gender') }}</x-label>
            <div class="flex space-x-2">
                @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                    <label class="inline-flex items-center w-full px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.gender" wire:model="filter.gender"
                            value="{{ $value }}">
                        <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex items-center space-x-2">
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
            <div class="flex items-center space-x-1">
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

    <div class="flex flex-col px-6 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-col items-center justify-between px-2 py-2 bg-white sm:flex-row filter rounded-xl">
                <x-filter.nav>
                    <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    @foreach ($_appeal_statuses as $_status)
                        <x-filter.item wire:click.prevent="setStatus({{ $_status->id }})" :active="$status === $_status->id">
                            {{ $_status->name }}
                        </x-filter.item>
                    @endforeach
                    {{--                 @can('manage-candidates') --}}
                    <x-filter.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('Deleted') }}
                    </x-filter.item>
                    {{--                 @endcan --}}
                </x-filter.nav>
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('create', App\Models\Candidate::class)
                        <button wire:click="openSideMenu('add-candidate')"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-blue-50"
                            type="button">
                            @include('components.icons.add-file')
                        </button>
                    @endcan
                    @can('export', App\Models\Candidate::class)
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                        <button
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-red-50"
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

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($_candidates as $key => $_candidate)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($_candidates->currentpage() - 1) * $_candidates->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_candidate->fullname_max }}
                                        </span>
                                        @if (!empty($_candidate->deleted_at))
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                    <span
                                                        class="text-black">{{ \Carbon\Carbon::parse($_candidate->deleted_at)->format('d-m-Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                    <span
                                                        class="text-black">{{ $_candidate->personDidDelete->name }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                </x-table.td>

                                <x-table.td>
                                    <span
                                        class="px-2 py-2 text-sm font-medium text-gray-900 rounded-lg bg-slate-100">{{ $_candidate->structure->name }}</span>
                                </x-table.td>

                                <x-table.td>
                                    @php
                                        $_status_color = [
                                            0 => 'slate',
                                            1 => 'gray',
                                            2 => 'rose',
                                            3 => 'orange',
                                            4 => 'blue',
                                            5 => 'green',
                                        ];
                                    @endphp
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center space-x-1">
                                            <span
                                                class="text-sm font-medium text-gray-500">{{ __('Knowledge') }}:</span>
                                            <span
                                                class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_status_color[$_candidate->knowledge_test] }}-100 text-{{ $_status_color[$_candidate->knowledge_test] }}-500">{{ $_candidate->knowledge_test }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span
                                                class="text-sm font-medium text-gray-500">{{ __('Physical fitness') }}:</span>
                                            <span
                                                class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_status_color[$_candidate->physical_fitness_exam] }}-100 text-{{ $_status_color[$_candidate->physical_fitness_exam] }}-500">{{ $_candidate->physical_fitness_exam }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <x-table.cell-vertical title="Appeal date">
                                            {{ \Carbon\Carbon::parse($_candidate->appeal_date)->format('d.m.Y') }}
                                        </x-table.cell-vertical>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-status :status-id="$_candidate->status_id" :label="$_candidate->status->name"></x-status>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        @can('update', $_candidate)
                                            <a href="#"
                                                wire:click="openSideMenu('edit-candidate',{{ $_candidate->id }})"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700">
                                                @include('components.icons.profile-icon')
                                            </a>
                                        @endcan
                                    @else
                                        @role('Admin')
                                            <button wire:click="restoreData('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-teal-50 hover:text-gray-700">
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
                                        @can('delete', $_candidate)
                                            <button wire:click="setDeleteCandidate('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700">
                                                @include('components.icons.delete-icon')
                                            </button>
                                        @endcan
                                    @else
                                        @can('delete', $_candidate)
                                            <button wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                wire:click="forceDeleteData('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700">
                                                @include('components.icons.force-delete')
                                            </button>
                                        @endcan
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
            {{ $_candidates->links() }}
        </div>
    </div>

    <x-side-modal>
        @can('create', App\Models\Candidate::class)
            @if ($showSideMenu == 'add-candidate')
                @livewire('candidates.add-candidate')
            @endif
        @endcan

        @if ($showSideMenu === 'edit-candidate')
            <livewire:candidates.edit-candidate :candidateModel="$modelName" />
        @endif
    </x-side-modal>

    @can('delete', App\Models\Candidate::class)
        <div>
            @livewire('candidates.delete-candidate')
        </div>
    @endcan

    <x-datepicker :auto=false></x-datepicker>
</div>
