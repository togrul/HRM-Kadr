@php
    $kpi = $this->payload['kpis'];
    $topStructures = $this->payload['top_structures'];
    $movementSnapshot = $this->payload['movement_snapshot'];
    $headcountTrend = $this->payload['headcount_trend'] ?? [];
    $topStructureMax = max(1, (int) collect($topStructures)->max('value'));
    $attendanceCoverage = max(0, min(100, (float) ($kpi['attendance_coverage_pct'] ?? 0)));
    $absenceRate = max(0, min(100, (float) ($kpi['attendance_absence_rate_pct'] ?? 0)));
    $trainingReach = $kpi['active_personnel_count'] > 0
        ? min(100, round(((float) $kpi['delivered_trainings_count'] / max(1, (int) $kpi['active_personnel_count'])) * 100, 1))
        : 0;
    $performanceCapture = $kpi['active_personnel_count'] > 0
        ? min(100, round(((float) $kpi['performance_forms_count'] / max(1, (int) $kpi['active_personnel_count'])) * 100, 1))
        : 0;
    $trendPoints = collect($headcountTrend)
        ->values()
        ->map(fn (array $row) => ['label' => $row['label'], 'value' => $row['value']]);
    $reportCardRows = [
        ['label' => __('reports::dashboard.labels.coverage_hint'), 'value' => $attendanceCoverage],
        ['label' => __('reports::dashboard.labels.training_reach'), 'value' => $trainingReach],
        ['label' => __('reports::dashboard.labels.performance_capture'), 'value' => $performanceCapture],
        ['label' => __('reports::dashboard.overview.cards.absence_rate'), 'value' => max(0, 100 - $absenceRate)],
    ];
    $reportCardUrl = route('reports', [
        'tab' => 'standard',
        'report' => 'attendance',
        'year' => $year,
        'month' => $month,
        'structure_id' => $structureId,
    ]);
    $reportTiles = [
        [
            'title' => __('reports::dashboard.standard.types.headcount'),
            'description' => __('reports::dashboard.standard.descriptions.headcount'),
            'metric' => $kpi['active_personnel_count'],
            'caption' => __('reports::dashboard.fields.personnel_count'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'structure', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.demographics'),
            'description' => __('reports::dashboard.standard.descriptions.demographics'),
            'metric' => $kpi['structures_covered'],
            'caption' => __('reports::dashboard.overview.cards.structures_covered'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'gender', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.attendance'),
            'description' => __('reports::dashboard.standard.descriptions.attendance'),
            'metric' => number_format($attendanceCoverage, 1).'%',
            'caption' => __('reports::dashboard.overview.cards.attendance_coverage'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'attendance', 'group_by' => 'structure', 'metric' => 'worked_hours', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.training'),
            'description' => __('reports::dashboard.standard.descriptions.training'),
            'metric' => $kpi['delivered_trainings_count'],
            'caption' => __('reports::dashboard.overview.cards.delivered_trainings'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'training', 'group_by' => 'quarter', 'metric' => 'participants_count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.standard.types.performance'),
            'description' => __('reports::dashboard.standard.descriptions.performance'),
            'metric' => $kpi['performance_forms_count'],
            'caption' => __('reports::dashboard.overview.cards.forms_total'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'performance', 'group_by' => 'category', 'metric' => 'forms_count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
        [
            'title' => __('reports::dashboard.dynamic.groups.status'),
            'description' => __('reports::dashboard.tab_descriptions.dynamic'),
            'metric' => count($headcountTrend),
            'caption' => __('reports::dashboard.labels.period_points'),
            'href' => route('reports', ['tab' => 'dynamic', 'source' => 'personnel', 'group_by' => 'status', 'metric' => 'count', 'year' => $year, 'month' => $month, 'structure_id' => $structureId]),
        ],
    ];
@endphp

<div class="space-y-6 px-2 py-1">
    <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.05)]">
        <div class="grid gap-4 border-b border-zinc-200 px-3 py-2 lg:grid-cols-[1.1fr,0.9fr] lg:px-7 lg:py-3">
            <div class="space-y-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('reports::dashboard.overview.eyebrow') }}</p>
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold tracking-tight text-zinc-950 sm:text-[2rem]">{{ __('reports::dashboard.overview.title') }}</h2>
                    <p class="max-w-3xl text-sm leading-8 text-zinc-600">{{ __('reports::dashboard.overview.description') }}</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.year') }}</label>
                    <select wire:model.live="year" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-3 text-sm text-zinc-800 shadow-sm transition focus:border-zinc-400 focus:bg-white">
                        @foreach (range(now()->year - 4, now()->year + 1) as $yearOption)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.month') }}</label>
                    <select wire:model.live="month" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-3 text-sm text-zinc-800 shadow-sm transition focus:border-zinc-400 focus:bg-white">
                        @foreach (range(1, 12) as $monthOption)
                            <option value="{{ $monthOption }}">{{ \Carbon\Carbon::create()->month($monthOption)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.structure') }}</label>
                    <select wire:model.live="structureId" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-3 text-sm text-zinc-800 shadow-sm transition focus:border-zinc-400 focus:bg-white">
                        <option value="">{{ __('reports::dashboard.labels.all_structures') }}</option>
                        @foreach ($structureOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="px-3 py-2 xl:px-5 xl:py-3">
            <div class="grid gap-5 grid-cols-1 md:grid-cols-3">
                <div class="overflow-hidden rounded-[1.7rem] border border-zinc-200/90 bg-white shadow-[0_8px_24px_rgba(15,23,42,0.05)]">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-2">
                        <div class="space-y-1 py-1">
                            <p class="text-[1.4rem] font-semibold tracking-tight text-zinc-950">{{ __('reports::dashboard.labels.report_card') }}</p>
                            <p class="text-[13px] text-zinc-500">{{ __('reports::dashboard.labels.last_evaluated', ['period' => \Carbon\Carbon::create()->month($month)->translatedFormat('F').' '.$year]) }}</p>
                        </div>
                        <a
                            wire:navigate
                            href="{{ $reportCardUrl }}"
                            class="inline-flex h-8 items-center rounded-xl border border-zinc-900 bg-black px-3 text-[12px] font-semibold uppercase tracking-tight text-zinc-50 shadow-[0_1px_2px_rgba(15,23,42,0.05)]"
                        >{{ __('reports::dashboard.labels.view_action') }}</a>
                    </div>

                        <div class="space-y-4 px-6 py-4">
                        @foreach ($reportCardRows as $row)
                            @php
                                $barValue = max(0, min(100, (float) $row['value']));
                                $visibleBarValue = $barValue > 0 ? max(6, $barValue) : 3;
                            @endphp
                            <div class="space-y-2">
                                <p class="text-[14px] font-medium leading-5 text-zinc-500">{{ $row['label'] }}</p>
                                <div class="flex items-center gap-3">
                                    <div class="h-4 flex-1 rounded-md border border-zinc-100 bg-zinc-50 px-[5px] py-[4px]">
                                        <div
                                            class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_7px,transparent_7px_11px)]"
                                            style="width: {{ $visibleBarValue }}%"
                                        ></div>
                                    </div>
                                    <p class="w-16 flex-none text-right text-[14px] font-semibold tracking-tight text-zinc-950">{{ number_format((float) $row['value'], 1) }}%</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="md:col-span-2 overflow-hidden rounded-[1.9rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <div class="grid min-h-full xl:grid-cols-5">
                        <div class="xl:col-span-3 border-b border-zinc-200 p-5 xl:border-b-0 xl:border-r xl:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('reports::dashboard.labels.trend_live') }}</p>
                                    <h3 class="mt-2 text-[1.4rem] font-semibold tracking-tight text-zinc-950">{{ __('reports::dashboard.labels.headcount_trend') }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">{{ __('reports::dashboard.labels.recent_window') }}</p>
                                </div>
                                <div class="relative inline-flex items-center rounded-full border border-zinc-200 bg-white p-1 text-xs text-zinc-600 shadow-[0_8px_16px_rgba(15,23,42,0.06)]">
                                    <span
                                        aria-hidden="true"
                                        class="pointer-events-none absolute bottom-1 top-1 z-0 rounded-full bg-[linear-gradient(180deg,#27272a_0%,#18181b_100%)] shadow-[0_6px_14px_rgba(24,24,27,.14),inset_0_1px_0_rgba(255,255,255,.08)] transition-all duration-200"
                                        style="{{ $trendWindow === 6 ? 'left:4px;width:calc(50% - 4px);' : 'left:calc(50%);width:calc(50% - 4px);' }}"
                                    ></span>
                                    @foreach ([6, 12] as $range)
                                        <button
                                            type="button"
                                            wire:click="setTrendWindow({{ $range }})"
                                            wire:loading.attr="disabled"
                                            wire:target="setTrendWindow"
                                            aria-pressed="{{ $trendWindow === $range ? 'true' : 'false' }}"
                                            class="relative z-10 inline-flex h-8 min-w-[50px] items-center justify-center rounded-full px-3 text-[13px] font-semibold tracking-[-0.01em] transition"
                                            style="{{ $trendWindow === $range ? 'color:#fff;' : 'color:#52525b;' }}"
                                        >
                                            {{ $range }}A
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="relative rounded-[1.4rem] border border-zinc-100 bg-[linear-gradient(180deg,#ffffff_0%,#fbfbfc_100%)] px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]">
                                    <div
                                        data-reports-trend-chart
                                        data-points='@json($trendPoints->values()->all())'
                                        class="relative w-full"
                                    >
                                        <div
                                            data-chart-tooltip
                                            class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-[1.15rem] border border-zinc-200/90 bg-white/95 px-3 py-2.5 text-xs shadow-[0_20px_45px_rgba(15,23,42,0.12)] backdrop-blur-sm transition-opacity duration-150"
                                            style="opacity:0;"
                                        >
                                            <p data-chart-tooltip-label class="text-[12px] font-semibold tracking-tight text-zinc-950"></p>
                                            <p class="mt-1 text-[11px] text-zinc-500"><span data-chart-tooltip-value></span> {{ __('reports::dashboard.fields.personnel_count') }}</p>
                                        </div>
                                        <canvas data-chart-canvas class="block h-[250px] w-full"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-2 grid grid-cols-2 gap-px bg-zinc-200/70">
                            @foreach ([
                                ['label' => __('reports::dashboard.overview.cards.active_personnel'), 'value' => $kpi['active_personnel_count'], 'hint' => __('reports::dashboard.standard.types.headcount')],
                                ['label' => __('reports::dashboard.overview.cards.structures_covered'), 'value' => $kpi['structures_covered'], 'hint' => __('reports::dashboard.labels.scope_summary', ['count' => $kpi['structures_covered']])],
                                ['label' => __('reports::dashboard.overview.cards.new_hires'), 'value' => $kpi['new_hires'], 'hint' => __('reports::dashboard.labels.last_evaluated', ['period' => $year])],
                                ['label' => __('reports::dashboard.overview.cards.exits'), 'value' => $kpi['exits'], 'hint' => __('reports::dashboard.overview.cards.absence_rate').': '.number_format($absenceRate, 1).'%' ],
                            ] as $item)
                                <div class="grid min-h-[176px] grid-rows-[3.5rem,1fr,5rem] bg-white px-5 py-5">
                                    <div class="space-y-2">
                                        <p class="text-[12px] font-semibold uppercase tracking-tight text-zinc-400">{{ $item['label'] }}</p>
                                        <span class="block h-px w-full bg-zinc-100"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <p class="text-[3.35rem] font-semibold leading-[0.88] tracking-[-0.055em] text-zinc-950">{{ $item['value'] }}</p>
                                    </div>
                                    <div class="flex items-end">
                                        <p class="max-w-[11rem] text-[13px] leading-6 tracking-tighter text-zinc-400">{{ $item['hint'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="overflow-hidden rounded-[2rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
            <div class="border-b border-zinc-200 px-5 py-4 lg:px-6">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('reports::dashboard.labels.structure_landscape') }}</p>
                <h3 class="mt-2 text-xl font-semibold tracking-tight text-zinc-950">{{ __('reports::dashboard.overview.cards.top_structures') }}</h3>
            </div>

                <div class="space-y-5 px-5 py-4">
                    @forelse ($topStructures as $row)
                    @php
                        $width = max(10, min(100, round(($row['value'] / $topStructureMax) * 100, 1)));
                        $share = round(($row['value'] / max(1, $kpi['active_personnel_count'])) * 100, 1);
                    @endphp
                    <div class="rounded-[1.65rem] border border-zinc-200/90 bg-white px-5 py-5 shadow-[0_8px_18px_rgba(15,23,42,0.04)]">
                        <div class="flex items-start justify-between gap-6">
                            <div class="min-w-0">
                                <p class="text-[14px] font-semibold tracking-tight text-zinc-950">{{ $row['label'] }}</p>
                                <p class="mt-1.5 text-sm leading-6 text-zinc-500">{{ __('reports::dashboard.labels.structure_share', ['share' => $share]) }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xl font-semibold leading-none tracking-tight text-zinc-950">{{ $row['value'] }}</p>
                                <p class="mt-1 text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('reports::dashboard.fields.personnel_count') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 h-[18px] rounded-md border border-zinc-100 bg-zinc-50 px-[6px] py-[5px] shadow-[inset_0_1px_2px_rgba(15,23,42,0.05)]">
                            <div
                                class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_8px,transparent_8px_12px)]"
                                style="width: {{ $width }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    @include('reports::components.report-placeholder', [
                        'title' => __('reports::dashboard.overview.cards.top_structures'),
                        'message' => __('reports::dashboard.empty.no_report_data'),
                    ])
                @endforelse
            </div>
        </div>

        <div class="space-y-5 lg:col-span-2">
            <div class="overflow-hidden rounded-[2rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <div class="border-b border-zinc-200 px-5 py-4 lg:px-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('reports::dashboard.labels.quick_reports') }}</p>
                    <h3 class="mt-2 text-xl font-semibold tracking-tight text-zinc-950">{{ __('reports::dashboard.labels.launch_surface') }}</h3>
                </div>

                <div class="grid gap-3 px-5 py-3 sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($reportTiles as $tile)
                        <a wire:navigate href="{{ $tile['href'] }}" class="group flex min-h-[224px] min-w-0 flex-col justify-between overflow-hidden rounded-[1.55rem] border border-zinc-200/90 bg-white p-5 shadow-[0_8px_18px_rgba(15,23,42,0.03)] transition hover:border-zinc-300 hover:shadow-[0_12px_24px_rgba(15,23,42,0.06)]">
                            <div class="min-w-0 space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ $tile['caption'] }}</p>
                                    <span class="shrink-0 rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-sm font-semibold text-zinc-950">{{ $tile['metric'] }}</span>
                                </div>
                                <h4 class="max-w-[18rem] text-lg font-semibold leading-[1] tracking-tight text-zinc-950">{{ $tile['title'] }}</h4>
                                <p class="max-w-[24rem] text-sm leading-7 text-zinc-500">{{ $tile['description'] }}</p>
                            </div>
                            <div class="mt-5 inline-flex items-center self-start rounded-full bg-zinc-950 px-3 py-1.5 text-xs font-semibold text-white">
                                {{ __('reports::dashboard.labels.open_dynamic') }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
