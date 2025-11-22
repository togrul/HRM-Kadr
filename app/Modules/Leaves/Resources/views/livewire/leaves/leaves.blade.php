<div
    x-data="leavesIndex()"
    x-init="init()"
    class="flex flex-col"
>
    {{-- filter --}}
       <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
       <x-ui.select-dropdown
                label="{{ __('Leave type') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.leave_type_id"
                :model="$this->leaveTypes"
        />
        <div class="flex flex-col">
            <x-label for="filter.fullname">{{ __('Fullname') }}</x-label>
            <x-livewire-input
                    mode="gray"
                    name="filter.fullname"
                    wire:model="filter.fullname"
            ></x-livewire-input>
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
       <div class="flex flex-col">
            <x-label for="filter.reason">{{ __('Reason') }}</x-label>
            <x-livewire-input mode="gray" type="text" name="filter.reason"
                    wire:model="filter.reason"></x-livewire-input>
      </div>

        <div class="flex flex-col">
            <x-label for="filter.appeal_date">{{ __('Dates') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-pikaday-input mode="gray" name="filter.starts_at" format="Y-MM-DD"
                    wire:model="filter.starts_at">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('filter.starts_at', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.ends_at" format="Y-MM-DD"
                    wire:model="filter.ends_at">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('filter.ends_at', $el.value);
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
    <div class="flex flex-col px-6 py-4 space-y-4">
        {{-- start  --}}
        <div class="flex items-center justify-between">
             <div class="px-2 py-2 bg-white filter rounded-xl">
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
                        class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-blue-50"
                        type="button"
                >
                        @include('components.icons.add-file')
                 </button>
                {{-- @endcan --}}
                {{-- @can('export-candidates') --}}
                <button wire:click.prevent="exportExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportExcel"
                        class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                        type="button"
                >
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
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-status :status-id="$leave->leave_type_id * 10" :label="$leave->leaveType->name"></x-status>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                      <span class="flex-wrap text-sm font-medium whitespace-normal">{{ $leave->periodLabel }}</span>
                                      <span class="text-sm font-medium text-neutral-700/80">({{ $leave->total_days }} {{ __('day') }})</span>
                                    </div>
                                      @if ($leave->deleted_at)
                                          <hr class="my-1" >
                                           <div class="flex items-center mt-2 space-x-1 text-xs font-medium">
                                                <span class="text-rose-500">{{ __('Deleted date') }}:</span>
                                                <span class="text-black">{{ \Carbon\Carbon::parse($leave->deleted_at)->format('d-m-Y H:i') }}</span>
                                            </div>
                                        @endif
                                </x-table.td>

                               <x-table.td>
                                    <span class="text-sm font-medium text-gray-700 whitespace-normal flex w-[160px] bg-white rounded-xl shadow-lg px-3 py-2">
                                        {{ $leave->reason }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center gap-2">
                                            <x-status
                                                :status-id="$leave->status_id"
                                                :label="$leave->status->name"
                                                type="order"
                                                design="modern"
                                            ></x-status>
                                            @if($leave?->latestLog?->comment)
                                            <div class="relative top-1" x-data="{showComment: false}">
                                                <button @click="showComment = true" class="appearance-none">
                                                    <x-icons.comment-icon color="text-indigo-500" hover="text-indigo-700" />
                                                </button>
                                                 <div @class([
                                                    'absolute px-3 py-2 rounded-md shadow-2xl bg-white border border-neutral-200/80 w-[200px] z-10',
                                                    'bottom-10' => $loop->last,
                                                    'top-6' => !$loop->last,
                                                ])
                                                    x-show="showComment"
                                                    x-transition.opacity
                                                    x-cloak
                                                    x-on:keydown.window.escape.prevent="showComment = false"
                                                    x-on:click.outside = "showComment = false"
                                                >
                                                    <span class="text-sm text-neutral-600">
                                                        {{ $leave->latestLog->comment }}
                                                    </span>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

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

                                <x-table.td standartWidth>
                                    <div class="flex flex-col flex-wrap space-y-2 whitespace-normal">
                                        @if($leave->document_path)
                                        <a
                                            href="/{{ $leave->document_path }}"
                                            target="_blank"
                                            class="flex flex-col gap-1 text-sm font-medium"
                                            style="word-break: break-word;"
                                        >
                                            <x-icons.files-icon color="text-blue-500" hover="text-sky-300" />
                                            <span class="text-blue-500">{{ __('File') }}</span>
                                        </a>
                                        @endif
                                        @if($leave->canBeApprovedBy(auth()->user()))
                                        <div class="flex items-center space-x-2">
                                            <button
                                                wire:loading.attr="disabled"
                                                @click="$dispatch('comment:open', { action: 'APPROVED', leaveId: @js($leave->id) })"
                                            >
                                                <x-icons.check-icon
                                                    color="text-green-500"
                                                    hover="text-green-600"
                                                    size="w-8 h-8"
                                                />
                                            </button>
                                            <button
                                                wire:loading.attr="disabled"
                                                @click="$dispatch('comment:open', { action: 'CANCELLED', leaveId: @js($leave->id) })"
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
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700"
                                            >
                                                @include('components.icons.document-icon')
                                            </button>
                                        {{-- @endcan --}}
                                    @else
                                        @role('Admin')
                                            <button wire:click="restoreData('{{ $leave->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restoreData('{{ $leave->id }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-teal-50 hover:text-gray-700"
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
                                        <button
                                            wire:click="setDeleteLeave('{{ $leave->id }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="setDeleteLeave('{{ $leave->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700"
                                        >
                                            @include('components.icons.delete-icon')
                                        </button>
                                        {{-- @endcan --}}
                                    @else
                                        {{-- @can('delete-leaves') --}}
                                        <button
                                            wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                            wire:click="forceDeleteData('{{ $leave->id }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="forceDeleteData('{{ $leave->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
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

    <div class="" x-data>
        @livewire('ui.confirmation.add-comment')
    </div>

    <x-side-modal>
        {{-- @can('add-candidates') --}}
            @if ($showSideMenu == 'add-leave')
                <livewire:leaves.add-leave />
            @endif
        {{-- @endcan --}}

        {{-- @can('edit-candidates') --}}
            @if ($showSideMenu == 'edit-leave')
                <livewire:leaves.edit-leave :leaveModel="$modelName" />
            @endif
        {{-- @endcan --}}
    </x-side-modal>

    {{-- @can('delete-candidates') --}}
        <div>
            @auth
                @livewire('leaves.delete-leave')
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
                    const events  = ['openSideMenu','closeSideMenu','leaveAdded','leaveUpdated','filterResetted','leaveWasDeleted', 'leaveApproved', 'leaveRejected'];
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
