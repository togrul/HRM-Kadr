<div class="space-y-4">
    <x-surface-card :title="__('Overtime approval board')" icon="icons.clock-icon">
        <div class="flex flex-wrap items-end gap-3">
            <p class="text-sm text-zinc-500">{{ __('Panel to approve/reject overtime requests.') }}</p>

            <div class="ms-auto grid gap-2 sm:grid-cols-3">
                <div>
                    <x-label for="attendance-ot-status">{{ __('Status') }}</x-label>
                    <select id="attendance-ot-status" wire:model.live="status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="pending">{{ __('pending') }}</option>
                        <option value="approved">{{ __('approved') }}</option>
                        <option value="rejected">{{ __('rejected') }}</option>
                        <option value="all">{{ __('all') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-ot-from">{{ __('From') }}</x-label>
                    <input id="attendance-ot-from" wire:model.live="fromDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-ot-to">{{ __('To') }}</x-label>
                    <input id="attendance-ot-to" wire:model.live="toDate" type="date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
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

    <div class="relative overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('Date'),
                    __('Tabel no'),
                    __('Personnel'),
                    __('Requested (min)'),
                    __('Approved (min)'),
                    __('Status'),
                    __('Action')
                ]">
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
                                @if($item->personnel?->structure?->name)
                                    <span class="text-xs text-zinc-500">{{ $item->personnel->structure->name }}</span>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td extraClasses="text-right text-zinc-700">{{ (int) $item->requested_minutes }}</x-table.td>
                        <x-table.td extraClasses="text-right">
                            <input
                                wire:model.defer="approvedMinutes.{{ $item->id }}"
                                type="number"
                                min="0"
                                max="{{ (int) $item->requested_minutes }}"
                                placeholder="{{ (int) $item->requested_minutes }}"
                                @disabled(! $canApprove)
                                class="h-8 w-24 rounded-md border-none bg-neutral-100 px-2 text-right text-xs shadow-sm disabled:cursor-not-allowed disabled:opacity-60"
                            />
                        </x-table.td>
                        <x-table.td>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badgeClass }}">{{ __($item->status) }}</span>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            @if($canApprove && $item->status === 'pending')
                                <div class="inline-flex items-center gap-2">
                                    <x-button mode="success" class="!h-8 !px-3 !text-xs" wire:click="approve({{ $item->id }})">
                                        {{ __('Approve') }}
                                    </x-button>
                                    <x-button mode="danger" class="!h-8 !px-3 !text-xs" wire:click="reject({{ $item->id }})">
                                        {{ __('Reject') }}
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
                    <x-table.empty :rows="7" />
                @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
</div>
