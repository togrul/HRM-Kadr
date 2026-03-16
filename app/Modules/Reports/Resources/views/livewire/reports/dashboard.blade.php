<div class="space-y-6">
    @php
        $tabRoute = fn (string $tab) => $this->tabRoute($tab);
        $tabDescriptions = [
            'overview' => __('reports::dashboard.tab_descriptions.overview'),
            'standard' => __('reports::dashboard.tab_descriptions.standard'),
            'dynamic' => __('reports::dashboard.tab_descriptions.dynamic'),
            'comparisons' => __('reports::dashboard.tab_descriptions.comparisons'),
        ];
        $tabOrder = array_keys($tabDescriptions);
        $activeTabIndex = array_search($activeTab, $tabOrder, true);
        $activeTabIndex = $activeTabIndex === false ? 0 : $activeTabIndex;
    @endphp

    <section class="relative overflow-hidden rounded-[1rem] rounded-bl-none rounded-br-none border border-white/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.56)_0%,rgba(255,255,255,0.26)_100%)] shadow-[0_18px_50px_rgba(15,23,42,0.08),inset_0_1px_0_rgba(255,255,255,0.96)] backdrop-blur-[28px] ring-1 ring-white/55">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(255,255,255,0.72)_0%,rgba(255,255,255,0.18)_52%,transparent_100%)]"></div>

        <div class="relative space-y-6 p-5 sm:p-7 xl:p-8">
            <div class="grid gap-6 xl:grid-cols-[1.35fr,0.65fr] xl:items-end">
                <div class="space-y-4">
                    <p class="text-sm font-medium text-zinc-500">{{ __('reports::dashboard.title') }}</p>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/75 bg-white/35 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500 shadow-[0_10px_24px_rgba(15,23,42,0.05),inset_0_1px_0_rgba(255,255,255,0.98)] backdrop-blur-xl">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ __('reports::dashboard.eyebrow') }}
                    </div>

                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-tight text-zinc-950 sm:text-[2.35rem]">
                            {{ __('reports::dashboard.hero_title') }}
                        </h1>
                        <p class="max-w-3xl text-base leading-8 text-zinc-600">
                            {{ __('reports::dashboard.subtitle') }}
                        </p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-white/15 bg-[linear-gradient(180deg,rgba(9,9,11,0.92)_0%,rgba(24,24,27,0.88)_100%)] px-5 py-5 text-white shadow-[0_22px_44px_rgba(24,24,27,0.22)] backdrop-blur-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('reports::dashboard.labels.analytics_live') }}</p>
                            <p class="text-base leading-7 text-zinc-200">{{ __('reports::dashboard.labels.live_hint') }}</p>
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 px-3 py-1.5 text-xs font-medium text-zinc-300 ring-1 ring-white/10">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                {{ __('reports::dashboard.tabs.'.$activeTab) }}
                            </div>
                        </div>

                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-[1.35rem] bg-emerald-500/12 ring-1 ring-emerald-400/20">
                            <span class="h-3.5 w-3.5 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(74,222,128,0.7)]"></span>
                        </span>
                    </div>
                </div>
            </div>

            <nav
                class="relative overflow-hidden rounded-[2.9rem] p-2"
                style="
                    background: rgba(255, 255, 255, 0.25);
                    border: 1px solid rgba(255, 255, 255, 0.18);
                    box-shadow:
                        0 8px 32px rgba(31, 38, 135, 0.12),
                        inset 2px 2px 0px -2px rgba(255, 255, 255, 0.7),
                        inset 0 0 3px 1px rgba(255, 255, 255, 0.65);
                    backdrop-filter: blur(10px) saturate(180%);
                    -webkit-backdrop-filter: blur(10px) saturate(180%);
                "
            >
                <div class="pointer-events-none absolute inset-0 rounded-[2.9rem] bg-[rgba(255,255,255,0.08)]"></div>
                <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.8),transparent)]"></div>
                <div class="pointer-events-none absolute left-0 top-5 h-[55%] w-px bg-[linear-gradient(180deg,rgba(255,255,255,0.78),transparent,rgba(255,255,255,0.3))]"></div>
                <div class="relative grid grid-cols-4 gap-0.5">
                    @foreach ($tabDescriptions as $tab => $description)
                        <a
                            wire:navigate
                            href="{{ $tabRoute($tab) }}"
                            @class([
                                'group relative flex items-center justify-center rounded-[2.3rem] px-5 py-2 text-center transition',
                                'border border-[rgba(255,255,255,0.42)] shadow-[0_10px_30px_rgba(31,38,135,0.10),inset_1px_1px_0_rgba(255,255,255,0.82),inset_-1px_-1px_0_rgba(255,255,255,0.16),inset_0_0_0_1px_rgba(255,255,255,0.18),inset_0_14px_28px_rgba(255,255,255,0.18)]' => $activeTab === $tab,
                                'border border-transparent' => $activeTab !== $tab,
                            ])
                            style="{{ $activeTab === $tab ? 'background: rgba(255, 255, 255, 0.22); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%);' : '' }}"
                        >
                            @if ($activeTab === $tab)
                                <span class="pointer-events-none absolute inset-x-5 top-3 h-px bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.92),transparent)]"></span>
                                <span class="pointer-events-none absolute inset-x-10 top-6 h-8 rounded-[2rem] bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.14),transparent_60%)] blur-lg"></span>
                            @endif
                            <div class="flex flex-col items-center justify-center gap-1">
                                <span @class([
                                    'text-[14px] font-semibold uppercase tracking-[0.3em] transition',
                                    'text-zinc-500' => $activeTab === $tab,
                                    'text-zinc-400/90' => $activeTab !== $tab,
                                ])>{{ sprintf('%02d', array_search($tab, $tabOrder, true) + 1) }}</span>
                                <p @class([
                                    'text-sm uppercase tracking-tight transition',
                                    'text-zinc-950 font-semibold' => $activeTab === $tab,
                                    'text-zinc-400/95 font-semibold' => $activeTab !== $tab,
                                ])>{{ __('reports::dashboard.tabs.'.$tab) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>
    </section>

    @if ($activeTab === 'overview')
        <livewire:reports.overview lazy />
    @elseif ($activeTab === 'standard')
        <livewire:reports.standard-reports
            lazy
            :key="'reports-standard-'.$report.'-'.$year.'-'.$month.'-'.($structureId ?? 'all')"
            :report="$report"
            :year="$year"
            :month="$month"
            :structure-id="$structureId"
        />
    @elseif ($activeTab === 'dynamic')
        <livewire:reports.dynamic-builder
            lazy
            :key="'reports-dynamic-'.$source.'-'.$groupBy.'-'.$metric.'-'.$year.'-'.$month.'-'.($structureId ?? 'all')"
            :source="$source"
            :group-by="$groupBy"
            :metric="$metric"
            :year="$year"
            :month="$month"
            :structure-id="$structureId"
        />
    @elseif ($activeTab === 'comparisons')
        <livewire:reports.comparisons lazy />
    @endif
</div>
