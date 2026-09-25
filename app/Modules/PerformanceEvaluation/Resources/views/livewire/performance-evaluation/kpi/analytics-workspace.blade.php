@php
    $t = 'performance_evaluation::kpi';
    $a = $t.'.analytics';
    $fmt = fn ($value, int $decimals = 1) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, $decimals, '.', ' '), '0'), '.');
    $money = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, '.', ' ');
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    // Spec §9 colour code: below threshold red, threshold–target amber, at/over target green.
    $tone = fn ($achievement, $threshold = 80) => $achievement === null ? 'bg-[#f4f4f5] text-ink-faint' : ((float) $achievement >= 100 ? 'bg-emerald-50 text-emerald-700' : ((float) $achievement >= (float) ($threshold ?? 80) ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'));
    $bar = fn ($achievement, $threshold = 80) => $achievement === null ? 'bg-zinc-300' : ((float) $achievement >= 100 ? 'bg-emerald-500' : ((float) $achievement >= (float) ($threshold ?? 80) ? 'bg-amber-500' : 'bg-rose-500'));
    $mine = $this->mine;
    $team = $this->team;
    $hr = $this->hr;
    $exportButton = 'flex h-10 items-center gap-1 rounded-lg px-2 text-[14px] font-medium text-ink-faint transition hover:bg-white hover:text-ink';
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-4">
    {{-- ───────────── filters ───────────── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="min-w-[13rem]">
            <x-ui.filter-native-select wire:model.live="cycleId">
                @foreach ($this->cycles as $cycle)
                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                @endforeach
            </x-ui.filter-native-select>
        </div>
        @if ($this->isHr)
            <div class="min-w-[14rem]">
                <x-ui.filter-native-select wire:model.live="structureId">
                    <option value="">{{ __($a.'.all_units') }}</option>
                    @foreach ($this->unitOptions as $unitId => $unitName)
                        <option value="{{ $unitId }}">{{ $unitName }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
        @endif
    </div>

    @if ($mine->isEmpty() && $team === null && $hr === null)
        <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-16 text-center">
            <p class="text-[13.5px] font-medium text-ink">{{ __($a.'.empty_title') }}</p>
            <p class="mx-auto mt-1 max-w-md text-[12.5px] leading-5 text-ink-muted">{{ __($a.'.empty_body') }}</p>
        </div>
    @endif

    {{-- ───────────── my KPIs ───────────── --}}
    @if ($mine->isNotEmpty())
        <div class="flex items-baseline justify-between">
            <p class="text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ __($a.'.mine.title') }}</p>
            <p class="text-[12px] text-ink-faint">{{ __($a.'.mine.hint') }}</p>
        </div>
        @foreach ($mine as $entry)
            @php
                $card = $entry['card'];
                $range = collect([$entry['bonus_now'], $entry['bonus_forecast']])->filter(fn ($v) => $v !== null);
            @endphp
            <div wire:key="mine-{{ $card->id }}" class="{{ $section }}">
                <div class="grid grid-cols-2 gap-px bg-hairline-subtle sm:grid-cols-4">
                    <div class="bg-white px-4 py-3">
                        <p class="hrm-eyebrow">{{ __($a.'.mine.position') }}</p>
                        <p class="mt-1 truncate text-[13.5px] font-semibold text-ink">{{ $card->position?->name ?? '—' }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ __($t.'.card_statuses.'.$card->status) }}</p>
                    </div>
                    <div class="bg-white px-4 py-3">
                        <p class="hrm-eyebrow">{{ __($a.'.mine.score_now') }}</p>
                        <p class="hrm-num mt-1 text-[20px] font-semibold leading-none text-ink">{{ $card->effectiveScore() === null ? '—' : $fmt($card->effectiveScore()).'%' }}</p>
                    </div>
                    <div class="bg-white px-4 py-3">
                        <p class="hrm-eyebrow">{{ __($a.'.mine.score_forecast') }}</p>
                        <p class="hrm-num mt-1 text-[20px] font-semibold leading-none {{ ($entry['forecast_score'] ?? 100) < 80 ? 'text-rose-600' : 'text-ink' }}">{{ $entry['forecast_score'] === null ? '—' : $fmt($entry['forecast_score']).'%' }}</p>
                    </div>
                    <div class="bg-white px-4 py-3">
                        <p class="hrm-eyebrow">{{ __($a.'.mine.bonus') }}</p>
                        <p class="hrm-num mt-1 text-[16px] font-semibold leading-tight text-ink">
                            @if ($range->isEmpty())
                                <span class="text-[12.5px] font-normal text-ink-faint">{{ __($a.'.mine.bonus_unknown') }}</span>
                            @else
                                {{ $money($range->min()) }}@if ($range->max() > $range->min()) – {{ $money($range->max()) }}@endif
                                <span class="text-[11.5px] font-medium text-ink-faint">AZN</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="divide-y divide-hairline-subtle border-t border-hairline-subtle">
                    @foreach ($card->items as $item)
                        @php $width = min(100, max(0, (float) ($item->achievement ?? 0) / 1.2)); @endphp
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] items-center gap-4 px-5 py-2.5">
                            <p class="truncate text-[12.5px] font-medium text-ink">{{ $item->kpi?->name }}</p>
                            <div class="relative h-2 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full {{ $bar($item->achievement, $item->threshold) }}" style="width: {{ $width }}%"></div>
                                <span class="absolute inset-y-0 w-px bg-ink/30" style="left: {{ 100 / 1.2 }}%" title="100%"></span>
                            </div>
                            <p class="hrm-num whitespace-nowrap text-right text-[12px] text-ink-soft">
                                {{ $item->achievement === null ? '—' : $fmt($item->achievement).'%' }}
                                @if ($item->forecast_achievement !== null)
                                    <span class="ml-1 text-ink-faint">→ {{ $fmt($item->forecast_achievement) }}%</span>
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if ($this->myTrend->filter(fn ($row) => $row['score'] !== null)->count() > 1)
            @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.trend', ['rows' => $this->myTrend, 'title' => __($a.'.mine.trend')])
        @endif
    @endif

    {{-- ───────────── team panel ───────────── --}}
    @if ($team !== null && $team['cards']->isNotEmpty())
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($a.'.team.title') }} <span class="hrm-num ml-1 text-ink-faint">{{ $team['cards']->count() }}</span></p>
                <p class="text-[11.5px] text-ink-faint">{{ __($a.'.team.hint') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[12.5px]">
                    <thead>
                        <tr class="border-b border-hairline-subtle text-left">
                            <th class="px-5 py-2 font-medium text-ink-muted">{{ __($a.'.columns.personnel') }}</th>
                            @foreach ($team['kpis'] as $code => $name)
                                <th class="px-2 py-2 text-center font-medium text-ink-muted" title="{{ $name }}"><span class="hrm-num text-[11px]">{{ $code }}</span></th>
                            @endforeach
                            <th class="px-5 py-2 text-right font-medium text-ink-muted">{{ __($a.'.columns.score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($team['cards'] as $member)
                            @php
                                $late = $member->stage_due_at && $member->stage_due_at->lt(today());
                                $atRisk = $member->items->contains(fn ($item) => $item->forecast_achievement !== null && $item->threshold !== null && (float) $item->forecast_achievement < (float) $item->threshold);
                                $byCode = $member->items->keyBy(fn ($item) => $item->kpi?->code);
                            @endphp
                            <tr wire:key="team-{{ $member->id }}" class="border-b border-hairline-subtle last:border-b-0">
                                <td class="px-5 py-2.5">
                                    <p class="font-medium text-ink">{{ $member->personnel?->fullname }}</p>
                                    <p class="mt-0.5 flex flex-wrap gap-1 text-[11px]">
                                        <span class="text-ink-faint">{{ __($t.'.card_statuses.'.$member->status) }}</span>
                                        @if ($late)
                                            <span class="rounded bg-rose-50 px-1 text-rose-700">{{ __($a.'.team.late') }}</span>
                                        @endif
                                        @if ($atRisk)
                                            <span class="rounded bg-amber-50 px-1 text-amber-700">{{ __($a.'.team.at_risk') }}</span>
                                        @endif
                                    </p>
                                </td>
                                @foreach ($team['kpis'] as $code => $name)
                                    @php $cell = $byCode->get($code); @endphp
                                    <td class="px-1.5 py-2.5 text-center">
                                        @if ($cell)
                                            <span class="hrm-num inline-block min-w-[3.25rem] rounded-md px-1.5 py-1 text-[11.5px] font-medium {{ $tone($cell->achievement, $cell->threshold) }}">{{ $cell->achievement === null ? '—' : $fmt($cell->achievement, 0).'%' }}</span>
                                        @else
                                            <span class="text-ink-faint">·</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="hrm-num px-5 py-2.5 text-right font-semibold text-ink">{{ $member->effectiveScore() === null ? '—' : $fmt($member->effectiveScore()).'%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ───────────── HR ───────────── --}}
    @if ($hr !== null)
        @php $totalCards = array_sum($hr['statuses']); @endphp
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($a.'.progress.title') }} <span class="hrm-num ml-1 text-ink-faint">{{ $totalCards }}</span></p>
            </div>
            <div class="p-5">
                <div class="flex h-2.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                    @foreach ($hr['statuses'] as $status => $count)
                        @if ($count > 0)
                            <div class="h-full {{ ['draft' => 'bg-zinc-400', 'pending_agreement' => 'bg-violet-500', 'active' => 'bg-sky-500', 'self_review' => 'bg-indigo-500', 'manager_review' => 'bg-amber-500', 'calibration' => 'bg-orange-500', 'approved' => 'bg-teal-500', 'closed' => 'bg-emerald-500'][$status] }}" style="width: {{ $count / max(1, $totalCards) * 100 }}%" title="{{ __($t.'.card_statuses.'.$status) }}: {{ $count }}"></div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($hr['statuses'] as $status => $count)
                        <div class="rounded-xl bg-[#fafafa] px-3 py-2">
                            <p class="text-[11.5px] text-ink-muted">{{ __($t.'.card_statuses.'.$status) }}</p>
                            <p class="hrm-num text-[16px] font-semibold text-ink">{{ $count }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- overdue by manager --}}
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($a.'.overdue.title') }}</p>
                    <button type="button" wire:click="export('overdue')" class="{{ $exportButton }}">Excel</button>
                </div>
                @forelse ($hr['overdue']->where('overdue', '>', 0) as $row)
                    <div class="flex items-center justify-between border-b border-hairline-subtle px-5 py-2.5 text-[12.5px] last:border-b-0">
                        <span class="truncate text-ink">{{ $row['manager'] }}</span>
                        <span class="hrm-num shrink-0 text-ink-muted"><span class="font-semibold text-rose-600">{{ $row['overdue'] }}</span> / {{ $row['cards'] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-[12.5px] text-ink-faint">{{ __($a.'.overdue.none') }}</p>
                @endforelse
            </div>

            {{-- manager strictness --}}
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($a.'.strictness.title') }}</p>
                    <button type="button" wire:click="export('strictness')" class="{{ $exportButton }}">Excel</button>
                </div>
                <p class="border-b border-hairline-subtle px-5 py-2 text-[11.5px] leading-5 text-ink-faint">{{ __($a.'.strictness.hint') }}</p>
                @forelse ($hr['strictness'] as $row)
                    <div class="grid grid-cols-[minmax(0,1fr)_48px_64px_64px] items-center gap-2 border-b border-hairline-subtle px-5 py-2.5 text-[12.5px] last:border-b-0">
                        <span class="truncate text-ink">{{ $row['manager'] }}</span>
                        <span class="hrm-num text-right text-ink-faint">{{ $row['people'] }}</span>
                        <span class="hrm-num text-right text-ink-soft">{{ $fmt($row['average']) }}%</span>
                        <span class="hrm-num text-right font-semibold {{ $row['delta'] <= -5 ? 'text-sky-700' : ($row['delta'] >= 5 ? 'text-amber-700' : 'text-ink-muted') }}">{{ $row['delta'] > 0 ? '+' : '' }}{{ $fmt($row['delta']) }}</span>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-[12.5px] text-ink-faint">{{ __($a.'.no_scores') }}</p>
                @endforelse
            </div>

            {{-- risks --}}
            @foreach (['flight', 'red_twice'] as $risk)
                <div class="{{ $section }}">
                    <div class="{{ $sectionHead }}">
                        <p class="text-[13px] font-semibold text-ink">{{ __($a.'.risks.'.$risk) }} <span class="hrm-num ml-1 text-ink-faint">{{ $hr['risks'][$risk]->count() }}</span></p>
                        <button type="button" wire:click="export('{{ $risk }}')" class="{{ $exportButton }}">Excel</button>
                    </div>
                    <p class="border-b border-hairline-subtle px-5 py-2 text-[11.5px] leading-5 text-ink-faint">{{ __($a.'.risks.'.$risk.'_hint') }}</p>
                    @forelse ($hr['risks'][$risk] as $row)
                        <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-5 py-2.5 text-[12.5px] last:border-b-0">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $row['personnel'] }}</p>
                                <p class="truncate text-[11.5px] text-ink-faint">{{ $row['position'] }}</p>
                            </div>
                            <p class="hrm-num shrink-0 text-right text-ink-soft">
                                {{ $fmt($row['score']) }}%
                                @isset($row['salary_gap'])
                                    <span class="block text-[11px] text-rose-600">{{ __($a.'.risks.salary_gap', ['gap' => $fmt($row['salary_gap'])]) }}</span>
                                @endisset
                            </p>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-[12.5px] text-ink-faint">{{ __($a.'.risks.none') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        {{-- bonus report --}}
        @php $bonus = $hr['bonus']; @endphp
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($a.'.bonus.title') }}</p>
                <p class="hrm-num text-[12px] text-ink-muted">
                    {{ $money($bonus['total']) }} {{ $bonus['currency'] }}
                    @if ($bonus['fund'])
                        <span class="text-ink-faint">/ {{ __($a.'.bonus.fund') }} {{ $money($bonus['fund']) }}</span>
                    @endif
                </p>
            </div>
            <div class="grid grid-cols-1 divide-y divide-hairline-subtle lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                @foreach (['units' => 'bonus_units', 'positions' => 'bonus_positions'] as $group => $exportKey)
                    <div>
                        <div class="flex items-center justify-between px-5 py-2">
                            <p class="hrm-eyebrow">{{ __($a.'.bonus.by_'.$group) }}</p>
                            <button type="button" wire:click="export('{{ $exportKey }}')" class="{{ $exportButton }}">Excel</button>
                        </div>
                        @forelse ($bonus[$group] as $row)
                            <div class="grid grid-cols-[minmax(0,1fr)_40px_110px] items-center gap-2 border-t border-hairline-subtle px-5 py-2 text-[12.5px]">
                                <span class="truncate text-ink">{{ $row['name'] }}</span>
                                <span class="hrm-num text-right text-ink-faint">{{ $row['people'] }}</span>
                                <span class="hrm-num text-right font-semibold text-ink">{{ $money($row['total']) }}</span>
                            </div>
                        @empty
                            <p class="border-t border-hairline-subtle px-5 py-6 text-center text-[12.5px] text-ink-faint">{{ __($a.'.bonus.none') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        @if ($hr['trend']->filter(fn ($row) => $row['score'] !== null)->isNotEmpty())
            @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.trend', ['rows' => $hr['trend'], 'title' => __($a.'.trend_company')])
        @endif
    @endif
</div>
