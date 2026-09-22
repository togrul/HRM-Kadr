    @if ($activeTab === 'cycles')
        @php
            $d = 'performance_evaluation::dashboard';
            $c = 'performance_evaluation::dashboard.cycle_cards';
            $iconButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300';
            $cycleBadge = [
                'active' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                'draft' => ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'],
                'closed' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
            ];
            $canManage = auth()->user()?->can('manage-performance-evaluation');
            $today = today();
        @endphp

        <div class="mx-auto flex max-w-6xl flex-col gap-4">
            {{-- ───────────── toolbar ───────────── --}}
            <div class="flex flex-col gap-3 rounded-2xl border border-hairline bg-white px-5 py-4 shadow-card sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[15px] font-semibold tracking-[-0.01em] text-ink">
                        {{ __($d.'.tabs.cycles') }}
                        <span class="hrm-num ml-1 text-[13px] font-normal text-ink-faint">{{ $this->cycleCards->count() }}</span>
                    </p>
                    <p class="mt-0.5 max-w-2xl text-[12.5px] leading-5 text-ink-muted">{{ __($c.'.hint') }}</p>
                </div>
                @if ($canManage)
                    <button type="button" wire:click="newCycle" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white transition hover:bg-ink-hover">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __($d.'.cards.cycle_setup') }}
                    </button>
                @endif
            </div>

            {{-- ───────────── cycle cards ───────────── --}}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($this->cycleCards as $cycle)
                    @php
                        [$badgeTone, $badgeDot] = $cycleBadge[$cycle->status] ?? $cycleBadge['draft'];
                        $start = $cycle->period_start;
                        $end = $cycle->period_end;
                        $totalDays = $start && $end ? max(1, $start->diffInDays($end) + 1) : 1;
                        $elapsed = $start ? min($totalDays, max(0, $start->diffInDays($today, false) + 1)) : 0;
                        $percent = (int) round($elapsed / $totalDays * 100);
                        $timeLabel = match (true) {
                            $start && $today->lt($start) => __($c.'.not_started'),
                            $end && $today->gt($end) => __($c.'.finished'),
                            default => __($c.'.days_left', ['days' => $end ? (int) $today->diffInDays($end) : 0]),
                        };
                    @endphp
                    <div wire:key="cycle-card-{{ $cycle->id }}" class="group flex flex-col rounded-2xl border border-hairline bg-white shadow-card transition hover:border-zinc-300">
                        <div class="flex items-start justify-between gap-3 px-4 pt-4">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ $cycle->name }}</p>
                                <p class="mt-0.5 text-[12px] text-ink-faint">{{ __($d.'.cycle_types.'.$cycle->cycle_type) }}</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $badgeTone }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                                {{ __($d.'.statuses.'.$cycle->status) }}
                            </span>
                        </div>

                        {{-- time elapsed --}}
                        <div class="px-4 pt-4">
                            <div class="flex items-center justify-between text-[11.5px]">
                                <span class="hrm-num text-ink-muted">{{ $start?->format('d.m.Y') }} – {{ $end?->format('d.m.Y') }}</span>
                                <span class="text-ink-faint">{{ $timeLabel }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full {{ $cycle->status === 'closed' ? 'bg-zinc-400' : 'bg-ink' }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        {{-- what the cycle holds --}}
                        <div class="mt-4 grid grid-cols-3 divide-x divide-hairline-subtle border-y border-hairline-subtle">
                            @foreach ([
                                'forms' => $cycle->forms_count,
                                'scored' => $cycle->scored_forms_count,
                                'kpi' => $cycle->scorecards_count,
                            ] as $metric => $value)
                                <div class="px-3 py-2.5 text-center">
                                    <p class="hrm-num text-[17px] font-semibold leading-none text-ink">{{ $value }}</p>
                                    <p class="mt-1 text-[11px] leading-tight text-ink-faint">{{ __($c.'.'.$metric) }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex min-h-[44px] items-center justify-between gap-2 px-4 py-2">
                            <p class="truncate text-[12px] text-ink-faint">
                                @if ($cycle->auto_generate_forms)
                                    {{ __($d.'.fields.auto_generate_forms') }}
                                @endif
                            </p>
                            @if ($canManage)
                                <div class="flex shrink-0 items-center gap-0.5">
                                    <button type="button" wire:click="openCycleEditor({{ $cycle->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirmDeleteCycle({{ $cycle->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                        <x-icons.delete-icon size="h-4 w-4" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-16 text-center md:col-span-2 xl:col-span-3">
                        <p class="text-[13.5px] font-medium text-ink">{{ __($d.'.empty.recent_cycles') }}</p>
                        @if ($canManage)
                            <button type="button" wire:click="newCycle" class="mt-4 inline-flex h-9 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-[12.5px] font-semibold text-white hover:bg-ink-hover">{{ __($d.'.cards.cycle_setup') }}</button>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ───────────── editor ───────────── --}}
        @if ($canManage)
            <x-side-modal size="large">
                @if ($showSideMenu === 'form-cycle')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ __($d.'.tabs.cycles') }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $editingCycleId ? __($d.'.labels.editing') : __($d.'.cards.cycle_setup') }}</h2>
                        <p class="mt-3 rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted">{{ __($c.'.hint') }}</p>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-label for="cycle-name">{{ __($d.'.fields.cycle_name') }}</x-label>
                                <x-livewire-input mode="gray" id="cycle-name" wire:model.defer="cycleForm.name" />
                                @error('cycleForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.cycle_type')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-type" direction="auto" wire:model.live="cycleForm.cycle_type"
                                    :model="collect(['annual', 'academic', 'quarterly'])->map(fn ($item) => ['id' => $item, 'label' => __($d.'.cycle_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                @error('cycleForm.cycle_type') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-status" direction="auto" wire:model.live="cycleForm.status"
                                    :model="collect(['draft', 'active', 'closed'])->map(fn ($item) => ['id' => $item, 'label' => __($d.'.statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                @error('cycleForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="cycle-period-start">{{ __($d.'.fields.period_start') }}</x-label>
                                <x-livewire-input mode="gray" id="cycle-period-start" type="date" wire:model.defer="cycleForm.period_start" />
                                @error('cycleForm.period_start') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="cycle-period-end">{{ __($d.'.fields.period_end') }}</x-label>
                                <x-livewire-input mode="gray" id="cycle-period-end" type="date" wire:model.defer="cycleForm.period_end" />
                                @error('cycleForm.period_end') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <x-label for="cycle-description">{{ __($d.'.fields.description') }}</x-label>
                                <textarea id="cycle-description" wire:model.defer="cycleForm.description" rows="3" class="w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0"></textarea>
                                @error('cycleForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <label class="flex items-center gap-2.5 text-[13px] text-ink-soft md:col-span-2">
                                <input type="checkbox" wire:model.defer="cycleForm.auto_generate_forms" class="h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                                {{ __($d.'.fields.auto_generate_forms') }}
                            </label>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveBuilderCycle" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_cycle') }}</button>
                        </div>
                    </div>
                @endif
            </x-side-modal>
        @endif
    @endif
