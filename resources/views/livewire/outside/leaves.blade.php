<div
    x-data="leavesIndex()"
    x-init="init()"
    class="flex flex-col"
>
    {{-- filter --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 lg:grid-cols-4 px-6 py-4">
        <div class="flex flex-col">
            <x-label for="filter.fullname">{{ __('Fullname') }}</x-label>
            <x-livewire-input
                    mode="gray"
                    name="filter.fullname"
                    wire:model="filter.fullname"
                    wire:model.debounce.400ms="filter.fullname"
            ></x-livewire-input>
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
            <x-button
                    mode="primary"
                    wire:click="searchFilter"
                    wire:loading.attr="disabled"
                    wire:target="searchFilter"
            >{{ __('Search') }}</x-button>
            <x-button
                    mode="black"
                    wire:click="resetFilter"
                    wire:loading.attr="disabled"
                    wire:target="resetFilter"
            >{{ __('Reset') }}</x-button>
        </div>
    </div>
    {{-- end filter --}}

    {{-- start --}}
    <div class="flex flex-col space-y-4 px-6 py-4">
        {{-- start  --}}
        <div class="flex justify-between items-center">
             <div class="filter bg-white py-2 px-2 rounded-xl">
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

            <div class="flex space-x-4">
                 {{-- @can('add-candidates') --}}
                 <button wire:click="openSideMenu('add-leave')"
                        class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50"
                        type="button"
                >
                        @include('components.icons.add-file')
                 </button>
                {{-- @endcan --}}
                {{-- @can('export-candidates') --}}
                <button wire:click.prevent="exportExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportExcel"
                        class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50"
                        type="button"
                >
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
                {{-- @endcan --}}
            </div>
        </div>
        {{-- end --}}
    </div>
    {{-- end --}}

    <div class="flex flex-col">
    {{-- start table --}}
    <div class="relative min-h-[300px] overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($permits as $key => $leave)
                            <tr wire:key="leave-row-{{ $leave->id }}">
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($permits->currentpage() - 1) * $permits->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-neutral-900">
                                            {{ $leave->personnel->fullname_max }}
                                        </span>
                                        <span class="text-sm font-medium text-neutral-600/80">
                                            {{ $leave->personnel->structure->name }}
                                        </span>
                                        <span class="text-sm font-medium text-emerald-600">
                                            {{ $leave->personnel->position->name }}
                                        </span>
                                         @if ($leave->deleted_at)
                                           <div class="flex items-center space-x-1 text-xs font-medium">
                                                <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                <span class="text-black">{{ \Carbon\Carbon::parse($leave->deleted_at)->format('d-m-Y H:i') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-status :status-id="$leave->leave_type_id * 10" :label="$leave->leaveType->name"></x-status>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                      <span class="text-sm font-medium whitespace-normal flex-wrap">{{ $leave->periodLabel }}</span>
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
                                        @if ($leave->status_id <> 10 && $leave->latestLog)
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
                                        <span class="text-sm font-medium text-gray-700 break-words">
                                            {{ $leave->document_path }}
                                        </span>
                                        @if($leave->canBeApprovedBy(auth()->user()))
                                        <div class="flex items-center space-x-2">
                                            <button
                                                wire:click="approvePermit({{ $leave->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="approvePermit({{ $leave->id }})"
                                            >
                                                <x-icons.check-icon
                                                    color="text-green-500"
                                                    hover="text-green-600"
                                                    size="w-8 h-8"
                                                />
                                            </button>
                                            <button
                                                wire:click="rejectPermit({{ $leave->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="rejectPermit({{ $leave->id }})"
                                            >
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

                                <x-table.td isButton>
                                    @if ($status != 'deleted')
                                        {{-- @can('edit-candidates') --}}
                                            <button
                                                wire:click="openSideMenu('edit-leave',{{ $leave->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="openSideMenu"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700"
                                            >
                                                @include('components.icons.document-icon')
                                            </button>
                                        {{-- @endcan --}}
                                    @else
                                        @role('Admin')
                                            <button wire:click="restoreData('{{ $leave->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restoreData('{{ $leave->id }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                                            >
                                                @include('components.icons.recover', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ])
                                            </button>
                                        @endrole
                                    @endif
                                </x-table.td>

                                <x-table.td isButton>
                                    @if ($status != 'deleted')
                                        {{-- @can('delete-leaves') --}}
                                            <button wire:click="setDeleteLeave('{{ $leave->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="setDeleteLeave('{{ $leave->id }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                            >
                                                @include('components.icons.delete-icon')
                                            </button>
                                        {{-- @endcan --}}
                                    @else
                                        {{-- @can('delete-leaves') --}}
                                            <button wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                    wire:click="forceDeleteData('{{ $leave->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="forceDeleteData('{{ $leave->id }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                            >
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
        {{-- end table --}}
        <div class="mt-2" x-ref="pager">
            {{ $permits->links() }}
        </div>
    </div>

    <x-side-modal>
        {{-- @can('add-candidates') --}}
            @if ($showSideMenu == 'add-leave')
                 <livewire:outside.add-leave />
            @endif
        {{-- @endcan --}}

        {{-- @can('edit-candidates') --}}
            @if ($showSideMenu == 'edit-leave')
                <livewire:outside.edit-leave :leaveModel="$modelName" />
            @endif
        {{-- @endcan --}}
    </x-side-modal>

    {{-- @can('delete-candidates') --}}
        <div>
            @auth
                @livewire('outside.delete-leave')
            @endauth
        </div>
    {{-- @endcan --}}

    <x-datepicker :auto=false></x-datepicker>
</div>

@push('js')
<script>
    function leavesIndex() {
        return {
            init() {
            // first paint
            this.highlightPager();

            // only react to specific Livewire actions/events
            Livewire.hook('message.processed', (m, c) => {
                    const method = m.updateQueue?.[0]?.payload?.method;
                    const event  = m.updateQueue?.[0]?.payload?.event;
                    const name   = m.updateQueue?.[0]?.name;

                    const methods = ['gotoPage','previousPage','nextPage','filterSelected'];
                    const events  = ['openSideMenu','closeSideMenu','leaveAdded','filterResetted','leaveWasDeleted', 'leaveApproved', 'leaveRejected'];
                    const names   = ['search'];

                    if (methods.includes(method) || events.includes(event) || names.includes(name)) {
                    this.highlightPager(true);
                }
            });
            },
                highlightPager(isUpdate = false) {
                const el = document.querySelector('span[aria-current=page]>span');
                if (!el) return;
                el.classList.remove('bg-blue-50','text-blue-600','bg-green-100','text-green-600');
                el.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
            }
        }
    }
</script>
@endpush
