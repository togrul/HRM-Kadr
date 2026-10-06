@php
    $t = 'performance_evaluation::kpi';
    $b = $t.'.bonus';
    $mode = $this->mode;
    $preview = $this->preview;
    $dirty = $this->isDirty();
    $fmt = fn ($value, int $decimals = 2) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, $decimals, '.', ' '), '0'), '.');
    $money = fn ($value) => $value === null ? '—' : number_format((float) $value, 2, '.', ' ');
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $input = 'hrm-num h-10 w-full rounded-[10px] border border-hairline bg-white px-2.5 text-base text-ink focus:border-zinc-400 focus:outline-none sm:text-sm';
    $canSeeSalary = auth()->user()?->can('view-compensation-amounts');
    $rows = $preview['rows'] ?? collect();
    $currency = $preview['currency'] ?? 'AZN';
    $statusTone = [
        'calculated' => 'bg-sky-50 text-sky-700',
        'exported' => 'bg-emerald-50 text-emerald-700',
        'ordered' => 'bg-emerald-50 text-emerald-700',
    ];
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
            <span class="inline-flex h-9 items-center gap-2 rounded-[10px] border border-hairline bg-white px-3 text-[12.5px] font-medium text-ink-soft" title="{{ __($b.'.modes.'.$mode.'.hint') }}">
                <span class="h-2 w-2 rounded-full {{ $mode === 'order' ? 'bg-amber-500' : 'bg-sky-500' }}"></span>
                {{ __($b.'.modes.'.$mode.'.label') }}
            </span>
        </div>

        @if ($preview)
            <div class="flex flex-wrap items-center gap-2">
                <x-pill-button variant="secondary" wire:click="calculate" wire:loading.attr="disabled" wire:target="calculate">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h8"/></svg>
                    {{ __($b.'.actions.calculate') }}
                </x-pill-button>
                @if ($mode === 'order')
                    <x-pill-button variant="primary" data-message="{{ __($b.'.confirm_orders') }}" x-on:click="$dispatch('confirm-action', { tone: 'emerald', message: $el.dataset.message, run: () => $wire.issueOrders() })">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M9 13h6M9 17h4"/></svg>
                        {{ __($b.'.actions.issue_orders') }}
                    </x-pill-button>
                @else
                    <x-pill-button variant="primary" wire:click="exportPayroll">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                        {{ __($b.'.actions.export') }}
                    </x-pill-button>
                @endif
            </div>
        @endif
    </div>

    @if (! $preview)
        <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-16 text-center text-[13.5px] text-ink-muted">{{ __($b.'.empty_cycle') }}</div>
    @else
        {{-- ───────────── summary ───────────── --}}
        @php
            $fund = $preview['fund'];
            $fundPercent = $fund ? min(100, (int) round($preview['total'] / max($fund, 0.01) * 100)) : null;
        @endphp
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-hairline bg-white px-4 py-3.5 shadow-card">
                <p class="hrm-eyebrow">{{ __($b.'.summary.people') }}</p>
                <p class="hrm-num mt-1.5 text-[22px] font-semibold leading-none text-ink">{{ $rows->count() }}</p>
                <p class="mt-1 text-[11.5px] text-ink-faint">{{ __($b.'.summary.people_hint') }}</p>
            </div>
            <div class="rounded-2xl border border-hairline bg-white px-4 py-3.5 shadow-card">
                <p class="hrm-eyebrow">{{ __($b.'.summary.paying') }}</p>
                <p class="hrm-num mt-1.5 text-[22px] font-semibold leading-none text-ink">{{ $rows->where('amount', '>', 0)->count() }}</p>
                <p class="mt-1 text-[11.5px] text-ink-faint">{{ __($b.'.summary.paying_hint') }}</p>
            </div>
            <div class="col-span-2 rounded-2xl border {{ $preview['over_fund'] ? 'border-rose-200' : 'border-hairline' }} bg-white px-4 py-3.5 shadow-card">
                <div class="flex items-baseline justify-between gap-3">
                    <p class="hrm-eyebrow">{{ __($b.'.summary.total') }}</p>
                    @if ($fund)
                        <p class="text-[11.5px] text-ink-faint">{{ __($b.'.summary.of_fund', ['fund' => $money($fund).' '.$currency]) }}</p>
                    @endif
                </div>
                <p class="hrm-num mt-1.5 text-[22px] font-semibold leading-none {{ $preview['over_fund'] ? 'text-rose-600' : 'text-ink' }}">{{ $money($preview['total']) }} <span class="text-[13px] font-medium text-ink-faint">{{ $currency }}</span></p>
                @if ($fund)
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                        <div class="h-full rounded-full {{ $preview['over_fund'] ? 'bg-rose-500' : 'bg-ink' }}" style="width: {{ $fundPercent }}%"></div>
                    </div>
                @endif
            </div>
        </div>

        @if ($preview['over_fund'] || $preview['missing_salary'] > 0 || $dirty)
            <div class="flex flex-col gap-2">
                @if ($dirty)
                    <p class="flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-[12.5px] text-violet-800">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>{{ __($b.'.simulation_notice') }}
                    </p>
                @endif
                @if ($preview['over_fund'])
                    <p class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[12.5px] text-rose-700">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                        {{ $ruleForm['scale_to_fund'] ? __($b.'.warnings.scaled', ['percent' => $fmt($preview['scale'] * 100)]) : __($b.'.warnings.over_fund') }}
                    </p>
                @endif
                @if ($preview['missing_salary'] > 0)
                    <p class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-[12.5px] text-amber-800">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>{{ __($b.'.warnings.missing_salary', ['count' => $preview['missing_salary']]) }}
                    </p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            {{-- ───────────── rule ───────────── --}}
            <div class="{{ $section }} lg:col-span-5">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($b.'.rule_title') }}</p>
                    @if ($dirty)
                        <span class="rounded-md bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700">{{ __($b.'.unsaved') }}</span>
                    @endif
                </div>
                <div class="flex flex-col gap-5 p-5">
                    <p class="rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted">{{ __($b.'.modes.'.$mode.'.formula') }}</p>

                    {{-- target --}}
                    <div class="grid grid-cols-2 gap-3">
                        @if ($mode === 'order')
                            <div class="col-span-2">
                                <x-label value="{{ __($b.'.fields.reward_months') }}" />
                                <input type="number" step="0.25" min="0" wire:model.live.debounce.400ms="ruleForm.reward_months" class="{{ $input }} mt-1">
                                <p class="mt-1 text-[11.5px] text-ink-faint">{{ __($b.'.fields.reward_months_hint') }}</p>
                                @error('reward_months') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        @else
                            <div class="col-span-2">
                                <x-label value="{{ __($b.'.fields.target_pct') }}" />
                                <input type="number" step="0.5" min="0" wire:model.live.debounce.400ms="ruleForm.target_pct" class="{{ $input }} mt-1">
                                <p class="mt-1 text-[11.5px] text-ink-faint">{{ __($b.'.fields.target_pct_hint') }}</p>
                                @error('target_pct') <x-validation>{{ $message }}</x-validation> @enderror
                            <div class="mt-3 flex items-center justify-between">
                                <p class="text-[12px] font-medium text-ink-soft">{{ __($b.'.fields.position_targets') }}</p>
                                @if ($this->positionOptions !== [])
                                    <button type="button" wire:click="addBand('position_targets')" class="text-[14px] font-medium text-ink-faint hover:text-ink">+ {{ __($b.'.actions.add_row') }}</button>
                                @endif
                            </div>
                            @if (($ruleForm['position_targets'] ?? []) !== [])
                                <div class="mt-1.5 overflow-hidden rounded-xl border border-hairline-subtle">
                                    @foreach ($ruleForm['position_targets'] as $index => $pair)
                                        <div wire:key="position_targets-{{ $index }}" class="grid grid-cols-[minmax(0,1fr)_96px_40px] items-center gap-2 px-3 py-1.5 {{ $loop->first ? '' : 'border-t border-hairline-subtle' }}">
                                            <x-ui.select class="min-w-0 truncate" wire:model.live="ruleForm.position_targets.{{ $index }}.from">
                                                <option value="">—</option>
                                                @foreach ($this->positionOptions as $optionId => $optionName)
                                                    <option value="{{ $optionId }}">{{ $optionName }}</option>
                                                @endforeach
                                            </x-ui.select>
                                            <div class="relative"><input type="number" step="0.5" wire:model.live.debounce.400ms="ruleForm.position_targets.{{ $index }}.value" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">%</span></div>
                                            <button type="button" wire:click="removeBand('position_targets', {{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint hover:bg-rose-50 hover:text-rose-600" aria-label="{{ __($t.'.actions.delete') }}">
                                                <x-icons.delete-icon size="h-4 w-4" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('position_targets') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        @endif
                    </div>

                    {{-- payout matrix --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <p class="text-[12.5px] font-semibold text-ink">{{ __($b.'.fields.payout_bands') }}</p>
                            <button type="button" wire:click="addBand('payout_bands')" class="text-[14px] font-medium text-ink-faint hover:text-ink">+ {{ __($b.'.actions.add_row') }}</button>
                        </div>
                        <div class="mt-2 overflow-hidden rounded-xl border border-hairline-subtle">
                            <div class="grid grid-cols-[1fr_1fr_40px] gap-2 bg-[#fafafa] px-3 py-1.5">
                                <span class="hrm-eyebrow">{{ __($b.'.fields.score_from') }}</span>
                                <span class="hrm-eyebrow">{{ __($b.'.fields.payout') }}</span>
                                <span></span>
                            </div>
                            @foreach ($ruleForm['payout_bands'] ?? [] as $index => $band)
                                <div wire:key="band-{{ $index }}" class="grid grid-cols-[1fr_1fr_40px] items-center gap-2 border-t border-hairline-subtle px-3 py-1.5">
                                    <div class="relative"><input type="number" step="1" wire:model.live.debounce.400ms="ruleForm.payout_bands.{{ $index }}.from" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">≥%</span></div>
                                    <div class="relative"><input type="number" step="1" wire:model.live.debounce.400ms="ruleForm.payout_bands.{{ $index }}.value" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">%</span></div>
                                    <button type="button" wire:click="removeBand('payout_bands', {{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint hover:bg-rose-50 hover:text-rose-600" aria-label="{{ __($t.'.actions.delete') }}">
                                        <x-icons.delete-icon size="h-4 w-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @error('payout_bands') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    {{-- company result --}}
                    @if ($mode === 'company')
                        <div>
                            <p class="text-[12.5px] font-semibold text-ink">{{ __($b.'.fields.company_block') }}</p>
                            <p class="mt-0.5 text-[11.5px] leading-5 text-ink-faint">{{ __($b.'.fields.company_hint') }}</p>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                @foreach (['company_result', 'company_gate', 'gate_floor_pct'] as $field)
                                    <div>
                                        <x-label value="{{ __($b.'.fields.'.$field) }}" />
                                        <input type="number" step="0.5" wire:model.live.debounce.400ms="ruleForm.{{ $field }}" class="{{ $input }} mt-1">
                                        @error($field) <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <p class="text-[12px] font-medium text-ink-soft">{{ __($b.'.fields.company_multipliers') }}</p>
                                <button type="button" wire:click="addBand('company_multipliers')" class="text-[14px] font-medium text-ink-faint hover:text-ink">+ {{ __($b.'.actions.add_row') }}</button>
                            </div>
                            <div class="mt-1.5 overflow-hidden rounded-xl border border-hairline-subtle">
                                @foreach ($ruleForm['company_multipliers'] ?? [] as $index => $pair)
                                    <div wire:key="mult-{{ $index }}" class="grid grid-cols-[1fr_1fr_40px] items-center gap-2 px-3 py-1.5 {{ $loop->first ? '' : 'border-t border-hairline-subtle' }}">
                                        <div class="relative"><input type="number" step="1" wire:model.live.debounce.400ms="ruleForm.company_multipliers.{{ $index }}.from" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">≥%</span></div>
                                        <div class="relative"><input type="number" step="0.05" wire:model.live.debounce.400ms="ruleForm.company_multipliers.{{ $index }}.value" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">×</span></div>
                                        <button type="button" wire:click="removeBand('company_multipliers', {{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint hover:bg-rose-50 hover:text-rose-600" aria-label="{{ __($t.'.actions.delete') }}">
                                            <x-icons.delete-icon size="h-4 w-4" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            @error('company_multipliers') <x-validation>{{ $message }}</x-validation> @enderror
                            <p class="mt-3 text-[11.5px] leading-5 text-ink-faint">{{ __($b.'.fields.unit_hint') }}</p>
                            <div class="mt-1.5 flex items-center justify-between">
                                <p class="text-[12px] font-medium text-ink-soft">{{ __($b.'.fields.unit_results') }}</p>
                                @if ($this->unitOptions !== [])
                                    <button type="button" wire:click="addBand('unit_results')" class="text-[14px] font-medium text-ink-faint hover:text-ink">+ {{ __($b.'.actions.add_row') }}</button>
                                @endif
                            </div>
                            @if (($ruleForm['unit_results'] ?? []) !== [])
                                <div class="mt-1.5 overflow-hidden rounded-xl border border-hairline-subtle">
                                    @foreach ($ruleForm['unit_results'] as $index => $pair)
                                        <div wire:key="unit_results-{{ $index }}" class="grid grid-cols-[minmax(0,1fr)_96px_40px] items-center gap-2 px-3 py-1.5 {{ $loop->first ? '' : 'border-t border-hairline-subtle' }}">
                                            <x-ui.select class="min-w-0 truncate" wire:model.live="ruleForm.unit_results.{{ $index }}.from">
                                                <option value="">—</option>
                                                @foreach ($this->unitOptions as $optionId => $optionName)
                                                    <option value="{{ $optionId }}">{{ $optionName }}</option>
                                                @endforeach
                                            </x-ui.select>
                                            <div class="relative"><input type="number" step="0.5" wire:model.live.debounce.400ms="ruleForm.unit_results.{{ $index }}.value" class="{{ $input }} pr-6"><span class="pointer-events-none absolute right-2.5 top-2 text-[12px] text-ink-faint">%</span></div>
                                            <button type="button" wire:click="removeBand('unit_results', {{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint hover:bg-rose-50 hover:text-rose-600" aria-label="{{ __($t.'.actions.delete') }}">
                                                <x-icons.delete-icon size="h-4 w-4" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('unit_results') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    @endif

                    {{-- limits --}}
                    <div>
                        <p class="text-[12.5px] font-semibold text-ink">{{ __($b.'.fields.limits') }}</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <x-label value="{{ __($b.'.fields.cap_pct') }}" />
                                <input type="number" step="5" wire:model.live.debounce.400ms="ruleForm.cap_pct" class="{{ $input }} mt-1">
                                @error('cap_pct') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label value="{{ __($b.'.fields.fund') }}" />
                                <input type="number" step="100" min="0" wire:model.live.debounce.400ms="ruleForm.fund" placeholder="—" class="{{ $input }} mt-1">
                                @error('fund') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>
                        <label class="mt-3 flex items-start gap-2.5 text-[12.5px] text-ink-soft">
                            <input type="checkbox" wire:model.live="ruleForm.scale_to_fund" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                            <span>{{ __($b.'.fields.scale_to_fund') }}</span>
                        </label>
                        <label class="mt-2 flex items-start gap-2.5 text-[12.5px] text-ink-soft">
                            <input type="checkbox" wire:model.live="ruleForm.pay_in_probation" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                            <span>{{ __($b.'.fields.pay_in_probation') }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-hairline-subtle px-5 py-3">
                    @if ($dirty)
                        <button type="button" wire:click="resetRule" class="h-10 rounded-lg px-3 text-[14px] font-medium text-ink-muted hover:bg-[#f4f4f5] hover:text-ink">{{ __($b.'.actions.reset') }}</button>
                    @endif
                    <button type="button" wire:click="saveRule" @disabled(! $dirty) class="h-10 rounded-lg bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover disabled:opacity-40">{{ __($b.'.actions.save_rule') }}</button>
                </div>
            </div>

            {{-- ───────────── result ───────────── --}}
            <div class="{{ $section }} self-start lg:col-span-7">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($b.'.result_title') }}</p>
                    <p class="text-[11.5px] text-ink-faint">{{ __($b.'.result_hint') }}</p>
                </div>
                @php $columns = 'sm:grid sm:grid-cols-[minmax(0,1.6fr)_64px_64px_minmax(0,1fr)_minmax(0,1fr)_88px] sm:items-center sm:gap-3'; @endphp
                <div class="{{ $columns }} hidden border-b border-hairline-subtle px-5 py-2">
                    <span class="hrm-eyebrow">{{ __($b.'.columns.person') }}</span>
                    <span class="hrm-eyebrow text-right">{{ __($b.'.columns.score') }}</span>
                    <span class="hrm-eyebrow text-right">{{ __($b.'.columns.payout') }}</span>
                    <span class="hrm-eyebrow text-right">{{ __($b.'.columns.base') }}</span>
                    <span class="hrm-eyebrow text-right">{{ __($b.'.columns.amount') }}</span>
                    <span class="hrm-eyebrow text-right">{{ __($b.'.columns.status') }}</span>
                </div>
                @forelse ($rows as $row)
                    <div wire:key="bonus-row-{{ $row['scorecard_id'] }}" class="{{ $columns }} flex flex-col gap-1 border-b border-hairline-subtle px-5 py-3 last:border-b-0">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <x-avatar :name="$row['personnel']?->fullname ?? '—'" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $row['personnel']?->fullname ?? '—' }}</p>
                                @if ($row['probation'] ?? false)
                                    <p class="text-[11px] text-amber-600">{{ __($b.'.probation') }}</p>
                                @endif
                                @if ((float) ($row['fte'] ?? 1) < 1)
                                    <p class="text-[11px] text-ink-faint">{{ __($b.'.factors.fte') }} <span class="hrm-num">{{ $fmt($row['fte']) }}</span></p>
                                @endif
                                @if ((float) $row['prorata'] < 1)
                                    <p class="text-[11px] text-ink-faint">{{ __($b.'.factors.prorata') }} <span class="hrm-num">{{ $fmt($row['prorata'] * 100) }}%</span></p>
                                @endif
                            </div>
                        </div>
                        <p class="hrm-num text-[13px] text-ink-soft sm:text-right">{{ $row['score'] === null ? '—' : $fmt($row['score'], 1).'%' }}</p>
                        <p class="hrm-num text-[13px] sm:text-right {{ (float) $row['payout_pct'] > 0 ? 'text-ink-soft' : 'text-ink-faint' }}">{{ $fmt($row['payout_pct']) }}%</p>
                        <p class="hrm-num text-[12.5px] text-ink-muted sm:text-right">
                            @if ($row['base_salary'] === null)
                                <span class="text-amber-600">{{ __($b.'.no_salary') }}</span>
                            @else
                                {{ $canSeeSalary ? $money($row['base_salary']) : '•••' }}
                            @endif
                        </p>
                        <p class="hrm-num text-[14px] font-semibold sm:text-right {{ (float) $row['amount'] > 0 ? 'text-ink' : 'text-ink-faint' }}">{{ $money($row['amount']) }}</p>
                        <div class="sm:text-right">
                            @if ($row['status'])
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium {{ $statusTone[$row['status']] ?? 'bg-[#f4f4f5] text-ink-muted' }}">{{ __($b.'.statuses.'.$row['status']) }}</span>
                            @else
                                <span class="inline-flex rounded-md bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700">{{ __($b.'.statuses.preview') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="text-[13.5px] font-medium text-ink">{{ __($b.'.empty_title') }}</p>
                        <p class="mx-auto mt-1 max-w-sm text-[12.5px] leading-5 text-ink-muted">{{ __($b.'.empty_body') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
