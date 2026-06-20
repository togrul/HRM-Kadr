<div class="space-y-4">
    <x-surface-card :title="__('attendance::exceptions.title')" icon="icons.pending-icon">
        <div class="space-y-3">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::exceptions.filters.title') }}</p>
                <p class="text-sm text-zinc-500">{{ __('attendance::exceptions.filters.description') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-label for="attendance-ex-status">{{ __('attendance::exceptions.filters.status') }}</x-label>
                    <select id="attendance-ex-status" wire:model.live="status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="open">{{ __('attendance::exceptions.statuses.open') }}</option>
                        <option value="resolved">{{ __('attendance::exceptions.statuses.resolved') }}</option>
                        <option value="all">{{ __('attendance::exceptions.statuses.all') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-ex-type">{{ __('attendance::exceptions.filters.type') }}</x-label>
                    <select id="attendance-ex-type" wire:model.live="type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="all">{{ __('attendance::exceptions.types.all') }}</option>
                        <option value="missing_in">{{ __('attendance::exceptions.types.missing_in') }}</option>
                        <option value="missing_out">{{ __('attendance::exceptions.types.missing_out') }}</option>
                        <option value="unmatched_punch">{{ __('attendance::exceptions.types.unmatched_punch') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-ex-from">{{ __('attendance::exceptions.filters.from') }}</x-label>
                    <input id="attendance-ex-from" wire:model.live="fromDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-ex-to">{{ __('attendance::exceptions.filters.to') }}</x-label>
                    <input id="attendance-ex-to" wire:model.live="toDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::exceptions.scope.badge') }}</x-small-badge>
            <span>{{ __('attendance::exceptions.scope.description') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="space-y-3">
    <div class="relative min-h-[220px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('attendance::exceptions.table.date'),
                    __('attendance::exceptions.table.tabel_no'),
                    __('attendance::exceptions.table.personnel'),
                    __('attendance::exceptions.table.type'),
                    __('attendance::exceptions.table.message'),
                    __('attendance::exceptions.table.status'),
                    __('attendance::exceptions.table.action')
                ]" :title="__('attendance::exceptions.table.title')">
                    @forelse($items as $item)
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
                            <x-table.td extraClasses="text-zinc-600">{{ __('attendance::exceptions.types.'.$item->type) }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-600 whitespace-normal">{{ $item->message }}</x-table.td>
                            <x-table.td>
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $item->status === 'open' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ __('attendance::exceptions.statuses.'.$item->status) }}
                                </span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                @if(! $canResolve)
                                    <span class="text-xs text-zinc-500">-</span>
                                @elseif($item->status === 'open')
                                    <x-button mode="success" class="!h-8 !px-3 !text-xs" wire:click="markResolved({{ $item->id }})">
                                        {{ __('attendance::exceptions.actions.resolve') }}
                                    </x-button>
                                @else
                                    <x-button mode="black" class="!h-8 !px-3 !text-xs" wire:click="reopen({{ $item->id }})">
                                        {{ __('attendance::exceptions.actions.reopen') }}
                                    </x-button>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="7" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $items->links() }}
    </div>
    </div>
</div>
