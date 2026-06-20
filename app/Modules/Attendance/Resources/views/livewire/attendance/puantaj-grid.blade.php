<div
    x-data="{
        detailPopover: null,
        openDetail(event, payload) {
            const rect = event.currentTarget.getBoundingClientRect();
            this.detailPopover = {
                lines: payload.lines || [],
                label: payload.label || '',
                anchorLeft: rect.left,
                anchorTop: rect.top,
                anchorBottom: rect.bottom,
                anchorWidth: rect.width,
                left: 12,
                top: 12,
                maxHeight: 320,
            };
            this.$nextTick(() => {
                this.positionDetail();
                requestAnimationFrame(() => this.positionDetail());
            });
        },
        positionDetail() {
            if (!this.detailPopover || !this.$refs.detailPanel) {
                return;
            }

            const margin = 12;
            const panel = this.$refs.detailPanel;
            const width = panel.offsetWidth || 256;
            const height = panel.offsetHeight || 220;
            const preferredLeft = this.detailPopover.anchorLeft + (this.detailPopover.anchorWidth / 2) - (width / 2);
            const maxLeft = Math.max(margin, window.innerWidth - width - margin);
            const left = Math.min(maxLeft, Math.max(margin, preferredLeft));
            const gap = 8;
            const availableBelow = Math.max(0, window.innerHeight - this.detailPopover.anchorBottom - margin - gap);
            const availableAbove = Math.max(0, this.detailPopover.anchorTop - margin - gap);
            const preferredBelow = availableBelow >= Math.min(height, 220) || availableBelow >= availableAbove;
            const maxHeight = Math.max(180, Math.min(availableBelow, availableAbove, window.innerHeight - (margin * 2)));

            let top = this.detailPopover.anchorBottom + gap;
            let constrainedMaxHeight = Math.max(180, preferredBelow ? availableBelow : availableAbove);

            if (!preferredBelow) {
                top = Math.max(margin, this.detailPopover.anchorTop - Math.min(height, constrainedMaxHeight) - gap);
            }

            if (preferredBelow && top + Math.min(height, constrainedMaxHeight) > window.innerHeight - margin) {
                top = Math.max(margin, window.innerHeight - Math.min(height, constrainedMaxHeight) - margin);
            }

            this.detailPopover = {
                ...this.detailPopover,
                left,
                top,
                maxHeight: Math.max(180, Math.min(constrainedMaxHeight, window.innerHeight - (margin * 2))),
            };
        },
        closeDetail() {
            this.detailPopover = null;
        }
    }"
    x-on:keydown.escape.window="closeDetail()"
    x-on:resize.window.debounce.75ms="positionDetail()"
    x-on:scroll.window.throttle.50ms="positionDetail()"
    class="space-y-4"
>
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
                                <x-table.td extraClasses="relative text-center text-xs {{ $cell['cell_classes'] }}" title="{{ $cell['title'] }}">
                                    @php
                                        $hasDetails = !empty($cell['detail_lines']);
                                        $cellDateLabel = $monthStart->copy()->day($day)->format('d.m.Y');
                                        $cellLabel = $personnel->surname.' '.$personnel->name.' '.$personnel->patronymic.' • '.$cellDateLabel;
                                    @endphp
                                    <button
                                        type="button"
                                        class="w-full min-h-[1.75rem] text-center"
                                        @if($hasDetails)
                                            @click="openDetail($event, { label: @js($cellLabel), lines: @js($cell['detail_lines']) })"
                                        @endif
                                    >
                                        @if(!empty($cell['legend_icon']) && (int) ($cell['worked_minutes'] ?? 0) > 0)
                                            <div class="relative flex min-h-[1.75rem] items-center justify-center">
                                                <span class="font-medium text-zinc-900">{{ $cell['display'] }}</span>
                                                <span class="absolute right-0 top-0 inline-flex h-6 w-6 items-center justify-center rounded-md border border-zinc-200 bg-white/95 shadow-sm">
                                                    <x-dynamic-component :component="$cell['legend_icon']" size="w-4 h-4" :color="$cell['legend_icon_color'] ?? 'text-zinc-600'" />
                                                </span>
                                            </div>
                                        @elseif(!empty($cell['legend_code']) && (int) ($cell['worked_minutes'] ?? 0) > 0)
                                            <div class="relative flex min-h-[1.75rem] items-center justify-center">
                                                <span class="font-medium text-zinc-900">{{ $cell['display'] }}</span>
                                                <span class="absolute right-0 top-0 inline-flex min-w-[1.65rem] items-center justify-center rounded-md border px-1 py-0.5 text-[10px] font-semibold uppercase tracking-tight shadow-sm {{ $cell['legend_code_classes'] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700' }}">
                                                    {{ $cell['legend_code'] }}
                                                </span>
                                            </div>
                                        @elseif(!empty($cell['legend_icon']))
                                            <div class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-zinc-200 bg-white/95 shadow-sm">
                                                <x-dynamic-component :component="$cell['legend_icon']" size="w-4 h-4" :color="$cell['legend_icon_color'] ?? 'text-zinc-600'" />
                                            </div>
                                        @elseif(!empty($cell['legend_code']))
                                            <div class="inline-flex min-w-[2rem] items-center justify-center rounded-md border px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-tight shadow-sm {{ $cell['legend_code_classes'] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700' }}">
                                                {{ $cell['display'] ?: $cell['legend_code'] }}
                                            </div>
                                        @elseif(!empty($cell['icon']))
                                            <div class="inline-flex items-center justify-center">
                                                <x-dynamic-component :component="$cell['icon']" size="w-4 h-4" :color="$cell['icon_color']" />
                                            </div>
                                        @else
                                            {{ $cell['display'] }}
                                        @endif
                                    </button>
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

    <div
        x-cloak
        x-show="detailPopover"
        x-transition.opacity
        class="fixed inset-0 z-40"
        @click="closeDetail()"
    >
        <div
            x-ref="detailPanel"
            x-show="detailPopover"
            x-transition
            class="absolute w-64 overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-3 shadow-2xl"
            :style="detailPopover ? `left:${detailPopover.left}px; top:${detailPopover.top}px; max-height:${detailPopover.maxHeight}px;` : ''"
            @click.stop
        >
            <div class="text-xs font-semibold uppercase tracking-tight text-zinc-400" x-text="detailPopover?.label"></div>
            <div class="mt-2 space-y-1.5">
                <template x-for="line in (detailPopover?.lines || [])" :key="line">
                    <div class="rounded-lg bg-zinc-50 px-2.5 py-2 text-sm leading-5 text-zinc-700" x-text="line"></div>
                </template>
            </div>
        </div>
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
                    <div class="rounded-xl border border-dashed border-zinc-200 bg-white/80 px-3 py-2 text-xs leading-5 text-zinc-500">
                        {{ __('attendance::puantaj.legend.leave_code_note') }}
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($leaveLegend as $item)
                            <div class="rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-700 shadow-sm">
                                <div class="flex items-center gap-3">
                                    @if(!empty($item['icon']))
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 shadow-sm">
                                            <x-dynamic-component :component="$item['icon']" size="w-5 h-5" :color="$item['icon_color'] ?? 'text-zinc-600'" />
                                        </span>
                                    @else
                                        <span class="inline-flex min-w-[3.25rem] items-center justify-center rounded-lg border px-2 py-1 text-[11px] font-semibold uppercase tracking-tight shadow-sm {{ $item['code_classes'] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700' }}">
                                            {{ $item['code'] !== '' ? $item['code'] : __('attendance::puantaj.short_labels.leave') }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium leading-5 text-zinc-900">{{ $item['label'] }}</div>
                                        <div class="text-xs leading-5 text-zinc-500">{{ __('attendance::puantaj.legend.leave_code_tap_hint') }}</div>
                                    </div>
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
