@php
    $hasCycles = $this->cycles->isNotEmpty();
    $boxMeta = [
        9 => 'emerald', 8 => 'emerald', 7 => 'amber',
        6 => 'emerald', 5 => 'blue', 4 => 'amber',
        3 => 'blue', 2 => 'amber', 1 => 'rose',
    ];
    $boxTones = [
        'emerald' => ['ring-emerald-200/70', 'bg-emerald-50/40', 'text-emerald-700'],
        'blue' => ['ring-blue-200/70', 'bg-blue-50/40', 'text-blue-700'],
        'amber' => ['ring-amber-200/70', 'bg-amber-50/40', 'text-amber-700'],
        'rose' => ['ring-rose-200/70', 'bg-rose-50/40', 'text-rose-700'],
    ];
    $readiness = ['ready_now', '1_2_years', '3_5_years'];
    $readinessChip = ['ready_now' => 'bg-emerald-50 text-emerald-600', '1_2_years' => 'bg-amber-50 text-amber-600', '3_5_years' => 'bg-zinc-100 text-zinc-500'];
    $riskChip = ['low' => 'bg-emerald-50 text-emerald-600', 'medium' => 'bg-amber-50 text-amber-600', 'high' => 'bg-rose-50 text-rose-600'];
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6">
    {{-- header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::succession.eyebrow') }}</p>
            <h1 class="mt-1.5 text-[1.75rem] font-semibold leading-none tracking-tight text-zinc-950">{{ __('performance_evaluation::succession.title') }}</h1>
            <p class="mt-2 max-w-xl text-[13px] leading-6 text-zinc-500">{{ __('performance_evaluation::succession.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2.5">
            @if ($hasCycles)
                <div class="min-w-[11rem]">
                    <x-ui.filter-native-select wire:model.live="cycleId">
                        @foreach ($this->cycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                        @endforeach
                    </x-ui.filter-native-select>
                </div>
            @endif
            @can('manage-performance-evaluation')
                @php
                    $headerAction = match ($section) {
                        'plans' => ['openPlan', __('performance_evaluation::succession.actions.add_plan')],
                        'pools' => ['openPool', __('performance_evaluation::succession.actions.add_pool')],
                        default => ['openAssess', __('performance_evaluation::succession.actions.assess')],
                    };
                @endphp
                <button type="button" wire:click="{{ $headerAction[0] }}"
                    class="inline-flex h-12 items-center gap-2 rounded-2xl bg-zinc-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 active:scale-[0.98]">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ $headerAction[1] }}
                </button>
            @endcan
        </div>
    </div>

    {{-- summary --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => __('performance_evaluation::succession.summary.assessed'), 'value' => $this->summary['assessed'], 'dot' => 'bg-zinc-300'],
            ['label' => __('performance_evaluation::succession.summary.top_talent'), 'value' => $this->summary['top_talent'], 'dot' => 'bg-emerald-500'],
            ['label' => __('performance_evaluation::succession.summary.plans'), 'value' => $this->summary['plans'], 'dot' => 'bg-blue-500'],
            ['label' => __('performance_evaluation::succession.summary.plans_no_ready'), 'value' => $this->summary['plans_no_ready'], 'dot' => 'bg-rose-500'],
        ] as $tile)
            <div class="rounded-2xl border border-zinc-200/60 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full {{ $tile['dot'] }}"></span>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ $tile['label'] }}</p>
                </div>
                <p class="mt-2 text-[2rem] font-semibold leading-none tracking-tight tabular-nums text-zinc-900">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- section toggle --}}
    <div class="inline-flex w-fit items-center gap-1 rounded-2xl bg-zinc-100 p-1">
        <button type="button" wire:click="setSection('grid')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition', 'bg-white text-zinc-900 shadow-sm' => $section === 'grid', 'text-zinc-500 hover:text-zinc-800' => $section !== 'grid'])>{{ __('performance_evaluation::succession.sections.grid') }}</button>
        <button type="button" wire:click="setSection('plans')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition', 'bg-white text-zinc-900 shadow-sm' => $section === 'plans', 'text-zinc-500 hover:text-zinc-800' => $section !== 'plans'])>{{ __('performance_evaluation::succession.sections.plans') }}</button>
        <button type="button" wire:click="setSection('pools')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition', 'bg-white text-zinc-900 shadow-sm' => $section === 'pools', 'text-zinc-500 hover:text-zinc-800' => $section !== 'pools'])>{{ __('performance_evaluation::succession.sections.pools') }}</button>
    </div>

    @if ($section === 'grid')
        {{-- ───────────── 9-box grid ───────────── --}}
        <div class="rounded-3xl border border-zinc-200/70 bg-white p-5 shadow-sm">
            <div class="flex gap-3">
                {{-- y axis --}}
                <div class="flex w-6 items-center justify-center">
                    <span class="-rotate-90 whitespace-nowrap text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('performance_evaluation::succession.axes.potential') }} →</span>
                </div>
                <div class="flex-1">
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($this->nineBox as $box)
                            @php [$ring, $bg, $txt] = $boxTones[$boxMeta[$box['index']]]; @endphp
                            <div class="min-h-[8.5rem] rounded-2xl p-3 ring-1 ring-inset {{ $ring }} {{ $bg }}">
                                <p class="text-[11px] font-semibold {{ $txt }}">{{ __('performance_evaluation::succession.boxes.b'.$box['index']) }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($box['people'] as $person)
                                        <span class="group inline-flex items-center gap-1 rounded-full bg-white px-2 py-1 text-[12px] font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200/70">
                                            {{ $person['name'] }}
                                            @can('manage-performance-evaluation')
                                                <button type="button" wire:click="removeAssessment({{ $person['assessment_id'] }})" class="text-zinc-300 transition hover:text-rose-500">✕</button>
                                            @endcan
                                        </span>
                                    @empty
                                        <span class="text-[12px] text-zinc-300">—</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- x axis --}}
                    <div class="mt-2 grid grid-cols-3 gap-3 text-center text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                        <span>{{ __('performance_evaluation::succession.axes.low') }}</span>
                        <span>{{ __('performance_evaluation::succession.axes.medium') }}</span>
                        <span>{{ __('performance_evaluation::succession.axes.high') }}</span>
                    </div>
                    <p class="mt-1 text-center text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('performance_evaluation::succession.axes.performance') }} →</p>
                </div>
            </div>
        </div>
    @elseif ($section === 'plans')
        {{-- ───────────── succession plans ───────────── --}}
        <div class="flex flex-col gap-3">
            @forelse ($this->plans as $plan)
                <div wire:key="succ-plan-{{ $plan->id }}" class="rounded-3xl border border-zinc-200/70 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate text-[15px] font-semibold text-zinc-900">{{ $plan->role_title }}</h3>
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase {{ $riskChip[$plan->risk_of_loss] ?? 'bg-zinc-100 text-zinc-500' }}">{{ __('performance_evaluation::succession.risk.'.$plan->risk_of_loss) }} {{ __('performance_evaluation::succession.fields.risk') }}</span>
                            </div>
                            <p class="mt-1 text-[12px] text-zinc-400">
                                {{ $plan->position?->name }}
                                @if ($plan->incumbent) · {{ __('performance_evaluation::succession.fields.incumbent') }}: {{ trim($plan->incumbent->surname.' '.$plan->incumbent->name) }}@endif
                            </p>
                        </div>
                        @can('manage-performance-evaluation')
                            <button type="button"
                                x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('performance_evaluation::succession.confirm_delete_plan')), run: () => $wire.deletePlan({{ $plan->id }}) })"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-500">
                                <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                            </button>
                        @endcan
                    </div>

                    {{-- candidates --}}
                    <div class="mt-4 border-t border-zinc-100 pt-4">
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('performance_evaluation::succession.fields.candidates') }}</p>
                        <div class="flex flex-col gap-1.5">
                            @forelse ($plan->candidates as $candidate)
                                <div wire:key="succ-cand-{{ $candidate->id }}" class="flex items-center justify-between rounded-xl bg-zinc-50/70 px-3 py-2">
                                    <span class="flex items-center gap-2 text-[13px] text-zinc-700">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-white text-zinc-400 ring-1 ring-inset ring-zinc-200/60">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        </span>
                                        {{ trim($candidate->personnel->surname.' '.$candidate->personnel->name) }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        @can('manage-performance-evaluation')
                                            <div class="relative">
                                                <select wire:change="setCandidateReadiness({{ $candidate->id }}, $event.target.value)"
                                                    class="h-7 cursor-pointer appearance-none rounded-lg border-0 py-0 pl-2.5 pr-7 align-middle text-[11px] font-semibold leading-7 {{ $readinessChip[$candidate->readiness] ?? 'bg-zinc-100 text-zinc-500' }} focus:outline-none focus:ring-2 focus:ring-zinc-200">
                                                    @foreach ($readiness as $r)
                                                        <option value="{{ $r }}" @selected($candidate->readiness === $r)>{{ __('performance_evaluation::succession.readiness.'.$r) }}</option>
                                                    @endforeach
                                                </select>
                                                <svg class="pointer-events-none absolute right-2 top-1/2 h-3 w-3 -translate-y-1/2 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                            </div>
                                            <button type="button" wire:click="removeCandidate({{ $candidate->id }})" class="text-zinc-300 transition hover:text-rose-500">✕</button>
                                        @else
                                            <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $readinessChip[$candidate->readiness] ?? 'bg-zinc-100 text-zinc-500' }}">{{ __('performance_evaluation::succession.readiness.'.$candidate->readiness) }}</span>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <p class="text-[13px] text-zinc-400">{{ __('performance_evaluation::succession.no_candidates') }}</p>
                            @endforelse
                        </div>

                        {{-- add candidate (inline personnel search for this plan) --}}
                        @can('manage-performance-evaluation')
                            <div class="relative mt-2">
                                @if ($personnelTarget === 'candidate:'.$plan->id)
                                    <livewire:performance-evaluation.personnel-picker
                                        target="candidate:{{ $plan->id }}"
                                        :placeholder="__('performance_evaluation::succession.fields.personnel_search')"
                                        wire:key="succ-picker-cand-{{ $plan->id }}" />
                                @else
                                    <button type="button" wire:click="startAddCandidate({{ $plan->id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-3 py-1.5 text-[12px] font-medium text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-700">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                        {{ __('performance_evaluation::succession.actions.add_candidate') }}
                                    </button>
                                @endif
                            </div>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-zinc-200/70 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-sm font-medium text-zinc-700">{{ __('performance_evaluation::succession.empty_plans') }}</p>
                    <p class="mt-1 text-[13px] text-zinc-400">{{ __('performance_evaluation::succession.empty_plans_subtitle') }}</p>
                </div>
            @endforelse
        </div>
    @else
        {{-- ───────────── talent pools ───────────── --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @forelse ($this->pools as $pool)
                <div wire:key="succ-pool-{{ $pool->id }}" class="rounded-3xl border border-zinc-200/70 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate text-[15px] font-semibold text-zinc-900">{{ $pool->name }}</h3>
                                <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-indigo-600">{{ __('performance_evaluation::succession.pool_types.'.$pool->pool_type) }}</span>
                            </div>
                            @if ($pool->description)<p class="mt-1 text-[12px] text-zinc-400">{{ $pool->description }}</p>@endif
                        </div>
                        @can('manage-performance-evaluation')
                            <button type="button"
                                x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('performance_evaluation::succession.confirm_delete_pool')), run: () => $wire.deletePool({{ $pool->id }}) })"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-500">
                                <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                            </button>
                        @endcan
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @forelse ($pool->members as $member)
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-50 px-2.5 py-1 text-[12px] font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200/70">
                                {{ trim($member->personnel->surname.' '.$member->personnel->name) }}
                                @can('manage-performance-evaluation')
                                    <button type="button" wire:click="removeMember({{ $member->id }})" class="text-zinc-300 transition hover:text-rose-500">✕</button>
                                @endcan
                            </span>
                        @empty
                            <span class="text-[13px] text-zinc-400">{{ __('performance_evaluation::succession.no_members') }}</span>
                        @endforelse
                    </div>

                    @can('manage-performance-evaluation')
                        <div class="relative mt-3">
                            @if ($personnelTarget === 'member:'.$pool->id)
                                <livewire:performance-evaluation.personnel-picker
                                    target="member:{{ $pool->id }}"
                                    :placeholder="__('performance_evaluation::succession.fields.personnel_search')"
                                    wire:key="succ-picker-member-{{ $pool->id }}" />
                            @else
                                <button type="button" wire:click="startAddMember({{ $pool->id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-3 py-1.5 text-[12px] font-medium text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                    {{ __('performance_evaluation::succession.actions.add_member') }}
                                </button>
                            @endif
                        </div>
                    @endcan
                </div>
            @empty
                <div class="rounded-3xl border border-zinc-200/70 bg-white px-6 py-16 text-center shadow-sm md:col-span-2">
                    <p class="text-sm font-medium text-zinc-700">{{ __('performance_evaluation::succession.empty_pools') }}</p>
                    <p class="mt-1 text-[13px] text-zinc-400">{{ __('performance_evaluation::succession.empty_pools_subtitle') }}</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ───────────── side modal (teleported; search keeps focus via the PersonnelPicker child) ───────────── --}}
    @can('manage-performance-evaluation')
        <x-side-modal size="large">
            @if ($showSideMenu === 'assess')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::succession.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::succession.actions.assess') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.personnel') }}" />
                            <div class="mt-1">
                                <livewire:performance-evaluation.personnel-picker
                                    target="assess"
                                    :selectedId="$assessForm['personnel_id']"
                                    :selectedLabel="$assessForm['personnel_label']"
                                    :placeholder="__('performance_evaluation::succession.fields.personnel_search')"
                                    wire:key="succ-picker-assess" />
                            </div>
                            @error('assessForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label value="{{ __('performance_evaluation::succession.fields.performance_level') }}" />
                                <div class="mt-1">
                                    <x-ui.filter-native-select wire:model="assessForm.performance_level">
                                        <option value="1">{{ __('performance_evaluation::succession.axes.low') }}</option>
                                        <option value="2">{{ __('performance_evaluation::succession.axes.medium') }}</option>
                                        <option value="3">{{ __('performance_evaluation::succession.axes.high') }}</option>
                                    </x-ui.filter-native-select>
                                </div>
                            </div>
                            <div>
                                <x-label value="{{ __('performance_evaluation::succession.fields.potential_level') }}" />
                                <div class="mt-1">
                                    <x-ui.filter-native-select wire:model="assessForm.potential_level">
                                        <option value="1">{{ __('performance_evaluation::succession.axes.low') }}</option>
                                        <option value="2">{{ __('performance_evaluation::succession.axes.medium') }}</option>
                                        <option value="3">{{ __('performance_evaluation::succession.axes.high') }}</option>
                                    </x-ui.filter-native-select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.note') }}" />
                            <x-livewire-input mode="gray" name="assessForm.note" wire:model="assessForm.note" />
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::succession.actions.cancel') }}</button>
                        <button type="button" wire:click="saveAssessment" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::succession.actions.save') }}</button>
                    </div>
                </div>
            @endif

            @if ($showSideMenu === 'plan')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::succession.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::succession.actions.add_plan') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-label value="{{ __('performance_evaluation::succession.fields.role_title') }}" />
                            <x-livewire-input mode="gray" name="planForm.role_title" wire:model="planForm.role_title" />
                            @error('planForm.role_title') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.position') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="planForm.position_id">
                                    <option value="">{{ __('performance_evaluation::succession.fields.no_position') }}</option>
                                    @foreach ($this->positionOptions as $opt)
                                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.incumbent') }}" />
                            <div class="mt-1">
                                <livewire:performance-evaluation.personnel-picker
                                    target="incumbent"
                                    :selectedId="$planForm['incumbent_personnel_id']"
                                    :selectedLabel="$planForm['incumbent_label']"
                                    :placeholder="__('performance_evaluation::succession.fields.personnel_search')"
                                    wire:key="succ-picker-incumbent" />
                            </div>
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.risk') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="planForm.risk_of_loss">
                                    <option value="low">{{ __('performance_evaluation::succession.risk.low') }}</option>
                                    <option value="medium">{{ __('performance_evaluation::succession.risk.medium') }}</option>
                                    <option value="high">{{ __('performance_evaluation::succession.risk.high') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.impact') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="planForm.impact_of_loss">
                                    <option value="low">{{ __('performance_evaluation::succession.risk.low') }}</option>
                                    <option value="medium">{{ __('performance_evaluation::succession.risk.medium') }}</option>
                                    <option value="high">{{ __('performance_evaluation::succession.risk.high') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <x-label value="{{ __('performance_evaluation::succession.fields.notes') }}" />
                            <x-livewire-input mode="gray" name="planForm.notes" wire:model="planForm.notes" />
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::succession.actions.cancel') }}</button>
                        <button type="button" wire:click="savePlan" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::succession.actions.save') }}</button>
                    </div>
                </div>
            @endif

            @if ($showSideMenu === 'pool')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::succession.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::succession.actions.add_pool') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.pool_name') }}" />
                            <x-livewire-input mode="gray" name="poolForm.name" wire:model="poolForm.name" />
                            @error('poolForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.pool_type') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="poolForm.pool_type">
                                    <option value="hipo">{{ __('performance_evaluation::succession.pool_types.hipo') }}</option>
                                    <option value="successor">{{ __('performance_evaluation::succession.pool_types.successor') }}</option>
                                    <option value="critical_role">{{ __('performance_evaluation::succession.pool_types.critical_role') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::succession.fields.notes') }}" />
                            <x-livewire-input mode="gray" name="poolForm.description" wire:model="poolForm.description" />
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::succession.actions.cancel') }}</button>
                        <button type="button" wire:click="savePool" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::succession.actions.save') }}</button>
                    </div>
                </div>
            @endif
        </x-side-modal>
    @endcan
</div>
