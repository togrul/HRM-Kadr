@php $hasCycles = $this->cycles->isNotEmpty(); @endphp

<div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6">
    {{-- ───────────── header ───────────── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::goals.eyebrow') }}</p>
            <h1 class="mt-1.5 text-[1.75rem] font-semibold leading-none tracking-tight text-zinc-950">{{ __('performance_evaluation::goals.title') }}</h1>
            <p class="mt-2 max-w-xl text-[13px] leading-6 text-zinc-500">{{ __('performance_evaluation::goals.subtitle') }}</p>
        </div>

        @if ($hasCycles)
            <div class="flex items-center gap-2.5">
                <div class="min-w-[13rem]">
                    <x-ui.filter-native-select wire:model.live="cycleId">
                        @foreach ($this->cycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                        @endforeach
                    </x-ui.filter-native-select>
                </div>

                @can('manage-performance-evaluation')
                    <button type="button" wire:click="openGoalForm"
                        class="inline-flex h-12 items-center gap-2 rounded-2xl bg-zinc-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 active:scale-[0.98]">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('performance_evaluation::goals.actions.add') }}
                    </button>
                @endcan
            </div>
        @endif
    </div>

    @if (! $hasCycles)
        {{-- ───────────── empty: no performance cycle ───────────── --}}
        <div class="rounded-[28px] border border-zinc-200/70 bg-gradient-to-b from-white to-zinc-50/60 px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-lg">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <h2 class="mt-5 text-lg font-semibold tracking-tight text-zinc-900">{{ __('performance_evaluation::goals.create_cycle_title') }}</h2>
            <p class="mx-auto mt-1.5 max-w-md text-[13px] leading-6 text-zinc-500">{{ __('performance_evaluation::goals.create_cycle_subtitle') }}</p>

            @can('manage-performance-evaluation')
                @if ($showCycleForm)
                    <div class="mx-auto mt-6 grid max-w-2xl grid-cols-1 gap-4 rounded-2xl border border-zinc-200/70 bg-white p-5 text-left shadow-sm sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <x-label value="{{ __('performance_evaluation::goals.fields.cycle_name') }}" />
                            <x-livewire-input mode="gray" name="cycleForm.name" wire:model="cycleForm.name" placeholder="2026 illik" />
                            @error('cycleForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::goals.fields.period_start') }}" />
                            <x-livewire-input mode="gray" type="date" name="cycleForm.period_start" wire:model="cycleForm.period_start" />
                            @error('cycleForm.period_start') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::goals.fields.period_end') }}" />
                            <x-livewire-input mode="gray" type="date" name="cycleForm.period_end" wire:model="cycleForm.period_end" />
                            @error('cycleForm.period_end') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="flex items-end justify-end gap-2 sm:col-span-3">
                            <button type="button" wire:click="$set('showCycleForm', false)" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::goals.actions.cancel') }}</button>
                            <button type="button" wire:click="createCycle" class="h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::goals.actions.save') }}</button>
                        </div>
                    </div>
                @else
                    <button type="button" wire:click="$set('showCycleForm', true)"
                        class="mt-6 inline-flex h-11 items-center gap-2 rounded-2xl bg-zinc-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 active:scale-[0.98]">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('performance_evaluation::goals.actions.create_cycle') }}
                    </button>
                @endif
            @endcan
        </div>
    @else
        {{-- ───────────── summary tiles ───────────── --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @php
                $tiles = [
                    ['label' => __('performance_evaluation::goals.summary.total'), 'value' => $this->summary['total'], 'dot' => 'bg-zinc-300'],
                    ['label' => __('performance_evaluation::goals.summary.active'), 'value' => $this->summary['active'], 'dot' => 'bg-blue-500'],
                    ['label' => __('performance_evaluation::goals.summary.at_risk'), 'value' => $this->summary['at_risk'], 'dot' => 'bg-amber-500'],
                ];
            @endphp
            @foreach ($tiles as $tile)
                <div class="rounded-2xl border border-zinc-200/60 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full {{ $tile['dot'] }}"></span>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ $tile['label'] }}</p>
                    </div>
                    <p class="mt-2 text-[2rem] font-semibold leading-none tracking-tight tabular-nums text-zinc-900">{{ $tile['value'] }}</p>
                </div>
            @endforeach

            @php $avg = (int) $this->summary['avg_progress']; $avgTone = $avg >= 80 ? 'emerald' : ($avg >= 50 ? 'amber' : 'rose'); @endphp
            <div class="rounded-2xl border border-zinc-200/60 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-{{ $avgTone }}-500"></span>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('performance_evaluation::goals.summary.avg_progress') }}</p>
                </div>
                <p class="mt-2 text-[2rem] font-semibold leading-none tracking-tight tabular-nums text-{{ $avgTone }}-600">{{ $avg }}%</p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                    <div class="h-full rounded-full bg-{{ $avgTone }}-500" style="width: {{ max(2, $avg) }}%"></div>
                </div>
            </div>
        </div>

        {{-- ───────────── goal tree ───────────── --}}
        <div class="overflow-hidden rounded-3xl border border-zinc-200/70 bg-white shadow-sm">
            @forelse ($this->tree as $node)
                @include('performance-evaluation::livewire.performance-evaluation.partials.goal-node', ['node' => $node, 'depth' => 0])
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-50 text-zinc-300 ring-1 ring-inset ring-zinc-200/70">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <p class="mt-4 text-sm font-medium text-zinc-700">{{ __('performance_evaluation::goals.empty') }}</p>
                    <p class="mt-1 text-[13px] text-zinc-400">{{ __('performance_evaluation::goals.empty_subtitle') }}</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ───────────── add-goal side modal (teleported; search keeps focus via the PersonnelPicker child) ───────────── --}}
    @can('manage-performance-evaluation')
        <x-side-modal size="x-large">
            @if ($showSideMenu === 'goal-form')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::goals.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::goals.actions.add') }}</h2>
                        <p class="mt-1.5 text-[13px] leading-6 text-zinc-500">{{ __('performance_evaluation::goals.subtitle') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-label value="{{ __('performance_evaluation::goals.fields.title') }}" />
                            <x-livewire-input mode="gray" name="form.title" wire:model="form.title" placeholder="{{ __('performance_evaluation::goals.fields.title') }}" />
                            @error('form.title') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        <div>
                            <x-label value="{{ __('performance_evaluation::goals.fields.type') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="form.goal_type">
                                    <option value="objective">{{ __('performance_evaluation::goals.types.objective') }}</option>
                                    <option value="kpi">{{ __('performance_evaluation::goals.types.kpi') }}</option>
                                    <option value="goal">{{ __('performance_evaluation::goals.types.goal') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>

                        <div>
                            <x-label value="{{ __('performance_evaluation::goals.fields.parent') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="form.parent_goal_id">
                                    <option value="">{{ __('performance_evaluation::goals.fields.no_parent') }}</option>
                                    @foreach ($this->parentOptions as $opt)
                                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <x-label value="{{ __('performance_evaluation::goals.fields.owner') }}" />
                            <div class="mt-1">
                                <livewire:performance-evaluation.personnel-picker
                                    target="goal-owner"
                                    :selectedId="$form['personnel_id']"
                                    :selectedLabel="$form['personnel_label']"
                                    :placeholder="__('performance_evaluation::goals.fields.owner_search')"
                                    wire:key="goal-picker-owner" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                            <div>
                                <x-label value="{{ __('performance_evaluation::goals.fields.weight') }}" />
                                <x-livewire-input mode="gray" type="number" name="form.weight_percent" wire:model="form.weight_percent" min="0" max="100" />
                            </div>
                            <div>
                                <x-label value="{{ __('performance_evaluation::goals.fields.target') }}" />
                                <x-livewire-input mode="gray" type="number" step="0.01" name="form.target_value" wire:model="form.target_value" />
                            </div>
                            <div>
                                <x-label value="{{ __('performance_evaluation::goals.fields.unit') }}" />
                                <x-livewire-input mode="gray" name="form.unit" wire:model="form.unit" />
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <x-label value="{{ __('performance_evaluation::goals.fields.due_date') }}" />
                            <x-livewire-input mode="gray" type="date" name="form.due_date" wire:model="form.due_date" />
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50">{{ __('performance_evaluation::goals.actions.cancel') }}</button>
                        <button type="button" wire:click="saveGoal" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::goals.actions.save') }}</button>
                    </div>
                </div>
            @endif
        </x-side-modal>
    @endcan
</div>
