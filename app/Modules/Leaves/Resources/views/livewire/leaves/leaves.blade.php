@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $counts = $this->statusCounts;
    $dayEquivalent = rtrim(rtrim(number_format($counts['day_equivalent'], 1, '.', ''), '0'), '.');
    $statusOptions = collect([['id' => 'all', 'label' => __('leaves::common.labels.all').' · '.$num($counts['all'])]])
        ->concat($_appeal_statuses->map(fn ($_status): array => [
            'id' => $_status->id,
            'label' => $_status->name.' · '.$num($counts['by_status'][(int) $_status->id] ?? 0),
        ]))
        ->when(auth()->user()?->can('delete', App\Models\Leave::class), fn ($options) => $options->push([
            'id' => 'deleted',
            'label' => __('leaves::common.labels.deleted').' · '.$num($counts['deleted']),
        ]))
        ->values()
        ->all();
    $leaveTypeOptions = collect($this->leaveTypes())
        ->map(fn ($leaveType): array => [
            'id' => data_get($leaveType, 'id'),
            'label' => data_get($leaveType, 'label').' · '.$num(data_get($stats, data_get($leaveType, 'label').'.count', 0)),
        ])
        ->all();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('leaves::common.titles.leaves')"
            :subtitle="$num($counts['all']).' '.__('leaves::common.labels.unit')"
        ></x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        collapsible-filters
        :filters-active="$this->hasActiveFilters"
        :title="__('leaves::common.labels.requests_title')"
        :breadcrumb="__('leaves::common.titles.leaves')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($counts['all'])" :label="__('leaves::common.labels.unit')" />
            <x-page-header.stat :value="$num($counts['by_status'][10] ?? 0)" :label="__('leaves::common.labels.pending_short')" tone="amber" />
            <x-page-header.stat :value="$dayEquivalent" :label="__('leaves::common.labels.day_equivalent')" />
        </x-slot:stats>

        <x-slot:actions>
            @can('export', App\Models\Leave::class)
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('leaves::common.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
            @can('create', App\Models\Leave::class)
                <x-pill-button variant="primary" wire:click="openAddLeaveModal" wire:loading.attr="disabled" wire:target="openAddLeaveModal">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('leaves::common.actions.add_leave') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex flex-wrap items-end gap-3">
                <label class="w-full flex-1 sm:min-w-[240px] sm:max-w-[320px]">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::common.labels.fullname') }}</span>
                    <x-livewire-input mode="gray" name="filter.fullname" wire:model.live.debounce.400ms="filter.fullname"
                        placeholder="{{ __('leaves::common.labels.search_by_person') }}" />
                </label>

                <div class="shrink-0">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::common.labels.dates') }}</span>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model.live="filter.starts_at"
                            aria-label="{{ __('leaves::common.labels.date_start') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0" />
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <input type="date" wire:model.live="filter.ends_at"
                            aria-label="{{ __('leaves::common.labels.date_end') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0" />
                    </div>
                </div>

                <label class="min-w-[170px] flex-1">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::common.labels.reason') }}</span>
                    <x-livewire-input mode="gray" type="text" name="filter.reason" wire:model.live.debounce.400ms="filter.reason" />
                </label>

                <x-ui.select-dropdown
                    :aria-label="__('leaves::common.labels.status')"
                    wire:key="leaves-status-filter"
                    :placeholder="__('leaves::common.labels.all')"
                    :clearable="false"
                    mode="gray"
                    class="w-full sm:w-52 [&>div]:mt-0"
                    wire:model.live="status"
                    :model="$statusOptions"
                />

                <x-ui.select-dropdown
                    :aria-label="__('leaves::common.labels.leave_type')"
                    wire:key="leaves-type-filter"
                    :placeholder="__('leaves::common.labels.leave_type')"
                    mode="gray"
                    class="w-full sm:w-64 [&>div]:mt-0"
                    searchable
                    wire:model.live="filter.leave_type_id"
                    :model="$leaveTypeOptions"
                />

                <x-filter.reset :active="$this->hasActiveFilters" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="hrm-eyebrow">{{ __('leaves::common.labels.gender') }}</span>
                {{-- chips sit next to the instant status chips, so they must apply on click:
                     $set would only touch $filter, and the list reads $search. --}}
                <x-filter.nav wrap class="min-w-0">
                    <x-filter.item
                        wire:click.prevent="applyFilter({ gender: null })"
                        wire:loading.attr="disabled"
                        :active="blank(data_get($search, 'gender'))"
                    >{{ __('leaves::common.labels.all') }}</x-filter.item>
                    @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                        <x-filter.item
                            wire:key="leave-gender-{{ $value }}"
                            wire:click.prevent="applyFilter({ gender: '{{ $value }}' })"
                            wire:loading.attr="disabled"
                            :active="(string) data_get($search, 'gender') === (string) $value"
                        >{{ $label }}</x-filter.item>
                    @endforeach
                </x-filter.nav>
            </div>

            <p class="text-[11.5px] text-ink-faint">{{ __('leaves::common.labels.approval_note') }}</p>
        </div>
    </x-page-header>

    <x-table.tbl sticky :headers="$this->getTableHeaders()">
        @php $authUser = auth()->user(); @endphp
        @forelse ($permits as $leave)
            @php
                $statusTone = match ((int) $leave->status_id) {
                    10 => 'amber',
                    20 => 'green',
                    30 => 'rose',
                    default => 'secondary',
                };
            @endphp
            <tr wire:key="leave-row-{{ $leave->id }}" @class(['bg-[#fffbeb]/60' => (int) $leave->status_id === 10])>
                <x-table.td standart-width>
                    <div class="flex items-center gap-2.5">
                        <x-avatar :name="(string) $leave->personnel?->fullname" :tone="$statusTone === 'amber' ? 'amber' : 'neutral'" />
                        <div class="min-w-0 max-w-[240px] leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $leave->personnel?->fullname_max }}</p>
                            <p class="truncate text-[11px] text-ink-faint">{{ $leave->personnel?->position_label }}</p>
                            <p class="truncate text-[11px] text-ink-faint">{{ $leave->personnel_structure_path }}</p>
                        </div>
                    </div>
                </x-table.td>

                <x-table.td>
                    <x-small-badge mode="secondary">{{ $leave->leaveType?->name }}</x-small-badge>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[220px] leading-tight">
                        <p class="hrm-num text-[13px] font-medium text-ink">{{ $leave->periodLabel }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <x-small-badge mode="secondary">{{ $leave->durationSummary() }}</x-small-badge>
                            @if ($leave->durationWindowLabel())
                                <x-small-badge mode="blue">{{ $leave->durationWindowLabel() }}</x-small-badge>
                            @endif
                        </div>
                        @if ($leave->deleted_at)
                            <p class="mt-1 text-[11px] text-ink-faint">
                                {{ __('leaves::common.labels.deleted_date') }}:
                                <span class="hrm-num">{{ \Carbon\Carbon::parse($leave->deleted_at)->format('d.m.Y H:i') }}</span>
                            </p>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <p class="max-w-[200px] whitespace-normal text-[12.5px] leading-snug text-ink-muted">{{ $leave->reason }}</p>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[190px] leading-tight">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge :mode="$statusTone" dot>{{ $leave->status?->name }}</x-small-badge>
                            @if ($leave?->latestLog?->comment)
                                <div class="relative" x-data="{ showComment: false }" x-on:click.outside="showComment = false">
                                    <button type="button" x-on:click="showComment = ! showComment"
                                        title="{{ __('leaves::common.actions.show_comment') }}"
                                        class="flex h-6 w-6 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                        <x-icons.comment-icon color="text-current" hover="text-current" size="w-4 h-4" />
                                    </button>
                                    <div
                                        @class([
                                            'absolute z-10 w-[210px] rounded-xl border border-hairline bg-white px-3 py-2 shadow-overlay',
                                            'bottom-8' => $loop->last,
                                            'top-8' => ! $loop->last,
                                        ])
                                        x-show="showComment" x-cloak
                                        x-on:keydown.window.escape.prevent="showComment = false"
                                    >
                                        <p class="text-[12px] leading-snug text-ink-muted">{{ $leave->latestLog->comment }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ((int) $leave->status_id !== 10 && $leave->latestLog)
                            <p class="mt-1 truncate text-[11px] text-ink-faint">{{ $leave->latestLog->changedBy?->fullname }}</p>
                            <p class="hrm-num text-[11px] text-ink-faint">{{ \Carbon\Carbon::parse($leave->latestLog->changed_at)->format('d.m.Y H:i') }}</p>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    @if ($leave->document_path)
                        <a href="/{{ $leave->document_path }}" target="_blank" rel="noopener"
                            title="{{ __('leaves::common.actions.download_document') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-[#0369a1]">
                            <x-icons.link-icon size="w-4 h-4" color="text-current" hover="text-current" />
                        </a>
                    @else
                        <span class="text-ink-faint">&mdash;</span>
                    @endif
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        @if ($leave->canBeApprovedBy($authUser))
                            <button type="button" wire:loading.attr="disabled"
                                x-on:click="$dispatch('comment:open', { action: 'APPROVED', leaveId: {{ (int) $leave->id }} })"
                                title="{{ __('leaves::common.actions.approve') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-emerald-50 hover:text-emerald-600">
                                <x-icons.check-icon color="text-current" hover="text-current" size="w-5 h-5" />
                            </button>
                            <button type="button" wire:loading.attr="disabled"
                                x-on:click="$dispatch('comment:open', { action: 'CANCELLED', leaveId: {{ (int) $leave->id }} })"
                                title="{{ __('leaves::common.actions.reject') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                <x-icons.x-circle-icon color="text-current" hover="text-current" size="w-5 h-5" />
                            </button>
                        @endif

                        @if ($status != 'deleted')
                            @can('update', $leave)
                                <button type="button" wire:click="openEditLeaveModal({{ $leave->id }})"
                                    wire:loading.attr="disabled" wire:target="openEditLeaveModal"
                                    title="{{ __('leaves::common.actions.edit') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                    <x-icons.document-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                            @can('delete', $leave)
                                <button type="button" wire:click="setDeleteLeave('{{ $leave->id }}')"
                                    wire:loading.attr="disabled"
                                    title="{{ __('leaves::common.actions.delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.delete-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                        @else
                            @can('restore', $leave)
                                <button type="button" wire:click="restoreData('{{ $leave->id }}')"
                                    wire:loading.attr="disabled"
                                    title="{{ __('leaves::common.actions.restore') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                    <x-icons.recover color="text-current" hover="text-current" />
                                </button>
                            @endcan
                            @can('forceDelete', $leave)
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { title: {{ \Illuminate\Support\Js::from(__('leaves::common.actions.force_delete')) }}, message: {{ \Illuminate\Support\Js::from(__('leaves::common.messages.remove_confirm')) }}, confirmText: {{ \Illuminate\Support\Js::from(__('leaves::common.actions.force_delete')) }}, tone: 'rose', run: () => $wire.forceDeleteData('{{ $leave->id }}') })"
                                    wire:loading.attr="disabled"
                                    title="{{ __('leaves::common.actions.force_delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.force-delete />
                                </button>
                            @endcan
                        @endif
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" :filtered="$this->hasActiveFilters">
                <x-slot:action>
                    @can('create', App\Models\Leave::class)
                        <x-pill-button variant="primary" wire:click="openAddLeaveModal">{{ __('leaves::common.actions.add_leave') }}</x-pill-button>
                    @endcan
                </x-slot:action>
            </x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$permits" :unit="__('leaves::common.labels.unit')" />

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

</div>
