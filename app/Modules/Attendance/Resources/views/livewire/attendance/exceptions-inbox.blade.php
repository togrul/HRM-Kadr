<div class="space-y-4">
    <x-surface-card :title="__('Exceptions inbox')" icon="icons.pending-icon">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-label for="attendance-ex-status">{{ __('Status') }}</x-label>
                <select id="attendance-ex-status" wire:model.live="status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                    <option value="open">{{ __('open') }}</option>
                    <option value="resolved">{{ __('resolved') }}</option>
                    <option value="all">{{ __('all') }}</option>
                </select>
            </div>
            <div>
                <x-label for="attendance-ex-type">{{ __('Type') }}</x-label>
                <select id="attendance-ex-type" wire:model.live="type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                    <option value="all">{{ __('all') }}</option>
                    <option value="missing_in">{{ __('missing_in') }}</option>
                    <option value="missing_out">{{ __('missing_out') }}</option>
                    <option value="unmatched_punch">{{ __('unmatched_punch') }}</option>
                </select>
            </div>
            <div>
                <x-label for="attendance-ex-from">{{ __('From') }}</x-label>
                <input id="attendance-ex-from" wire:model.live="fromDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
            </div>
            <div>
                <x-label for="attendance-ex-to">{{ __('To') }}</x-label>
                <input id="attendance-ex-to" wire:model.live="toDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('Structure scope') }}</x-small-badge>
            <span>{{ __('Showing personnel from the selected structure tree only.') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="relative min-h-[220px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('Date'),
                    __('Tabel no'),
                    __('Personnel'),
                    __('Type'),
                    __('Message'),
                    __('Status'),
                    __('Action')
                ]">
                    @forelse($items as $item)
                        <tr>
                            <x-table.td>{{ optional($item->date)->format('Y-m-d') }}</x-table.td>
                            <x-table.td extraClasses="font-medium text-zinc-700">{{ $item->tabel_no }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-700">
                                <div class="flex flex-col">
                                    <span>{{ $item->personnel?->surname }} {{ $item->personnel?->name }} {{ $item->personnel?->patronymic }}</span>
                                    @if($item->personnel?->structure?->name)
                                        <span class="text-xs text-zinc-500">{{ $item->personnel->structure->name }}</span>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td extraClasses="text-zinc-600">{{ __($item->type) }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-600 whitespace-normal">{{ $item->message }}</x-table.td>
                            <x-table.td>
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $item->status === 'open' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ __($item->status) }}
                                </span>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                @if(! $canResolve)
                                    <span class="text-xs text-zinc-500">-</span>
                                @elseif($item->status === 'open')
                                    <x-button mode="success" class="!h-8 !px-3 !text-xs" wire:click="markResolved({{ $item->id }})">
                                        {{ __('Resolve') }}
                                    </x-button>
                                @else
                                    <x-button mode="black" class="!h-8 !px-3 !text-xs" wire:click="reopen({{ $item->id }})">
                                        {{ __('Reopen') }}
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
