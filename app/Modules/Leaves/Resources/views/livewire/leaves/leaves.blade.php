<div
    x-data="leavesIndex()"
    x-init="init()"
    class="flex flex-col"
>
    {{-- ===================== Premium header ===================== --}}
    <div class="px-4 pt-4 sm:px-6">
        <x-page-header :title="__('leaves::common.titles.leaves')" :count="$permits->total()" :count-label="__('leaves::common.labels.all')">
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
            </x-slot:icon>
            <x-slot:actions>
                @can('create', App\Models\Leave::class)
                    <x-pill-button variant="primary" wire:click="openAddLeaveModal" wire:loading.attr="disabled" wire:target="openAddLeaveModal">
                        <x-icons.add-file color="text-white" hover="text-white" size="w-5 h-5" />
                        {{ __('leaves::common.actions.add_leave') }}
                    </x-pill-button>
                @endcan
                @can('export', App\Models\Leave::class)
                    <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        title="{{ __('leaves::common.actions.export_excel') }}" aria-label="{{ __('leaves::common.actions.export_excel') }}">
                        <x-icons.excel-icon />
                    </x-pill-button>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </div>

    {{-- filter --}}
       <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
       <x-ui.select-dropdown
                label="{{ __('leaves::common.labels.leave_type') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.leave_type_id"
                :model="$this->leaveTypes"
        />
        <div class="flex flex-col">
            <x-label for="filter.fullname">{{ __('leaves::common.labels.fullname') }}</x-label>
            <x-livewire-input
                    mode="gray"
                    name="filter.fullname"
                    wire:model="filter.fullname"
            ></x-livewire-input>
        </div>
        <div class="flex flex-col w-full space-y-1">
            <x-label for="filter.gender">{{ __('leaves::common.labels.gender') }}</x-label>
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
            <x-label for="filter.reason">{{ __('leaves::common.labels.reason') }}</x-label>
            <x-livewire-input mode="gray" type="text" name="filter.reason"
                    wire:model="filter.reason"></x-livewire-input>
      </div>

        <div class="flex flex-col">
            <x-label for="filter.appeal_date">{{ __('leaves::common.labels.dates') }}</x-label>
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
            >{{ __('leaves::common.labels.search') }}</x-button>
            <x-button
                    mode="black"
                    wire:click="resetFilter"
                    wire:loading.attr="disabled"
                    wire:target="resetFilter"
            >{{ __('leaves::common.labels.reset') }}</x-button>
        </div>
    </div>
    {{-- end filter --}}

    {{-- start --}}
    <div class="flex flex-col px-6 py-4 space-y-4">
        @if (!empty($stats))
            <div class="grid w-full gap-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($stats as $type => $row)
                    <x-surface-card :title="$type" class="" icon="icons.calendar-icon">
                      @php
                          $equivalentDays = rtrim(rtrim(number_format((float) $row['total_days'], 1, '.', ''), '0'), '.');
                      @endphp
                      <div class="flex items-center justify-between text-slate-800">
                        <div class="flex items-baseline space-x-1">
                            <span class="text-lg font-bold font-title">{{ $equivalentDays }}</span>
                            <span class="text-[12px] font-medium font-mono text-gray-500 uppercase">{{ __('leaves::common.labels.day_equivalent') }}</span>
                        </div>
                        <div class="flex items-baseline space-x-1 text-emerald-700">
                            <span class="text-lg font-bold font-title">{{ $row['count'] }}</span>
                            <span class="text-[12px] font-medium font-mono text-emerald-600 uppercase">{{ __('leaves::common.labels.request') }}</span>
                        </div>
                    </div>
                    </x-surface-card>
                @endforeach
            </div>
        @endif
        {{-- start  --}}
        <div class="flex items-center">
             <div class="px-2 py-2 bg-white filter rounded-xl">
                <x-filter.nav>
                    <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('leaves::common.labels.all') }}
                    </x-filter.item>
                    @foreach ($_appeal_statuses as $_status)
                        <x-filter.item wire:click.prevent="setStatus({{ $_status->id }})" :active="$status === $_status->id">
                            {{ $_status->name }}
                        </x-filter.item>
                    @endforeach
                    @can('delete', App\Models\Leave::class)
                        <x-filter.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                            {{ __('leaves::common.labels.deleted') }}
                        </x-filter.item>
                    @endcan
                </x-filter.nav>
           </div>
        </div>
        {{-- end --}}
    </div>
    {{-- end --}}

    <div class="flex flex-col">
    {{-- start table --}}
    <div class="relative min-h-[300px] overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="$this->getTableHeaders()" title="{{ __('leaves::common.titles.leaves') }}">
                        @php
                            $authUser = auth()->user();
                        @endphp
                        @forelse ($permits as $leave)
                            @php
                                $durationTone = $leave->normalizedDurationUnit() === 'day'
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'bg-sky-100 text-sky-700';
                                $statusTone = match ($leave->status_id) {
                                    10 => 'bg-neutral-200/60 text-neutral-600 border-neutral-200',
                                    20 => 'bg-emerald-50 border-emerald-200 text-emerald-600',
                                    30 => 'bg-rose-50 border-rose-200 text-rose-600',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <tr wire:key="leave-row-{{ $leave->id }}">
                                <td class="px-5 py-3 align-middle text-sm text-zinc-700 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $leave->row_no }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle text-sm text-zinc-700 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-neutral-900">
                                            {{ $leave->personnel->fullname_max }}
                                        </span>
                                        <span class="text-sm font-medium text-neutral-600/80">
                                            {{ $leave->personnel_structure_path }}
                                        </span>
                                        <span class="text-sm font-medium text-emerald-600">
                                            {{ $leave->personnel->position_label }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-3 align-middle text-sm text-zinc-700 whitespace-nowrap">
                                    <span class="inline-flex w-max items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-tight text-slate-700">
                                        {{ $leave->leaveType->name }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle text-sm text-zinc-700 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                      <span class="flex-wrap text-sm font-medium whitespace-normal">{{ $leave->periodLabel }}</span>
                                      <div class="flex flex-wrap items-center gap-2">
                                          <span class="inline-flex h-5 w-fit shrink-0 items-center justify-center rounded-4xl bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-900">
                                              {{ $leave->durationSummary() }}
                                          </span>
                                          @if($leave->durationWindowLabel())
                                              <span class="inline-flex h-5 w-fit shrink-0 items-center justify-center rounded-4xl bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">
                                                  {{ $leave->durationWindowLabel() }}
                                              </span>
                                          @endif
                                      </div>
                                    </div>
                                      @if ($leave->deleted_at)
                                          <hr class="my-1" >
                                           <div class="flex items-center mt-2 space-x-1 text-xs font-medium">
                                                <span class="text-rose-500">{{ __('leaves::common.labels.deleted_date') }}:</span>
                                                <span class="text-black">{{ \Carbon\Carbon::parse($leave->deleted_at)->format('d-m-Y H:i') }}</span>
                                            </div>
                                        @endif
                                </td>

                               <td class="px-5 py-3 align-middle text-sm text-zinc-700">
                                    <span class="text-sm font-medium text-gray-700 whitespace-normal flex w-[160px] bg-zinc-100/70 rounded-xl shadow-sm border border-gray-200 px-3 py-2">
                                        {{ $leave->reason }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle text-sm text-zinc-700 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex w-max items-center rounded-full border px-2.5 py-1 text-xs font-semibold uppercase tracking-tight {{ $statusTone }}">
                                                {{ $leave->status->name }}
                                            </span>
                                            <span class="inline-flex h-5 w-fit shrink-0 items-center justify-center rounded-4xl px-2 py-0.5 text-xs font-medium {{ $durationTone }}">
                                                {{ $leave->durationSummary() }}
                                            </span>
                                            @if($leave?->latestLog?->comment)
                                            <div class="relative top-1" x-data="{showComment: false}">
                                                <button @click="showComment = true" class="appearance-none" type="button" title="{{ __('leaves::common.actions.show_comment') }}" aria-label="{{ __('leaves::common.actions.show_comment') }}">
                                                    <x-icons.comment-icon color="text-sky-500" hover="text-indigo-700" />
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

                                        @if($leave->durationWindowLabel())
                                            <div class="text-xs text-zinc-500">
                                                {{ $leave->durationWindowLabel() }}
                                            </div>
                                        @endif

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
                                </td>

                                <td class="px-5 py-3 align-middle text-sm text-zinc-700">
                                    <div class="flex flex-col flex-wrap space-y-2 whitespace-normal">
                                        @if($leave->document_path)
                                        <a
                                            href="/{{ $leave->document_path }}"
                                            target="_blank"
                                            class="flex flex-col gap-1 text-sm font-medium"
                                            style="word-break: break-word;"
                                            title="{{ __('leaves::common.actions.download_document') }}"
                                            aria-label="{{ __('leaves::common.actions.download_document') }}"
                                        >
                                            <x-icons.link-icon size="w-6 h-6" color="text-blue-600" hover="text-sky-300" />
                                        </a>
                                        @endif
                                        @if($leave->canBeApprovedBy($authUser))
                                        <div class="flex items-center space-x-2">
                                            <button
                                                wire:loading.attr="disabled"
                                                @click="$dispatch('comment:open', { action: 'APPROVED', leaveId: @js($leave->id) })"
                                                type="button"
                                                title="{{ __('leaves::common.actions.approve') }}"
                                                aria-label="{{ __('leaves::common.actions.approve') }}"
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
                                                type="button"
                                                title="{{ __('leaves::common.actions.reject') }}"
                                                aria-label="{{ __('leaves::common.actions.reject') }}"
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
                                </td>

                                <td class="px-4 py-3 align-middle text-sm text-zinc-700 text-right whitespace-nowrap">
                                    @if ($status != 'deleted')
                                        @can('update', $leave)
                                            <button
                                                wire:click="openEditLeaveModal({{ $leave->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="openEditLeaveModal"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700"
                                                type="button"
                                                title="{{ __('leaves::common.actions.edit') }}"
                                                aria-label="{{ __('leaves::common.actions.edit') }}"
                                            >
                                                <x-icons.document-icon></x-icons.document-icon>
                                            </button>
                                        @endcan
                                    @else
                                        @can('restore', $leave)
                                            <button wire:click="restoreData('{{ $leave->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restoreData('{{ $leave->id }}')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-teal-50 hover:text-gray-700"
                                                    type="button"
                                                    title="{{ __('leaves::common.actions.restore') }}"
                                                    aria-label="{{ __('leaves::common.actions.restore') }}"
                                            >
                                                <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                                            </button>
                                        @endcan
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-middle text-sm text-zinc-700 text-right whitespace-nowrap">
                                    @if ($status != 'deleted')
                                        @can('delete', $leave)
                                            <button
                                                wire:click="setDeleteLeave('{{ $leave->id }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="setDeleteLeave('{{ $leave->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700"
                                                type="button"
                                                title="{{ __('leaves::common.actions.delete') }}"
                                                aria-label="{{ __('leaves::common.actions.delete') }}"
                                            >
                                                <x-icons.delete-icon></x-icons.delete-icon>
                                            </button>
                                        @endcan
                                    @else
                                        @can('forceDelete', $leave)
                                            <button
                                                x-on:click="$dispatch('confirm-action', { title: @js(__('leaves::common.actions.force_delete')), message: @js(__('leaves::common.messages.remove_confirm')), confirmText: @js(__('leaves::common.actions.force_delete')), tone: 'rose', run: () => $wire.forceDeleteData('{{ $leave->id }}') })"
                                                wire:loading.attr="disabled"
                                                wire:target="forceDeleteData('{{ $leave->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                                type="button"
                                                title="{{ __('leaves::common.actions.force_delete') }}"
                                                aria-label="{{ __('leaves::common.actions.force_delete') }}"
                                            >
                                                <x-icons.force-delete></x-icons.force-delete>
                                            </button>
                                        @endcan
                                    @endif
                                </td>
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

    <x-side-modal :local-state="true">
        @can('create', App\Models\Leave::class)
            @if($showSideMenu === 'add-leave')
                <div x-show="activeMenu === 'add-leave'" x-cloak>
                    <livewire:leaves.add-leave wire:key="leaves-add-modal" />
                </div>
            @endif
        @endcan

        @can('update', App\Models\Leave::class)
            @if($showSideMenu === 'edit-leave')
                <div x-show="activeMenu === 'edit-leave'" x-cloak>
                    <livewire:leaves.edit-leave :leave-model="$modelName" wire:key="leaves-edit-modal-{{ $modelName ?? 'empty' }}" />
                </div>
            @endif
        @endcan
    </x-side-modal>

    <div>
        @auth
            @can('delete', App\Models\Leave::class)
                @livewire('leaves.delete-leave')
            @endcan
        @endauth
    </div>

    <x-datepicker :auto=false></x-datepicker>
</div>

@push('js')
<script>
    function leavesIndex() {
        return {
            componentId: null,
            init() {
                this.componentId = this.$root?.getAttribute('wire:id');
                this.highlightPager();

                if (typeof Livewire !== 'undefined') {
                    Livewire.hook('commit', ({ component, succeed }) => {
                        if (!this.componentId || component.id !== this.componentId) return;
                        succeed(() => queueMicrotask(() => this.highlightPager(true)));
                    });
                }
            },
            highlightPager(isUpdate = false) {
                const el = this.$root?.querySelector('span[aria-current=page]>span');
                if (!el) return;
                el.classList.remove('bg-blue-50','text-blue-600','bg-green-100','text-green-600');
                el.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
            }
        }
    }
</script>
@endpush
