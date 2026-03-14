<div class="space-y-4">
    <x-surface-card :title="__('attendance::history.title')" icon="icons.info-circle-icon">
        <div class="space-y-3">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('attendance::history.filters.title') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('attendance::history.filters.description') }}</p>
                </div>
                @if($subjectId)
                    <x-button mode="slate" class="!h-9 !px-3 !text-xs" wire:click="clearSubjectFilter">
                        {{ __('attendance::history.actions.clear_subject_filter') }}
                    </x-button>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <x-label for="attendance-history-type">{{ __('attendance::history.filters.type') }}</x-label>
                    <select id="attendance-history-type" wire:model.live="type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="attendance-history-from">{{ __('attendance::history.filters.date_from') }}</x-label>
                    <input id="attendance-history-from" type="date" wire:model.live="dateFrom" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-history-to">{{ __('attendance::history.filters.date_to') }}</x-label>
                    <input id="attendance-history-to" type="date" wire:model.live="dateTo" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                </div>
                <div>
                    <x-label for="attendance-history-search">{{ __('attendance::history.filters.search') }}</x-label>
                    <x-livewire-input
                        id="attendance-history-search"
                        mode="gray"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('attendance::history.filters.search_placeholder') }}"
                    />
                </div>
            </div>
        </div>
    </x-surface-card>

    @if($subjectId)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::history.labels.subject_filter') }}</x-small-badge>
            <span>{{ __('attendance::history.labels.subject_filter_description', ['id' => $subjectId]) }}</span>
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <x-surface-card :title="__('attendance::history.cards.total_changes')"><div class="text-2xl font-semibold text-zinc-800">{{ $totals['total_changes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::history.cards.calendar_changes')"><div class="text-2xl font-semibold text-blue-700">{{ $totals['calendar_changes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::history.cards.shift_changes')"><div class="text-2xl font-semibold text-emerald-700">{{ $totals['shift_changes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::history.cards.settings_changes')"><div class="text-2xl font-semibold text-amber-700">{{ $totals['settings_changes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::history.cards.causer_count')"><div class="text-2xl font-semibold text-zinc-800">{{ $totals['causer_count'] }}</div></x-surface-card>
    </div>

    <div class="relative overflow-x-hidden">
        <div class="block w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('attendance::history.table.when'),
                    __('attendance::history.table.section'),
                    __('attendance::history.table.event'),
                    __('attendance::history.table.subject'),
                    __('attendance::history.table.actor'),
                    __('attendance::history.table.changed_fields'),
                    __('attendance::history.table.actions')
                ]" :title="__('attendance::history.table.title')" class="table-fixed">
                    @forelse($rows as $row)
                        <tr wire:key="attendance-history-{{ $row->id }}">
                            <x-table.td standartWidth extraClasses="w-36 whitespace-normal break-words text-sm text-zinc-600 align-top">{{ $row->created_at?->format('Y-m-d H:i') }}</x-table.td>
                            <x-table.td standartWidth extraClasses="w-32 whitespace-normal align-top"><x-small-badge mode="secondary">{{ $row->type_label }}</x-small-badge></x-table.td>
                            <x-table.td standartWidth extraClasses="whitespace-normal break-words align-top">
                                <div class="flex min-w-0 flex-col gap-1">
                                    <span class="font-medium text-zinc-800">{{ $row->event_label }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td standartWidth extraClasses="whitespace-normal break-words text-sm text-zinc-700 align-top">{{ $row->subject_label }}</x-table.td>
                            <x-table.td standartWidth extraClasses="w-40 whitespace-normal break-words text-sm text-zinc-700 align-top">{{ $row->causer_name }}</x-table.td>
                            <x-table.td standartWidth extraClasses="w-52 whitespace-normal align-top">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($row->changed_keys as $key)
                                        <x-small-badge>{{ $key }}</x-small-badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endforelse
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true" standartWidth extraClasses="w-32 whitespace-normal align-top">
                                <x-button mode="slate" class="!h-8 !px-3 !text-xs" wire:click="toggleRow({{ $row->id }})">
                                    {{ $expandedId === $row->id ? __('attendance::history.actions.hide_details') : __('attendance::history.actions.show_details') }}
                                </x-button>
                            </x-table.td>
                        </tr>
                        @if($expandedId === $row->id)
                            <tr wire:key="attendance-history-detail-{{ $row->id }}">
                                <td colspan="7" class="overflow-hidden bg-zinc-50/70 px-4 py-4">
                                    <div class="grid min-w-0 gap-4 lg:grid-cols-2">
                                        <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-4">
                                            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('attendance::history.details.before') }}</p>
                                            <div class="space-y-2 text-sm text-zinc-700">
                                                @forelse($row->before as $label => $value)
                                                    <div class="min-w-0 border-b border-zinc-100 pb-2 last:border-b-0 last:pb-0">
                                                        <div class="flex min-w-0 flex-col gap-1">
                                                            <span class="break-words text-xs font-medium uppercase tracking-wide text-zinc-400">{{ $label }}</span>
                                                            <span class="break-words whitespace-normal">{{ $value }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-zinc-400">{{ __('attendance::history.details.empty') }}</p>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-4">
                                            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('attendance::history.details.after') }}</p>
                                            <div class="space-y-2 text-sm text-zinc-700">
                                                @forelse($row->after as $label => $value)
                                                    <div class="min-w-0 border-b border-zinc-100 pb-2 last:border-b-0 last:pb-0">
                                                        <div class="flex min-w-0 flex-col gap-1">
                                                            <span class="break-words text-xs font-medium uppercase tracking-wide text-zinc-400">{{ $label }}</span>
                                                            <span class="break-words whitespace-normal">{{ $value }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-zinc-400">{{ __('attendance::history.details.empty') }}</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <x-table.empty :rows="8" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $rows->links() }}
    </div>
</div>
