@php
    $t = 'performance_evaluation::kpi';
    $cardBadge = [
        'draft' => ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'],
        'pending_agreement' => ['bg-violet-50 text-violet-700', 'bg-violet-500'],
        'active' => ['bg-sky-50 text-sky-700', 'bg-sky-500'],
        'self_review' => ['bg-indigo-50 text-indigo-700', 'bg-indigo-500'],
        'manager_review' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'calibration' => ['bg-orange-50 text-orange-700', 'bg-orange-500'],
        'approved' => ['bg-teal-50 text-teal-700', 'bg-teal-500'],
        'closed' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
    ];
    // Spec §9 colour code: below threshold red, threshold–target amber, at/over target green.
    $scoreTone = fn ($score, $threshold = 80) => $score === null ? 'text-ink-faint' : ((float) $score >= 100 ? 'text-emerald-600' : ((float) $score >= (float) ($threshold ?? 80) ? 'text-amber-600' : 'text-rose-600'));
    $fmt = fn ($value, int $decimals = 2) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, $decimals, '.', ' '), '0'), '.');
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $field = 'h-10 w-full rounded-xl border border-hairline bg-white px-3 text-[13px] text-ink focus:border-zinc-400 focus:outline-none';
    $card = $this->card;
    $role = $this->role;
    $reasonActions = collect(\App\Models\PerformanceScorecard::TRANSITIONS)->filter(fn ($rule) => $rule['reason'] ?? false)->keys()->all();
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-4">
    {{-- ───────────── toolbar ───────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="min-w-[13rem]">
                <x-ui.filter-native-select wire:model.live="cycleId">
                    @foreach ($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
            @unless ($card)
                <div class="min-w-[12rem]">
                    <x-ui.filter-native-select wire:model.live="statusFilter">
                        <option value="">{{ __($t.'.all_statuses') }}</option>
                        @foreach (\App\Models\PerformanceScorecard::STATUSES as $status)
                            <option value="{{ $status }}">{{ __($t.'.card_statuses.'.$status) }}</option>
                        @endforeach
                    </x-ui.filter-native-select>
                </div>
            @endunless
        </div>

        @can('manage-performance-evaluation')
            @if ($cycleId && ! $card)
                <div class="flex flex-wrap items-center gap-2">
                    <x-pill-button variant="secondary" wire:click="syncMetrics" wire:loading.attr="disabled" wire:target="syncMetrics" title="{{ __($t.'.metrics.sync_hint') }}">
                        <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="syncMetrics" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/></svg>
                        {{ __($t.'.metrics.sync') }}
                    </x-pill-button>
                    <x-pill-button variant="secondary" wire:click="toggleImport" class="{{ $showImport ? '!border-zinc-400 !bg-white !text-ink' : '' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="m9 13 2 2 4-4"/></svg>
                        {{ __($t.'.import.open') }}
                    </x-pill-button>
                    <x-pill-button variant="primary" wire:click="generate" wire:loading.attr="disabled" wire:target="generate">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __($t.'.actions.generate_cards') }}
                    </x-pill-button>
                </div>
            @endif
        @endcan
    </div>

    @if ($showImport && ! $card)
        {{-- ───────────── Excel import ───────────── --}}
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($t.'.import.title') }}</p>
                <button type="button" wire:click="toggleImport" class="text-[12px] font-medium text-ink-faint hover:text-ink">{{ __($t.'.actions.cancel') }}</button>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                @foreach ([1, 2, 3] as $step)
                    <div class="flex gap-3">
                        <span class="hrm-num flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-[12px] font-semibold text-white">{{ $step }}</span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.import.steps.'.$step.'.title') }}</p>
                            <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($t.'.import.steps.'.$step.'.body') }}</p>
                            @if ($step === 1)
                                <button type="button" wire:click="downloadActualsTemplate" class="mt-2 inline-flex h-8 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[12px] font-semibold text-ink-soft hover:border-zinc-300 hover:text-ink">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                                    {{ __($t.'.import.download') }}
                                </button>
                            @elseif ($step === 3)
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <label class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 bg-[#fafafa] px-3 text-[12px] font-medium text-ink-soft hover:border-zinc-400">
                                        <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="hidden">
                                        <span class="max-w-[10rem] truncate">{{ $importFile ? $importFile->getClientOriginalName() : __($t.'.import.choose') }}</span>
                                    </label>
                                    <button type="button" wire:click="importActuals" wire:loading.attr="disabled" wire:target="importActuals,importFile" @disabled(! $importFile) class="inline-flex h-8 items-center rounded-lg bg-ink px-3 text-[12px] font-semibold text-white hover:bg-ink-hover disabled:opacity-40">{{ __($t.'.import.submit') }}</button>
                                </div>
                                @error('importFile') <x-validation>{{ $message }}</x-validation> @enderror
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($importErrors !== [])
                <div class="border-t border-hairline-subtle bg-rose-50/50 px-5 py-3">
                    <p class="text-[12.5px] font-semibold text-rose-700">{{ __($t.'.import.failed', ['count' => count($importErrors)]) }}</p>
                    <ul class="mt-1.5 max-h-40 space-y-0.5 overflow-y-auto text-[12px] text-rose-700">
                        @foreach ($importErrors as $line => $message)
                            <li><span class="hrm-num font-semibold">{{ __($t.'.import.row', ['row' => $line]) }}</span> — {{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if ($card)
        @php
            [$badgeTone, $badgeDot] = $cardBadge[$card->status] ?? $cardBadge['draft'];
            $overdue = $card->stage_due_at && $card->stage_due_at->lt(today());
            $competencies = $this->competencies;
            $expectedCheckins = app(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService::class)->expectedCheckins($card);
            $stageIndex = array_search($card->status, \App\Models\PerformanceScorecard::STATUSES, true);
        @endphp

        {{-- ───────────── card head ───────────── --}}
        <div class="{{ $section }}">
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-3.5">
                    <x-avatar :name="$card->personnel?->fullname ?? '—'" size="lg" />
                    <div class="min-w-0">
                        <button type="button" wire:click="closeCard" class="text-[12px] font-medium text-ink-faint hover:text-ink">← {{ __($t.'.actions.back_to_list') }}</button>
                        <h2 class="mt-1 truncate text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $card->personnel?->fullname }}</h2>
                        <p class="mt-0.5 text-[12.5px] text-ink-muted">
                            {{ $card->position?->name ?? '—' }} ·
                            {{ __($t.'.fields.manager') }}: {{ $card->manager ? trim($card->manager->surname.' '.$card->manager->name) : '—' }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11.5px]">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-medium {{ $badgeTone }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                                {{ __($t.'.card_statuses.'.$card->status) }}
                            </span>
                            <span class="hrm-num rounded-md bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ $card->valid_from?->format('d.m.Y') }} – {{ $card->valid_to?->format('d.m.Y') }}</span>
                            @if ((float) $card->prorata_factor < 1)
                                <span class="rounded-md bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ __($t.'.fields.prorata') }} <span class="hrm-num">{{ $fmt((float) $card->prorata_factor * 100) }}%</span></span>
                            @endif
                            @if ($card->stage_due_at && $card->status !== 'closed')
                                <span class="rounded-md px-2 py-0.5 {{ $overdue ? 'bg-rose-50 text-rose-700' : 'bg-[#f4f4f5] text-ink-muted' }}">
                                    {{ __($t.'.stage_due') }} <span class="hrm-num">{{ $card->stage_due_at->format('d.m.Y') }}</span>
                                </span>
                            @endif
                            @if ((int) $card->leave_days > 0)
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-amber-700" title="{{ __($t.'.leave.chip_hint', ['threshold' => config('performance_evaluation.kpi.long_leave_days', 30)]) }}">{{ __($t.'.leave.chip', ['days' => $card->leave_days]) }}</span>
                            @endif
                            @if ($card->closure_reason)
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-amber-700">{{ __($t.'.closure_reasons.'.$card->closure_reason) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid shrink-0 grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        'kpi_score' => $card->kpi_score,
                        'competency_score' => (float) $card->competency_weight_share > 0 ? $card->competency_score : null,
                        'final_score' => $card->final_score,
                        'calibrated_score' => $card->calibrated_score,
                    ] as $label => $value)
                        @continue($label === 'competency_score' && (float) $card->competency_weight_share <= 0)
                        @continue($label === 'calibrated_score' && $value === null)
                        <div class="min-w-[92px] rounded-xl border border-hairline-subtle bg-[#fafafa] px-3 py-2">
                            <p class="hrm-eyebrow">{{ __($t.'.fields.'.$label) }}</p>
                            <p class="hrm-num mt-1 text-[18px] font-semibold leading-none {{ $scoreTone($value) }}">{{ $value === null ? '—' : $fmt($value).'%' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($card->rating_category)
                <div class="border-t border-hairline-subtle px-5 py-2 text-[12px] text-ink-muted">
                    {{ __($t.'.fields.rating') }}: <span class="font-semibold text-ink">{{ __($t.'.ratings.'.$card->rating_category) }}</span>
                </div>
            @endif

            @if ($card->bonus)
                @php
                    $bonus = $card->bonus;
                    $b = $t.'.bonus';
                    $factors = $bonus->mode === 'order'
                        ? [
                            'base_salary' => $fmt($bonus->base_salary).' '.$bonus->currency,
                            'reward_months' => $fmt($bonus->period_months),
                            'payout_pct' => $fmt($bonus->payout_pct).'%',
                            'prorata' => $fmt($bonus->prorata * 100).'%',
                        ]
                        : [
                            'base_salary' => $fmt($bonus->base_salary).' '.$bonus->currency,
                            'period_months' => $fmt($bonus->period_months),
                            'target_pct' => $fmt($bonus->target_pct).'%',
                            'payout_pct' => $fmt($bonus->payout_pct).'%',
                            'company_mult' => '×'.$fmt($bonus->company_mult, 4),
                            'unit_mult' => '×'.$fmt($bonus->unit_mult, 4),
                            'prorata' => $fmt($bonus->prorata * 100).'%',
                        ];
                    if ((float) $bonus->scale_factor < 1) {
                        $factors['scale_factor'] = '×'.$fmt($bonus->scale_factor, 4);
                    }
                @endphp
                <div class="flex flex-col gap-3 border-t border-hairline-subtle px-5 py-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] text-ink-muted">
                        <span class="hrm-eyebrow">{{ __($b.'.title') }}</span>
                        @foreach ($factors as $factor => $value)
                            <span>{{ __($b.'.factors.'.$factor) }} <span class="hrm-num font-semibold text-ink-soft">{{ $value }}</span></span>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11.5px] text-ink-muted">{{ __($b.'.statuses.'.$bonus->status) }}</span>
                        <span class="hrm-num text-[17px] font-semibold text-ink">{{ $fmt($bonus->amount) }} {{ $bonus->currency }}</span>
                    </div>
                </div>
            @endif

            {{-- stage stepper --}}
            @php
                $stages = \App\Models\PerformanceScorecard::STATUSES;
                $stageOwner = \App\Models\PerformanceScorecard::STAGE_OWNER[$card->status] ?? null;
                $myTurn = $stageOwner !== null && ($role === $stageOwner || ($role === 'hr' && $stageOwner !== 'employee'));
            @endphp
            <div class="hrm-scroll-hidden overflow-x-auto border-t border-hairline-subtle px-5 pb-4 pt-5">
                <ol class="grid min-w-[760px] grid-cols-8">
                    @foreach ($stages as $index => $status)
                        @php $state = $index < $stageIndex ? 'done' : ($index === $stageIndex ? 'current' : 'todo'); @endphp
                        <li class="relative flex flex-col items-center text-center">
                            @if (! $loop->first)
                                <span class="absolute right-1/2 top-[13px] h-[2px] w-full {{ $index <= $stageIndex ? 'bg-emerald-500' : 'bg-hairline' }}"></span>
                            @endif
                            <span @class([
                                'relative z-[1] flex h-7 w-7 items-center justify-center rounded-full text-[12px] font-semibold transition',
                                'bg-emerald-500 text-white' => $state === 'done',
                                'bg-ink text-white ring-4 ring-zinc-900/10' => $state === 'current',
                                'bg-white text-ink-faint ring-1 ring-hairline' => $state === 'todo',
                            ])>
                                @if ($state === 'done')
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                @else
                                    <span class="hrm-num">{{ $index + 1 }}</span>
                                @endif
                            </span>
                            <span @class([
                                'mt-2 px-1 text-[11.5px] leading-tight',
                                'font-semibold text-ink' => $state === 'current',
                                'text-ink-muted' => $state === 'done',
                                'text-ink-faint' => $state === 'todo',
                            ])>{{ __($t.'.card_statuses.'.$status) }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- next step + actions --}}
            <div x-data="{ asking: null }" class="border-t border-hairline-subtle bg-[#fafafa] px-5 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $myTurn ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-hairline' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-ink">
                                {{ __($t.'.next_step_title') }}
                                @if ($stageOwner && $card->status !== 'closed')
                                    <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $myTurn ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-hairline' }}">
                                        {{ $myTurn ? __($t.'.your_turn') : __($t.'.waiting_for', ['who' => __($t.'.roles.'.$stageOwner)]) }}
                                    </span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-[12.5px] leading-5 text-ink-muted">{{ __($t.'.next_step.'.$card->status) }}</p>
                        </div>
                    </div>

                    @if ($this->actions !== [])
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @foreach ($this->actions as $action)
                                @php $primary = $loop->first && ! in_array($action, $reasonActions, true); @endphp
                                @if (in_array($action, $reasonActions, true))
                                    <button type="button" wire:key="card-action-{{ $action }}" x-on:click="asking = asking === @js($action) ? null : @js($action)"
                                        class="h-9 rounded-xl border border-hairline bg-white px-4 text-[13px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink"
                                        :class="asking === @js($action) ? '!border-ink !text-ink' : ''">
                                        {{ __($t.'.transitions.'.$action) }}
                                    </button>
                                @else
                                    <button type="button" wire:key="card-action-{{ $action }}"
                                        x-on:click="$dispatch('confirm-action', { tone: 'emerald', message: @js(__($t.'.confirm_transition.'.$action)), run: () => $wire.moveCard(@js($action)) })"
                                        class="{{ $primary ? 'bg-ink text-white hover:bg-ink-hover' : 'border border-hairline bg-white text-ink-soft hover:border-zinc-300 hover:text-ink' }} h-9 rounded-xl px-4 text-[13px] font-semibold transition">
                                        {{ __($t.'.transitions.'.$action) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="asking" x-cloak class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                    <textarea wire:model="transitionReason" rows="2" placeholder="{{ __($t.'.reason_placeholder') }}" class="w-full rounded-xl border border-hairline bg-white px-3 py-2 text-[13px] focus:border-zinc-400 focus:outline-none"></textarea>
                    <button type="button" x-on:click="$wire.moveCard(asking)" class="h-10 shrink-0 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.confirm') }}</button>
                </div>
                @error('reason') <x-validation>{{ $message }}</x-validation> @enderror
                @error('scorecard') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
        </div>

        {{-- ───────────── KPI items ───────────── --}}
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.card_kpis') }}</p>
                <span class="hrm-num text-[11.5px] text-ink-faint">{{ (float) $card->kpi_weight_share }}%</span>
            </div>
            <div class="hrm-scroll overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-[13px]">
                    <thead class="hrm-eyebrow whitespace-nowrap border-b border-hairline-subtle">
                        <tr>
                            <th class="px-5 py-2.5">{{ __($t.'.fields.kpi') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.weight') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.target') }}</th>
                            <th class="px-3 py-2.5 text-right" title="{{ __($t.'.band_title') }}">{{ __($t.'.fields.band') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.actual') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.achievement') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.score') }}</th>
                            <th class="px-5 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline-subtle">
                        @foreach ($card->items as $item)
                            <tr wire:key="card-item-{{ $item->id }}" class="align-top text-ink-soft">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-ink">
                                        {{ $item->kpi?->name }}
                                        @if ($item->kpi?->source_metric)
                                            <span class="ml-1 rounded-md bg-sky-50 px-1.5 py-px text-[10.5px] font-medium text-sky-700">{{ __($t.'.metrics.auto_badge') }}</span>
                                        @endif
                                        @if ($item->kpi?->integration_error)
                                            <span class="ml-1 rounded-md bg-rose-50 px-1.5 py-px text-[10.5px] font-medium text-rose-700" title="{{ $item->kpi->integration_error }}">{{ __($t.'.connector.stale') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-[11.5px] text-ink-faint"><span class="hrm-num">{{ $item->kpi?->code }}</span> · {{ __($t.'.directions_short.'.$item->kpi?->direction) }} · {{ __($t.'.units.'.$item->kpi?->unit) }}</p>
                                    @if ($card->status === 'draft' && in_array($role, ['hr', 'manager'], true) && $this->goalOptions !== [])
                                        <select wire:change="linkGoal({{ $item->id }}, $event.target.value)" class="mt-1.5 h-8 max-w-[260px] rounded-lg border border-hairline bg-white px-2 text-[12px] text-ink-muted focus:outline-none">
                                            <option value="">{{ __($t.'.no_goal') }}</option>
                                            @foreach ($this->goalOptions as $goalId => $goalTitle)
                                                <option value="{{ $goalId }}" @selected((int) $item->performance_goal_id === (int) $goalId)>{{ $goalTitle }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($item->goal)
                                        <p class="mt-1 inline-flex items-center gap-1 rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                                            {{ $item->goal->title }}
                                        </p>
                                    @endif
                                </td>
                                <td class="hrm-num px-3 py-3 text-right">{{ $fmt($item->weight) }}%</td>
                                <td class="px-3 py-3 text-right">
                                    @if ($card->status === 'draft' && ($role === 'hr' || ($role === 'manager' && $item->target_editable)))
                                        <div class="ml-auto inline-flex h-8 items-center overflow-hidden rounded-lg border border-hairline bg-white transition focus-within:border-zinc-400 focus-within:ring-2 focus-within:ring-zinc-900/5">
                                            <input type="number" step="any" wire:model="targets.{{ $item->id }}" wire:keydown.enter="saveTarget({{ $item->id }})" aria-label="{{ __($t.'.fields.target') }}"
                                                class="hrm-num h-full w-24 border-0 bg-transparent px-2 text-right text-[12.5px] text-ink focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                            <button type="button" wire:click="saveTarget({{ $item->id }})" title="{{ __($t.'.actions.save') }}" aria-label="{{ __($t.'.actions.save') }}"
                                                class="flex h-full w-8 items-center justify-center border-l border-hairline text-ink-faint transition hover:bg-emerald-50 hover:text-emerald-700">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                            </button>
                                        </div>
                                        @error('targets.'.$item->id) <x-validation>{{ $message }}</x-validation> @enderror
                                    @elseif ($item->kpi?->direction === 'range')
                                        <span class="hrm-num">{{ $fmt($item->range_min) }} – {{ $fmt($item->range_max) }}</span>
                                    @else
                                        <span class="hrm-num">{{ $fmt($item->target) }}</span>
                                        @if ($item->original_target !== null)
                                            <span class="block text-[11px] text-ink-faint line-through decoration-ink-faint/60" title="{{ __($t.'.leave.original', ['value' => $fmt($item->original_target)]) }}">{{ $fmt($item->original_target) }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="hrm-num whitespace-nowrap px-3 py-3 text-right text-[12px] text-ink-faint" title="{{ __($t.'.band_title') }}">{{ $fmt($item->threshold) }} · {{ $fmt($item->stretch) }} · {{ $fmt($item->cap) }}</td>
                                <td class="hrm-num px-3 py-3 text-right">{{ $fmt($item->actual) }}</td>
                                <td class="hrm-num px-3 py-3 text-right">{{ $item->achievement === null ? '—' : $fmt($item->achievement).'%' }}</td>
                                <td class="hrm-num px-3 py-3 text-right font-semibold {{ $scoreTone($item->score, $item->threshold) }}">{{ $item->score === null ? '—' : $fmt($item->score).'%' }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($card->status === 'active' && $role !== null && $actualItemId !== $item->id)
                                        <button type="button" wire:click="startActual({{ $item->id }})" class="whitespace-nowrap rounded-lg border border-hairline px-2.5 py-1 text-[12px] font-medium text-ink-soft hover:bg-[#f4f4f5]">{{ __($t.'.actions.add_actual') }}</button>
                                    @endif
                                </td>
                            </tr>

                            @if ($actualItemId === $item->id)
                                <tr wire:key="card-item-form-{{ $item->id }}">
                                    <td colspan="8" class="bg-[#fafafa] px-5 py-3">
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                            <label class="block">
                                                <span class="text-[11px] text-ink-muted">{{ __($t.'.fields.actual') }}</span>
                                                <input type="number" step="any" wire:model="actualValue" class="{{ $field }}">
                                                @error('actualValue') <x-validation>{{ $message }}</x-validation> @enderror
                                                @error('actual') <x-validation>{{ $message }}</x-validation> @enderror
                                            </label>
                                            <label class="block sm:col-span-2">
                                                <span class="text-[11px] text-ink-muted">{{ __($t.'.fields.note') }}</span>
                                                <input type="text" wire:model="actualNote" class="{{ $field }}">
                                            </label>
                                            <label class="block">
                                                <span class="text-[11px] text-ink-muted">
                                                    {{ __($t.'.fields.evidence') }}
                                                    @if ($item->kpi?->evidence_required) <span class="text-rose-600">*</span> @endif
                                                </span>
                                                <input type="file" wire:model="evidence" class="block w-full text-[12px] text-ink-muted file:mr-2 file:rounded-lg file:border-0 file:bg-[#f4f4f5] file:px-2 file:py-1.5">
                                                @error('evidence') <x-validation>{{ $message }}</x-validation> @enderror
                                            </label>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between gap-2">
                                            <p class="text-[11px] text-ink-faint">{{ $role === 'employee' ? __($t.'.actual_pending_hint') : '' }}</p>
                                            <div class="flex gap-2">
                                                <button type="button" wire:click="cancelActual" class="h-9 rounded-xl border border-hairline px-4 text-[13px] text-ink-soft hover:bg-white">{{ __($t.'.actions.cancel') }}</button>
                                                <button type="button" wire:click="saveActual" wire:loading.attr="disabled" wire:target="saveActual,evidence" class="h-9 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.save') }}</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if ($item->actuals->isNotEmpty())
                                <tr wire:key="card-item-history-{{ $item->id }}">
                                    <td colspan="8" class="px-5 pb-3 pt-0">
                                        <div class="flex flex-col gap-1 border-l-2 border-hairline pl-3">
                                            @foreach ($item->actuals->take(5) as $actual)
                                                <div wire:key="actual-{{ $actual->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] text-ink-muted">
                                                    <span class="hrm-num font-medium text-ink">{{ $fmt($actual->value, 4) }}</span>
                                                    <span>{{ $actual->enteredBy?->name ?? '—' }} · <span class="hrm-num">{{ $actual->created_at?->format('d.m.Y H:i') }}</span></span>
                                                    @if ($actual->evidence_name)
                                                        <span class="text-ink-faint">{{ __($t.'.fields.evidence') }}: {{ $actual->evidence_name }}</span>
                                                    @endif
                                                    @if ($actual->note)
                                                        <span class="text-ink-faint">“{{ $actual->note }}”</span>
                                                    @endif
                                                    @if ($actual->approved_at)
                                                        <span class="rounded-full bg-emerald-50 px-1.5 text-emerald-700">{{ __($t.'.actual_approved') }}</span>
                                                    @else
                                                        <span class="rounded-full bg-amber-50 px-1.5 text-amber-700">{{ __($t.'.actual_pending') }}</span>
                                                        @if (in_array($role, ['hr', 'manager'], true) && $card->status === 'active')
                                                            <button type="button" wire:click="approveActual({{ $actual->id }})" class="font-semibold text-emerald-700 hover:underline">{{ __($t.'.actions.approve') }}</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ───────────── competencies ───────────── --}}
        @if ($competencies->isNotEmpty())
            @php
                $canSelf = $card->status === 'self_review' && $role === 'employee';
                $canManager = $card->status === 'manager_review' && in_array($role, ['manager', 'hr'], true);
                $showSelf = $role !== 'employee' || in_array($card->status, ['self_review', 'manager_review', 'calibration', 'approved', 'closed'], true);
            @endphp
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.competencies') }}</p>
                    <span class="hrm-num text-[11.5px] text-ink-faint">{{ (float) $card->competency_weight_share }}%</span>
                </div>
                <p class="px-5 pt-3 text-[12px] text-ink-faint">{{ __($t.'.competency_hint') }}</p>
                @error('competency') <div class="px-5"><x-validation>{{ $message }}</x-validation></div> @enderror
                <div class="divide-y divide-hairline-subtle">
                    @foreach ($competencies as $competency)
                        <div wire:key="competency-{{ $competency['id'] }}" class="grid grid-cols-1 gap-3 px-5 py-3 md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-semibold text-ink">{{ $competency['name'] }}</p>
                                <p class="text-[11.5px] text-ink-faint">{{ $competency['section'] }}</p>
                            </div>
                            @foreach (['self' => [$canSelf, $showSelf], 'manager' => [$canManager, true]] as $evaluator => [$editable, $visible])
                                <div>
                                    <p class="hrm-eyebrow mb-1">{{ __($t.'.evaluators.'.$evaluator) }}</p>
                                    @if ($visible)
                                        <div class="flex gap-1">
                                            @foreach (range(1, 5) as $rating)
                                                @php $on = $competency[$evaluator] === $rating; @endphp
                                                @if ($editable)
                                                    <button type="button" wire:click="rate({{ $competency['id'] }}, '{{ $evaluator }}', {{ $rating }})"
                                                        class="hrm-num h-8 w-8 rounded-lg border text-[12.5px] font-semibold transition {{ $on ? 'border-ink bg-ink text-white' : 'border-hairline bg-white text-ink-muted hover:border-zinc-400 hover:text-ink' }}">{{ $rating }}</button>
                                                @else
                                                    <span class="hrm-num flex h-8 w-8 items-center justify-center rounded-lg text-[12.5px] font-semibold {{ $on ? 'bg-ink text-white' : 'bg-[#f4f4f5] text-ink-faint' }}">{{ $rating }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-[12px] text-ink-faint">{{ __($t.'.hidden_until_self_review') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- ───────────── check-ins ───────────── --}}
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.checkins') }}</p>
                    <span class="hrm-num text-[11.5px] {{ $card->checkins->count() >= $expectedCheckins ? 'text-emerald-700' : 'text-ink-faint' }}">{{ $card->checkins->count() }} / {{ $expectedCheckins }}</span>
                </div>
                @if ($card->status === 'active' && $role !== null)
                    <div class="grid grid-cols-1 gap-2 border-b border-hairline-subtle px-5 py-3">
                        <div class="grid grid-cols-[140px_minmax(0,1fr)] gap-2">
                            <input type="date" wire:model="checkinDate" class="{{ $field }}">
                            <input type="text" wire:model="checkinProgress" placeholder="{{ __($t.'.checkin_progress_placeholder') }}" class="{{ $field }}">
                        </div>
                        <input type="text" wire:model="checkinRisks" placeholder="{{ __($t.'.checkin_risks_placeholder') }}" class="{{ $field }}">
                        @error('checkinProgress') <x-validation>{{ $message }}</x-validation> @enderror
                        @error('checkin') <x-validation>{{ $message }}</x-validation> @enderror
                        <div class="flex justify-end">
                            <button type="button" wire:click="saveCheckin" class="h-9 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.add_checkin') }}</button>
                        </div>
                    </div>
                @endif
                <div class="hrm-scroll max-h-72 divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($card->checkins as $checkin)
                        <div wire:key="checkin-{{ $checkin->id }}" class="px-5 py-3">
                            <p class="text-[11.5px] text-ink-faint"><span class="hrm-num">{{ $checkin->checkin_date?->format('d.m.Y') }}</span> · {{ $checkin->author?->name ?? '—' }}</p>
                            <p class="mt-1 text-[13px] text-ink-soft">{{ $checkin->progress }}</p>
                            @if ($checkin->risks)
                                <p class="mt-1 text-[12px] text-amber-700">{{ __($t.'.risks') }}: {{ $checkin->risks }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-6 text-center text-[12.5px] text-ink-faint">{{ __($t.'.no_checkins') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- ───────────── history (+ calibration) ───────────── --}}
            <div class="flex flex-col gap-4">
                @if ($card->status === 'calibration' && $role === 'hr')
                    <div class="{{ $section }}">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.calibration') }}</p>
                            <span class="text-[11.5px] text-ink-faint">± {{ \App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService::MAX_CALIBRATION_DELTA }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 px-5 py-3">
                            <div class="grid grid-cols-[120px_minmax(0,1fr)] gap-2">
                                <input type="number" step="0.5" wire:model="calibrationDelta" placeholder="+/-" class="{{ $field }} hrm-num">
                                <input type="text" wire:model="calibrationReason" placeholder="{{ __($t.'.reason_placeholder') }}" class="{{ $field }}">
                            </div>
                            @error('calibrationDelta') <x-validation>{{ $message }}</x-validation> @enderror
                            @error('calibrationReason') <x-validation>{{ $message }}</x-validation> @enderror
                            @error('calibration') <x-validation>{{ $message }}</x-validation> @enderror
                            <div class="flex justify-end">
                                <button type="button" wire:click="saveCalibration" class="h-9 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.calibrate') }}</button>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="{{ $section }}">
                    <div class="{{ $sectionHead }}">
                        <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.history') }}</p>
                    </div>
                    <ol class="hrm-scroll max-h-72 overflow-y-auto px-5 py-3">
                        @foreach ($card->calibrations as $adjustment)
                            <li wire:key="calibration-{{ $adjustment->id }}" class="relative border-l border-hairline pb-3 pl-4">
                                <span class="absolute -left-[4.5px] top-1.5 h-2 w-2 rounded-full bg-orange-500"></span>
                                <p class="text-[12.5px] font-medium text-ink">{{ __($t.'.calibration_entry', ['delta' => ((float) $adjustment->delta > 0 ? '+' : '').$fmt($adjustment->delta)]) }}</p>
                                <p class="text-[11.5px] text-ink-faint">{{ $adjustment->adjustedBy?->name ?? '—' }} · <span class="hrm-num">{{ $adjustment->created_at?->format('d.m.Y H:i') }}</span></p>
                                <p class="mt-0.5 text-[12px] text-ink-muted">{{ $adjustment->reason }}</p>
                            </li>
                        @endforeach
                        @foreach ($card->events as $event)
                            <li wire:key="event-{{ $event->id }}" class="relative border-l border-hairline pb-3 pl-4 last:pb-0">
                                <span class="absolute -left-[4.5px] top-1.5 h-2 w-2 rounded-full {{ $cardBadge[$event->to_status][1] ?? 'bg-zinc-400' }}"></span>
                                <p class="text-[12.5px] font-medium text-ink">{{ __($t.'.events.'.$event->action) }}</p>
                                <p class="text-[11.5px] text-ink-faint">{{ $event->user?->name ?? __($t.'.system') }} · <span class="hrm-num">{{ $event->created_at?->format('d.m.Y H:i') }}</span></p>
                                @if ($event->reason)
                                    <p class="mt-0.5 text-[12px] text-ink-muted">{{ $event->action === 'closed_early' ? __($t.'.closure_reasons.'.$event->reason) : $event->reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    @else
        {{-- ───────────── calibration spread + cascade gaps (HR) ───────────── --}}
        @php $spread = $this->distribution; $spreadTotal = collect($spread)->sum('count'); @endphp
        @if ($spreadTotal > 0 || $this->unlinkedItems > 0)
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                @if ($spreadTotal > 0)
                    <div class="{{ $section }} px-5 py-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.distribution') }}</p>
                            <span class="hrm-num text-[11.5px] text-ink-faint">{{ $spreadTotal }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-5 gap-3">
                            @foreach ($spread as $category => $row)
                                <div>
                                    <div class="relative h-20 overflow-hidden rounded-lg bg-[#f4f4f5]">
                                        <div class="absolute inset-x-0 bottom-0 bg-ink" style="height: {{ min(100, $row['share']) }}%"></div>
                                        <div class="absolute inset-x-0 border-t-2 border-dashed border-orange-500" style="bottom: {{ $row['target'] }}%"></div>
                                    </div>
                                    <p class="hrm-num mt-1.5 text-[12px] font-semibold text-ink">{{ $row['share'] }}% <span class="font-normal text-ink-faint">/ {{ $row['target'] }}%</span></p>
                                    <p class="text-[11px] leading-tight text-ink-faint">{{ __($t.'.ratings.'.$category) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] text-ink-faint">{{ __($t.'.distribution_hint') }}</p>
                    </div>
                @endif
                @if ($this->unlinkedItems > 0)
                    <div class="{{ $section }} px-5 py-4">
                        <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.cascade_gaps') }}</p>
                        <p class="hrm-num mt-2 text-[28px] font-semibold leading-none text-amber-600">{{ $this->unlinkedItems }}</p>
                        <p class="mt-1.5 text-[12px] text-ink-muted">{{ __($t.'.cascade_gaps_hint') }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- ───────────── card list ───────────── --}}
        @php $cardColumns = 'md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_150px_88px_88px] md:items-center md:gap-4'; @endphp
        <div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="{{ $cardColumns }} hidden border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5">
                <span class="hrm-eyebrow">{{ __($t.'.fields.personnel') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.position') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.manager') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.status') }}</span>
                <span class="hrm-eyebrow text-right">{{ __($t.'.fields.kpi_score') }}</span>
                <span class="hrm-eyebrow text-right">{{ __($t.'.fields.final_score') }}</span>
            </div>

            @forelse ($this->cards as $row)
                @php [$badgeTone, $badgeDot] = $cardBadge[$row->status] ?? $cardBadge['draft']; @endphp
                <button type="button" wire:key="card-row-{{ $row->id }}" wire:click="openCard({{ $row->id }})"
                    class="{{ $cardColumns }} flex w-full flex-col gap-2 border-b border-hairline-subtle px-5 py-3 text-left transition-colors last:border-b-0 hover:bg-[#fafafa] focus-visible:bg-[#fafafa] focus-visible:outline-none">
                    <span class="flex min-w-0 items-center gap-3">
                        <x-avatar size="sm" :name="$row->personnel?->fullname ?? '—'" />
                        <span class="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-ink">{{ $row->personnel?->fullname }}</span>
                    </span>
                    <span class="truncate text-[12.5px] text-ink-soft">{{ $row->position?->name ?? '—' }}</span>
                    <span class="truncate text-[12.5px] text-ink-muted">{{ $row->manager ? trim($row->manager->surname.' '.$row->manager->name) : '—' }}</span>
                    <span>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $badgeTone }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                            {{ __($t.'.card_statuses.'.$row->status) }}
                        </span>
                    </span>
                    <span class="hrm-num text-[13px] md:text-right {{ $scoreTone($row->kpi_score) }}">{{ $row->kpi_score === null ? '—' : $fmt($row->kpi_score).'%' }}</span>
                    <span class="hrm-num text-[13px] font-semibold md:text-right {{ $scoreTone($row->final_score) }}">{{ $row->final_score === null ? '—' : $fmt($row->final_score).'%' }}</span>
                </button>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-[13.5px] font-medium text-ink">{{ $cycleId ? __($t.'.empty_cards') : __($t.'.no_cycle') }}</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
