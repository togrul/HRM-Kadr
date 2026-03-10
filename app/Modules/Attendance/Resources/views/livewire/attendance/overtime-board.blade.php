<div class="space-y-4">
    <x-surface-card :title="__('attendance::overtime.title')" icon="icons.clock-icon">
        <div class="space-y-3">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::overtime.filters.title') }}</p>
                <p class="text-sm text-zinc-500">{{ __('attendance::overtime.filters.description') }}</p>
            </div>

            <div class="grid gap-2 sm:grid-cols-3">
                <div>
                    <x-label for="attendance-ot-status">{{ __('attendance::overtime.filters.status') }}</x-label>
                    <select id="attendance-ot-status" wire:model.live="status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="pending">{{ __('attendance::overtime.statuses.pending') }}</option>
                        <option value="approved">{{ __('attendance::overtime.statuses.approved') }}</option>
                        <option value="rejected">{{ __('attendance::overtime.statuses.rejected') }}</option>
                        <option value="all">{{ __('attendance::overtime.statuses.all') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-ot-from">{{ __('attendance::overtime.filters.from') }}</x-label>
                    <input id="attendance-ot-from" wire:model.live="fromDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-ot-to">{{ __('attendance::overtime.filters.to') }}</x-label>
                    <input id="attendance-ot-to" wire:model.live="toDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white/70 px-3 py-2 text-xs text-zinc-600">
                <span class="font-medium text-zinc-700">{{ __('attendance::overtime.badges.title') }}</span>
                <x-small-badge mode="purple">{{ __('attendance::overtime.badges.manual') }}</x-small-badge>
                <x-small-badge mode="blue">{{ __('attendance::overtime.badges.auto_generated') }}</x-small-badge>
                <x-small-badge mode="sky">{{ __('attendance::overtime.badges.manual_request') }}</x-small-badge>
                <x-small-badge mode="green">{{ __('attendance::overtime.badges.from_manual_entry') }}</x-small-badge>
                <x-small-badge mode="secondary">{{ __('attendance::overtime.badges.from_ledger') }}</x-small-badge>
            </div>
        </div>
    </x-surface-card>

    @if($canCreate)
        <x-surface-card :title="__('attendance::overtime.create.title')">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <x-ui.search-input-select
                        :label="__('attendance::overtime.create.personnel')"
                        searchModel="personnelSearch"
                        :selected="$selectedPersonnel"
                        displayKey="fullname"
                        idKey="tabel_no"
                        onClear="clearPersonnel"
                        clearField="tabel_no"
                        :placeholder="__('attendance::overtime.create.placeholder')"
                    >
                        @forelse($personnelResults as $personnel)
                            <button
                                type="button"
                                wire:click="selectPersonnel('{{ $personnel->tabel_no }}', '{{ addslashes($personnel->fullname) }}')"
                                class="flex w-full flex-col rounded-md px-2 py-1 text-left text-slate-600 transition-all duration-300 hover:bg-white drop-shadow-sm"
                            >
                                <span>{{ $personnel->fullname }}</span>
                                <span class="text-xs font-mono text-zinc-500">{{ $personnel->tabel_no }}</span>
                                @if($personnel->structure_path)
                                    <span class="max-w-[18rem] truncate text-[11px] text-zinc-400 md:max-w-[24rem]" title="{{ $personnel->structure_path }}">
                                        {{ $personnel->structure_path }}
                                    </span>
                                @endif
                            </button>
                        @empty
                            <span class="mx-auto text-sm font-medium text-slate-500">
                                {{ __('attendance::overtime.create.placeholder') }}
                            </span>
                        @endforelse
                    </x-ui.search-input-select>
                </div>
                <div>
                    <x-label for="attendance-ot-create-date">{{ __('attendance::overtime.create.date') }}</x-label>
                    <input id="attendance-ot-create-date" wire:model.live="manualRequest.date" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-ot-create-minutes">{{ __('attendance::overtime.create.requested_minutes') }}</x-label>
                    <input id="attendance-ot-create-minutes" wire:model.defer="manualRequest.requested_minutes" type="number" min="1" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <x-label for="attendance-ot-create-reason">{{ __('attendance::overtime.create.reason') }}</x-label>
                    <input id="attendance-ot-create-reason" wire:model.defer="manualRequest.reason" type="text" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div class="flex items-end">
                    <x-button mode="success" class="w-full !h-10" wire:click="createManualRequest">
                        {{ __('attendance::overtime.create.action') }}
                    </x-button>
                </div>
            </div>
        </x-surface-card>
    @endif

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::overtime.scope.badge') }}</x-small-badge>
            <span>{{ __('attendance::overtime.scope.description') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    @if(!empty($activeFilters))
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2">
            <span class="text-xs font-semibold uppercase  text-zinc-400">{{ __('attendance::overtime.filters.active') }}</span>
            @foreach($activeFilters as $filter)
                <x-small-badge :mode="$filter['mode']">
                    {{ $filter['label'] }}: {{ $filter['value'] }}
                </x-small-badge>
            @endforeach
        </div>
    @endif

    <div class="space-y-3">
    <div class="relative overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('attendance::overtime.table.date'),
                    __('attendance::overtime.table.tabel_no'),
                    __('attendance::overtime.table.personnel'),
                    __('attendance::overtime.table.requested_minutes'),
                    __('attendance::overtime.table.approved_minutes'),
                    __('attendance::overtime.table.status'),
                    __('attendance::overtime.table.action')
                ]" :title="__('attendance::overtime.table.title')">
                @forelse($items as $item)
                    @php
                        $badgeClass = match($item->status) {
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                    @endphp
                    <tr>
                        <x-table.td>{{ optional($item->date)->format('Y-m-d') }}</x-table.td>
                        <x-table.td extraClasses="font-medium text-zinc-700">{{ $item->tabel_no }}</x-table.td>
                        <x-table.td extraClasses="text-zinc-700">
                            <div class="flex flex-col">
                                <span>{{ $item->personnel?->surname }} {{ $item->personnel?->name }} {{ $item->personnel?->patronymic }}</span>
                                @if($item->personnel?->structure_path)
                                    <span class="max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $item->personnel->structure_path }}">
                                        {{ $item->personnel->structure_path }}
                                    </span>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td extraClasses="text-right text-zinc-700">{{ (int) $item->requested_minutes }}</x-table.td>
                        <x-table.td extraClasses="text-right">
                            <div class="inline-flex min-w-24 items-center justify-end rounded-md bg-neutral-100 px-3 py-2 text-right text-xs font-medium text-zinc-700 shadow-sm">
                                {{ (int) ($item->status === 'approved' ? $item->approved_minutes : $item->requested_minutes) }}
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badgeClass }}">{{ __('attendance::overtime.statuses.'.$item->status) }}</span>
                                    <x-small-badge :mode="$item->origin_badge_mode">
                                        {{ $item->origin_label }}
                                    </x-small-badge>
                                    <x-small-badge :mode="$item->source_badge_mode">
                                        {{ $item->source_label }}
                                    </x-small-badge>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-zinc-500">
                                    <span>{{ __('attendance::overtime.table.request_age') }}: {{ (int) $item->request_age_days }}d</span>
                                    @if($item->is_stale_pending)
                                        <x-small-badge mode="amber">{{ __('attendance::overtime.badges.stale_pending') }}</x-small-badge>
                                    @endif
                                    @if($item->status === 'pending')
                                        <span>{{ __('attendance::overtime.table.pending_approval_hint') }}</span>
                                    @endif
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            @if($canApprove && $item->status === 'pending')
                                <div class="inline-flex items-center gap-2">
                                    <x-button mode="success" class="!h-8 !px-3 !text-xs" wire:click="approve({{ $item->id }})">
                                        {{ __('attendance::overtime.actions.approve') }}
                                    </x-button>
                                    <x-button mode="danger" class="!h-8 !px-3 !text-xs" wire:click="reject({{ $item->id }})">
                                        {{ __('attendance::overtime.actions.reject') }}
                                    </x-button>
                                </div>
                            @else
                                <span class="text-xs text-zinc-500">
                                    {{ optional($item->approvedBy)->name ?? '-' }}
                                </span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10">
                            <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-6 py-8 text-center">
                                <x-small-badge mode="secondary">{{ $emptyStateTitle }}</x-small-badge>
                                <p class="max-w-2xl text-sm leading-6 text-zinc-500">
                                    {{ $emptyStateDescription }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
    </div>
</div>
