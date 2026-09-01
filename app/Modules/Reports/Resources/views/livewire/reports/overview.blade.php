@php
    $kpi = $this->payload['kpis'];
    $trend = collect($this->payload['headcount_trend'] ?? []);
    $genderSplit = $this->payload['gender_split'] ?? [];
    $ageSplit = $this->payload['age_split'] ?? [];
    $topStructures = $this->payload['top_structures'];
    $topStructureMax = max(1, (int) collect($topStructures)->max('value'));

    $num = fn ($value): string => number_format((float) $value, 0, ',', ' ');

    // Net movement inside the report month — the headcount tile's delta is the change the
    // period actually produced, not a year-over-year ratio like the movement tiles.
    $lastPoint = $trend->last();
    $netMovement = (int) ($lastPoint['joins'] ?? 0) - (int) ($lastPoint['exits'] ?? 0);

    $movementMax = max(1, (int) $trend->flatMap(fn (array $row) => [$row['joins'] ?? 0, $row['exits'] ?? 0])->max());
    $genderTotal = max(1, (int) collect($genderSplit)->sum('value'));
    $ageTotal = max(1, (int) collect($ageSplit)->sum('value'));

    $metrics = [
        [
            'label' => __('reports::dashboard.overview.cards.active_personnel'),
            'value' => $num($kpi['active_personnel_count']),
            'delta' => $netMovement,
            'suffix' => '',
        ],
        [
            'label' => __('reports::dashboard.overview.cards.new_hires_year'),
            'value' => $num($kpi['new_hires']),
            'delta' => $kpi['new_hires_delta_pct'] ?? null,
            'suffix' => '%',
        ],
        [
            'label' => __('reports::dashboard.overview.cards.exits_year'),
            'value' => $num($kpi['exits']),
            'delta' => $kpi['exits_delta_pct'] ?? null,
            'suffix' => '%',
            'invert' => true,
        ],
        [
            'label' => __('reports::dashboard.overview.cards.avg_worked_hours'),
            'value' => $num($kpi['avg_worked_hours']),
            'caption' => __('reports::dashboard.labels.hours_per_month'),
        ],
        [
            'label' => __('reports::dashboard.overview.cards.overtime_hours'),
            'value' => $num($kpi['overtime_hours']),
            'delta' => $kpi['overtime_delta_pct'] ?? null,
            'suffix' => '%',
        ],
    ];

    $reportTiles = [
        [
            'title' => __('reports::dashboard.standard.types.headcount'),
            'metric' => $num($kpi['active_personnel_count']),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'structure', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.demographics'),
            'metric' => $num($kpi['structures_covered']),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'gender', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.attendance'),
            'metric' => number_format((float) $kpi['attendance_coverage_pct'], 1).'%',
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'attendance', 'group_by' => 'structure', 'metric' => 'worked_hours', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.training'),
            'metric' => $num($kpi['delivered_trainings_count']),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'training', 'group_by' => 'quarter', 'metric' => 'participants_count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.performance'),
            'metric' => $num($kpi['performance_forms_count']),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'performance', 'group_by' => 'category', 'metric' => 'forms_count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.overview.cards.turnover_rate'),
            'metric' => number_format((float) $kpi['turnover_rate_pct'], 1).'%',
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'status', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
    ];
@endphp

<div class="flex flex-col gap-4">
    {{-- ===================== headline metrics ===================== --}}
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($metrics as $metric)
            <div class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                <p class="hrm-eyebrow">{{ $metric['label'] }}</p>
                <div class="mt-1.5 flex items-baseline gap-2">
                    <p class="hrm-num text-[26px] font-semibold leading-none tracking-[-0.035em] text-ink">{{ $metric['value'] }}</p>

                    @if (($metric['delta'] ?? null) !== null && (float) $metric['delta'] !== 0.0)
                        @php $positive = ((float) $metric['delta'] > 0) !== (bool) ($metric['invert'] ?? false); @endphp
                        <span @class([
                            'hrm-num text-[12px] font-semibold',
                            'text-[#059669]' => $positive,
                            'text-[#e11d48]' => ! $positive,
                        ])>
                            {{ (float) $metric['delta'] > 0 ? '+' : '−' }}{{ number_format(abs((float) $metric['delta']), $metric['suffix'] === '%' ? 1 : 0, ',', ' ') }}{{ $metric['suffix'] }}
                        </span>
                    @elseif (! empty($metric['caption']))
                        <span class="text-[12px] text-ink-faint">{{ $metric['caption'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
        {{-- ===================== hires vs exits ===================== --}}
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">
                    {{ __('reports::dashboard.overview.cards.movement_chart', ['year' => $year]) }}
                </h2>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] text-ink-muted">
                        <span class="h-2 w-2 rounded-[3px] bg-ink"></span>{{ __('reports::dashboard.fields.joined_count') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] text-ink-muted">
                        <span class="h-2 w-2 rounded-[3px] bg-[#d4d4d8]"></span>{{ __('reports::dashboard.fields.exits_count') }}
                    </span>
                </div>
            </div>

            <div class="px-4 py-4">
                @if ($trend->isEmpty())
                    <x-ui.empty-state icon="icons.report-chart-icon" :message="__('reports::dashboard.empty.no_report_data')" />
                @else
                    <div class="hrm-scroll flex items-end gap-2 overflow-x-auto">
                        @foreach ($trend as $point)
                            @php
                                $joins = (int) ($point['joins'] ?? 0);
                                $exits = (int) ($point['exits'] ?? 0);
                            @endphp
                            <div class="flex min-w-[38px] flex-1 flex-col items-center gap-2" wire:key="reports-movement-{{ $loop->index }}">
                                <div class="flex h-[170px] w-full items-end justify-center gap-1">
                                    <div class="w-1/2 max-w-[16px] rounded-t-[4px] bg-ink"
                                        style="height: {{ $joins > 0 ? max(3, round($joins / $movementMax * 100)) : 2 }}%"
                                        title="{{ __('reports::dashboard.fields.joined_count') }}: {{ $joins }}"></div>
                                    <div class="w-1/2 max-w-[16px] rounded-t-[4px] bg-[#d4d4d8]"
                                        style="height: {{ $exits > 0 ? max(3, round($exits / $movementMax * 100)) : 2 }}%"
                                        title="{{ __('reports::dashboard.fields.exits_count') }}: {{ $exits }}"></div>
                                </div>
                                <span class="text-[11px] text-ink-faint">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ===================== gender + age ===================== --}}
        <section class="flex flex-col gap-4">
            <div class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('reports::dashboard.fields.gender_distribution') }}</h2>
                </div>
                <div class="space-y-3 px-4 py-3.5">
                    @foreach ($genderSplit as $row)
                        <div wire:key="reports-gender-{{ $row['key'] }}">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-[12.5px] text-ink-soft">{{ $row['label'] }}</span>
                                <span class="hrm-num text-[13px] font-semibold text-ink">{{ $num($row['value']) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full {{ $row['key'] === 'female' ? 'bg-[#a1a1aa]' : 'bg-ink' }}"
                                    style="width: {{ round($row['value'] / $genderTotal * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('reports::dashboard.fields.age_distribution') }}</h2>
                </div>
                <div class="space-y-3 px-4 py-3.5">
                    @foreach ($ageSplit as $row)
                        <div wire:key="reports-age-{{ $row['key'] }}">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="hrm-num text-[12.5px] text-ink-soft">{{ __('reports::dashboard.age_buckets.'.$row['key']) }}</span>
                                <span class="hrm-num text-[13px] font-semibold text-ink">{{ $num($row['value']) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-ink" style="width: {{ round($row['value'] / $ageTotal * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        {{-- ===================== biggest structures ===================== --}}
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('reports::dashboard.overview.cards.top_structures') }}</h2>
            </div>
            <div class="space-y-3 px-4 py-3.5">
                @forelse ($topStructures as $row)
                    <div wire:key="reports-structure-{{ $loop->index }}">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="min-w-0 truncate text-[12.5px] text-ink-soft">{{ $row['label'] }}</span>
                            <span class="hrm-num shrink-0 text-[13px] font-semibold text-ink">{{ $num($row['value']) }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                            <div class="h-full rounded-full bg-ink" style="width: {{ max(3, round($row['value'] / $topStructureMax * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.report-chart-icon" :message="__('reports::dashboard.empty.no_report_data')" />
                @endforelse
            </div>
        </section>

        {{-- ===================== one-click reports ===================== --}}
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('reports::dashboard.labels.quick_reports') }}</h2>
                <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('reports::dashboard.labels.launch_surface') }}</p>
            </div>
            <div class="grid gap-2 p-3 sm:grid-cols-2">
                @foreach ($reportTiles as $tile)
                    <a wire:navigate href="{{ $tile['href'] }}"
                        class="flex items-center justify-between gap-2 rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-2.5 transition hover:border-zinc-300 hover:bg-white">
                        <span class="min-w-0 truncate text-[12.5px] font-medium text-ink-soft">{{ $tile['title'] }}</span>
                        <span class="hrm-num shrink-0 text-[13px] font-semibold text-ink">{{ $tile['metric'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
