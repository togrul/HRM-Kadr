<div class="space-y-4">
    <x-surface-card :title="__('attendance::puantaj.title')" icon="icons.calendar-icon">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <div class="md:col-span-2">
                <x-label for="attendance-puantaj-search">{{ __('attendance::puantaj.search.label') }}</x-label>
                <x-livewire-input
                    id="attendance-puantaj-search"
                    mode="gray"
                    name="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('attendance::puantaj.search.placeholder') }}"
                />
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::puantaj.scope.badge') }}</x-small-badge>
            <span>{{ __('attendance::puantaj.scope.description') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="relative min-h-[300px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="$headers" :title="__('attendance::puantaj.title')" bordered>
                    @forelse($rows as $row)
                        @php
                            $personnel = $row['personnel'];
                        @endphp
                        <tr>
                            <x-table.td extraClasses="w-max">
                                <div class="font-medium text-zinc-800">
                                    {{ $personnel->surname }} {{ $personnel->name }} {{ $personnel->patronymic }}
                                </div>
                                <div class="text-xs font-mono font-normal text-zinc-500">{{ $personnel->tabel_no }}</div>
                                @if($row['structure_path'])
                                    <div
                                        class="max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[22rem]"
                                        title="{{ $row['structure_path'] }}"
                                    >
                                        {{ $row['structure_path'] }}
                                    </div>
                                @endif
                            </x-table.td>

                            @foreach($days as $day)
                                @php
                                    $cell = $row['cells'][$day] ?? ['display' => '', 'status' => 'none', 'title' => '', 'cell_classes' => 'text-zinc-400 bg-white'];
                                @endphp
                                <x-table.td extraClasses="text-center text-xs {{ $cell['cell_classes'] }}" title="{{ $cell['title'] }}">
                                    @if(!empty($cell['icon']))
                                        <div class="inline-flex items-center justify-center">
                                            <x-dynamic-component :component="$cell['icon']" size="w-4 h-4" :color="$cell['icon_color']" />
                                        </div>
                                    @else
                                        {{ $cell['display'] }}
                                    @endif
                                </x-table.td>
                            @endforeach
                            <x-table.td extraClasses="text-center font-medium text-zinc-700 stats-cell">
                                {{ $row['total_hours'] }}
                            </x-table.td>
                            <x-table.td extraClasses="text-center font-medium text-zinc-700 stats-cell">
                                {{ $row['total_days'] }}
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="count($headers)" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $personnels->links() }}
    </div>

    <x-surface-card :title="__('attendance::puantaj.legend.title')" icon="icons.info-circle-icon">
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-3 space-y-2">
                <p class="text-xs font-semibold uppercase  text-zinc-400">{{ __('attendance::puantaj.legend.sections.colors') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($statusLegend as $item)
                        <x-small-badge :mode="$item['mode']" :icon="$item['icon']">{{ $item['label'] }}</x-small-badge>
                    @endforeach
                </div>
                <div class="grid gap-2 text-xs text-zinc-500 md:grid-cols-2">
                    @foreach($statusLegend as $item)
                        <div><span class="font-medium text-zinc-700">{{ $item['label'] }}:</span> {{ $item['description'] }}</div>
                    @endforeach
                </div>
            </div>

            @if($leaveLegend !== [])
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-3 space-y-2">
                    <p class="text-xs font-semibold uppercase  text-zinc-400">{{ __('attendance::puantaj.legend.sections.leave_types') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($leaveLegend as $item)
                            <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-50">
                                    <x-dynamic-component :component="$item['icon']" size="w-4 h-4" :color="$item['icon_color']" />
                                </span>
                                <div class="min-w-0">
                                    <div class="font-medium leading-none">{{ $item['label'] }}</div>
                                    @if($item['code'] !== '')
                                        <div class="mt-1 text-xs font-mono uppercase tracking-[0.12em] text-zinc-500">{{ $item['code'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($calendarOverrides !== [])
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-3 space-y-2">
                    <p class="text-xs font-semibold uppercase  text-zinc-400">{{ __('attendance::puantaj.legend.sections.calendar') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($calendarOverrides as $item)
                            <x-small-badge :mode="$item['mode']" :icon="$item['icon']">{{ $item['label'] }}</x-small-badge>
                        @endforeach
                    </div>
                    <div class="grid gap-2 text-xs text-zinc-500 md:grid-cols-2">
                        @foreach($calendarOverrides as $item)
                            <div><span class="font-medium text-zinc-700">{{ $item['label'] }}:</span> {{ $item['description'] }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-surface-card>
</div>
