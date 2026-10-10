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
                <x-ui.filter-native-select wire:model.live="cycleId" aria-label="{{ __($t.'.a11y.cycle') }}" title="{{ __($t.'.a11y.cycle') }}">
                    @foreach ($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
            @unless ($card)
                <div class="min-w-[12rem]">
                    <x-ui.filter-native-select wire:model.live="statusFilter" aria-label="{{ __($t.'.a11y.status_filter') }}" title="{{ __($t.'.a11y.status_filter') }}">
                        <option value="">{{ __($t.'.all_statuses') }}</option>
                        @foreach (\App\Models\PerformanceScorecard::STATUSES as $status)
                            <option value="{{ $status }}">{{ __($t.'.card_statuses.'.$status) }}</option>
                        @endforeach
                    </x-ui.filter-native-select>
                </div>
            @endunless
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = ! open" class="flex h-10 w-10 items-center justify-center rounded-[10px] border border-hairline bg-[#f4f4f5] text-ink-soft transition hover:border-zinc-300 hover:text-ink" title="{{ __($t.'.notification_settings.title') }}" aria-label="{{ __($t.'.notification_settings.title') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </button>
                <div x-cloak x-show="open" x-transition.opacity class="absolute left-0 z-30 mt-2 w-72 rounded-2xl border border-hairline bg-white p-4 shadow-card">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.notification_settings.title') }}</p>
                    <p class="mt-0.5 text-[11.5px] leading-5 text-ink-muted">{{ __($t.'.notification_settings.hint') }}</p>
                    <label class="mt-3 flex items-start gap-2.5 text-[12.5px] text-ink-soft">
                        <input type="checkbox" wire:model.live="notifyByEmail" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                        <span>{{ __($t.'.notification_settings.email') }}</span>
                    </label>
                    <label class="mt-2 flex items-start gap-2.5 text-[12.5px] text-ink-soft {{ $notifyByEmail ? '' : 'opacity-50' }}">
                        <input type="checkbox" wire:model.live="notifyDigest" @disabled(! $notifyByEmail) class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                        <span>{{ __($t.'.notification_settings.digest') }}</span>
                    </label>
                    <p class="mt-3 rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11.5px] leading-5 text-amber-800">{{ __($t.'.notification_settings.mandatory') }}</p>
                </div>
            </div>
        </div>

        @can('manage-performance-evaluation')
            @if ($cycleId && ! $card)
                <div class="flex flex-wrap items-center gap-2">
                    <x-pill-button variant="secondary" wire:click="syncMetrics" wire:loading.attr="disabled" wire:target="syncMetrics" title="{{ __($t.'.metrics.sync_hint') }}">
                        <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="syncMetrics" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/></svg>
                        {{ __($t.'.metrics.sync') }}
                    </x-pill-button>
                    <x-pill-button variant="secondary" wire:click="openExtra" title="{{ __($t.'.extra.hint') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M12 11v5M9.5 13.5h5"/></svg>
                        {{ __($t.'.extra.open') }}
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

    @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.scorecards.import')

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
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <button type="button" wire:click="closeCard" class="inline-flex h-10 items-center text-[14px] font-medium text-ink-faint hover:text-ink">← {{ __($t.'.actions.back_to_list') }}</button>
                            <a href="{{ route('performance-evaluation.scorecard-print', $card->id) }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-1 text-[14px] font-medium text-ink-faint hover:text-ink">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                {{ __($t.'.print.open') }}
                            </a>
                        </div>
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
                            @if ($card->is_additional || (float) $card->fte < 1)
                                <span class="rounded-md bg-sky-50 px-2 py-0.5 text-sky-700">{{ __($t.'.extra.chip', ['fte' => $fmt($card->fte)]) }}</span>
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
                    // Maaş bazası və bonus məbləği yalnız view-compensation-amounts icazəsi ilə (və ya öz kartında) görünür.
                    $canSeeSalary = (auth()->user()?->can('view-compensation-amounts') ?? false) || $role === 'employee';
                    $salary = fn ($value) => $canSeeSalary ? $fmt($value).' '.$bonus->currency : '•••';
                    $factors = $bonus->mode === 'order'
                        ? [
                            'base_salary' => $salary($bonus->base_salary),
                            'reward_months' => $fmt($bonus->period_months),
                            'payout_pct' => $fmt($bonus->payout_pct).'%',
                            'prorata' => $fmt($bonus->prorata * 100).'%',
                        ]
                        : [
                            'base_salary' => $salary($bonus->base_salary),
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
                        <span class="hrm-num text-[17px] font-semibold text-ink">{{ $salary($bonus->amount) }}</span>
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
                                        class="h-10 rounded-xl border border-hairline bg-white px-4 text-[14px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink"
                                        :class="asking === @js($action) ? '!border-ink !text-ink' : ''">
                                        {{ __($t.'.transitions.'.$action) }}
                                    </button>
                                @else
                                    <button type="button" wire:key="card-action-{{ $action }}"
                                        x-on:click="$dispatch('confirm-action', { tone: 'emerald', message: @js(__($t.'.confirm_transition.'.$action)), run: () => $wire.moveCard(@js($action)) })"
                                        class="{{ $primary ? 'bg-ink text-white hover:bg-ink-hover' : 'border border-hairline bg-white text-ink-soft hover:border-zinc-300 hover:text-ink' }} h-10 rounded-xl px-4 text-[14px] font-semibold transition">
                                        {{ __($t.'.transitions.'.$action) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="asking" x-cloak class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                    <textarea wire:model="transitionReason" rows="2" aria-label="{{ __($t.'.a11y.transition_reason') }}" placeholder="{{ __($t.'.reason_placeholder') }}" class="w-full rounded-xl border border-hairline bg-white px-3 py-2 text-base focus:border-zinc-400 focus:outline-none sm:text-sm"></textarea>
                    @foreach (array_intersect($this->actions, $reasonActions) as $action)
                        <button type="button" wire:key="card-action-confirm-{{ $action }}" x-show="asking === @js($action)"
                            x-on:click="$dispatch('confirm-action', { title: @js(__($t.'.transitions.'.$action)), message: @js(__($t.'.confirm_transition.'.$action)), confirmText: @js(__($t.'.transitions.'.$action)), tone: 'rose', run: () => $wire.moveCard(@js($action)) })"
                            class="h-10 shrink-0 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.confirm') }}</button>
                    @endforeach
                </div>
                @error('reason') <x-validation>{{ $message }}</x-validation> @enderror
                @error('scorecard') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
        </div>

        {{-- ───────────── pending target changes (HR decides) ───────────── --}}
        @php $pendingChanges = $card->items->flatMap(fn ($item) => $item->changeRequests->where('status', 'pending')); @endphp
        @if ($role === 'hr' && $pendingChanges->isNotEmpty())
            <div class="{{ $section }} border-violet-200">
                <div class="{{ $sectionHead }} bg-violet-50/60">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.change_requests.title') }} <span class="hrm-num ml-1 text-ink-faint">{{ $pendingChanges->count() }}</span></p>
                </div>
                @foreach ($pendingChanges as $change)
                    <div wire:key="change-{{ $change->id }}" class="flex flex-col gap-3 border-b border-hairline-subtle px-5 py-3 last:border-b-0 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 text-[12.5px]">
                            <p class="font-semibold text-ink">{{ $card->items->firstWhere('id', $change->performance_scorecard_item_id)?->kpi?->name }}
                                <span class="hrm-num ml-1 font-normal text-ink-muted">{{ $fmt($change->current_target) }} → <span class="font-semibold text-ink">{{ $fmt($change->proposed_target) }}</span></span>
                            </p>
                            <p class="mt-0.5 text-ink-muted">{{ $change->reason }} · <span class="text-ink-faint">{{ $change->requester?->name }}, {{ $change->created_at?->format('d.m.Y') }}</span></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <input type="text" wire:model="decisionNote" aria-label="{{ __($t.'.a11y.decision_note') }}" placeholder="{{ __($t.'.change_requests.note_placeholder') }}" class="h-10 w-full rounded-lg border border-hairline bg-white px-2.5 text-base focus:border-zinc-400 focus:outline-none sm:w-48 sm:text-sm">
                            <button type="button" x-on:click="$dispatch('confirm-action', { title: @js(__($t.'.change_requests.reject')), message: @js(__($t.'.change_requests.confirm_reject')), confirmText: @js(__($t.'.change_requests.reject')), tone: 'rose', run: () => $wire.rejectChange({{ $change->id }}) })" class="h-10 rounded-lg border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600">{{ __($t.'.change_requests.reject') }}</button>
                            <button type="button" wire:click="approveChange({{ $change->id }})" class="h-10 rounded-lg bg-ink px-3 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.change_requests.approve') }}</button>
                        </div>
                    </div>
                @endforeach
                @error('reason') <div class="px-5 pb-3"><x-validation>{{ $message }}</x-validation></div> @enderror
            </div>
        @endif

        @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.scorecards.card-items')

        @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.scorecards.card-reviews')
    @else
        @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.scorecards.card-list')
    @endif

    @include('performance-evaluation::livewire.performance-evaluation.kpi.partials.scorecards.extra-card-modal')
</div>

