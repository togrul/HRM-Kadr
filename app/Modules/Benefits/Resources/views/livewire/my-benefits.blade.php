@php
    $check = 'rounded border-hairline text-ink focus:ring-[#e4e4e7]';
    $checkLabel = 'inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted';
@endphp

<div class="flex flex-col gap-4">
    @if ($this->personnel)
        <livewire:benefits.total-rewards :tabel-no="(string) $this->personnel->tabel_no" :key="'my-benefits-rewards-'.$this->personnel->id" />
    @endif

    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.my.current') }}</h2>
        </div>
        <div class="divide-y divide-hairline-subtle">
            @forelse ($this->enrollments as $enrollment)
                @php $window = $enrollment->renewalWindow; @endphp
                <div wire:key="my-benefit-{{ $enrollment->id }}" class="px-4 py-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $enrollment->plan?->name }}</p>
                            <p class="text-[11.5px] text-ink-faint">
                                {{ __('benefits::dashboard.types.'.($enrollment->plan?->type ?? 'other')) }}
                                · {{ $enrollment->start_date?->format('d.m.Y') }} – {{ $enrollment->end_date?->format('d.m.Y') ?? '…' }}
                            </p>
                            @if ($enrollment->dependents->isNotEmpty())
                                <p class="mt-1 text-[11.5px] text-ink-muted">{{ __('benefits::dashboard.fields.dependents') }}: {{ $enrollment->dependents->pluck('full_name')->implode(', ') }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="hrm-num text-[13px] text-ink">{{ __('benefits::dashboard.my.employer_pays', ['amount' => $money($enrollment->employer_amount)]) }}</p>
                            <p class="hrm-num text-[11.5px] text-ink-faint">{{ __('benefits::dashboard.my.you_pay', ['amount' => $money($enrollment->employee_amount)]) }}</p>
                        </div>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($enrollment->plan?->allows_dependents)
                            <x-pill-button wire:click="editDependents({{ $enrollment->id }})">{{ __('benefits::dashboard.actions.dependents') }}</x-pill-button>
                        @endif

                        @if ($window && $window->status === 'open')
                            <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-amber-200 bg-amber-50 px-2.5 py-1.5">
                                <span class="text-[12px] text-amber-900">{{ __('benefits::dashboard.my.window_open', ['date' => $window->closes_on?->format('d.m.Y')]) }}</span>
                                <x-pill-button :variant="$enrollment->renewal_decision === 'renew' ? 'primary' : 'secondary'" wire:click="decide({{ $enrollment->id }}, 'renew')">{{ __('benefits::dashboard.decisions.renew') }}</x-pill-button>
                                <x-pill-button :variant="$enrollment->renewal_decision === 'opt_out' ? 'primary' : 'secondary'" wire:click="decide({{ $enrollment->id }}, 'opt_out')">{{ __('benefits::dashboard.decisions.opt_out') }}</x-pill-button>
                                <x-pill-button wire:click="startSwitch({{ $enrollment->id }})">{{ __('benefits::dashboard.actions.switch_plan') }}</x-pill-button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8">
                    <x-ui.empty-state icon="icons.gift-icon" :title="__('benefits::dashboard.my.none')" />
                </div>
            @endforelse
        </div>
    </section>

    @if ($this->availablePlans->isNotEmpty())
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.my.available') }}</h2>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @foreach ($this->availablePlans as $plan)
                    <div wire:key="my-benefit-plan-{{ $plan->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $plan->name }}</p>
                            <p class="text-[11.5px] text-ink-faint">{{ __('benefits::dashboard.types.'.$plan->type) }}@if ($plan->provider) · {{ $plan->provider }}@endif · {{ $money($plan->monthly_cost) }} {{ $plan->currency }}</p>
                        </div>
                        <x-pill-button variant="primary" wire:click="startEnroll({{ $plan->id }})">{{ __('benefits::dashboard.actions.join') }}</x-pill-button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($panel !== '')
        <x-ui.side-panel
            title-id="my-benefits-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('benefits::dashboard.actions.close')"
            width="2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <h2 id="my-benefits-panel-title" class="text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ __('benefits::dashboard.my.panel_'.$panel) }}</h2>
                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('benefits::dashboard.actions.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'dependents')
                    <div class="space-y-1.5">
                        @forelse ($this->kinshipOptions as $kin)
                            <label wire:key="my-benefits-dep-{{ $kin['id'] }}" class="{{ $checkLabel }}">
                                <input type="checkbox" value="{{ $kin['id'] }}" wire:model="dependentIds" class="{{ $check }}" />
                                {{ $kin['full_name'] }}@if ($kin['relation']) · {{ $kin['relation'] }}@endif
                            </label>
                        @empty
                            <p class="text-[12.5px] text-ink-faint">{{ __('benefits::dashboard.enrollments.no_kinship') }}</p>
                        @endforelse
                        @error('dependents') <p class="mt-1 text-[12px] text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    @if ($panel === 'switch')
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.plan')" :error="$errors->first('form.plan_id') ?: $errors->first('plan')">
                            <x-ui.select wire:model="form.plan_id">
                                @forelse ($this->switchOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @empty
                                    <option value="">{{ __('benefits::dashboard.my.no_switch_options') }}</option>
                                @endforelse
                            </x-ui.select>
                        </x-ui.input-shell>
                        <p class="text-[12px] text-ink-faint">{{ __('benefits::dashboard.my.switch_help', ['date' => \Illuminate\Support\Carbon::parse($form['start_date'])->format('d.m.Y')]) }}</p>
                    @else
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.start_date')" :error="$errors->first('form.start_date') ?: $errors->first('plan')">
                            <x-ui.input type="date" wire:model="form.start_date" />
                        </x-ui.input-shell>
                    @endif

                    @php $planForForm = $this->availablePlans->firstWhere('id', (int) $form['plan_id']) ?? $this->switchOptions->firstWhere('id', (int) $form['plan_id']); @endphp
                    @if ($planForForm?->allows_dependents && $this->kinshipOptions !== [])
                        <div>
                            <p class="text-[12px] font-medium text-ink-muted">{{ __('benefits::dashboard.fields.dependents') }}</p>
                            <div class="mt-2 space-y-1.5">
                                @foreach ($this->kinshipOptions as $kin)
                                    <label wire:key="my-benefits-kin-{{ $kin['id'] }}" class="{{ $checkLabel }}">
                                        <input type="checkbox" value="{{ $kin['id'] }}" wire:model="form.kinship_ids" class="{{ $check }}" />
                                        {{ $kin['full_name'] }}@if ($kin['relation']) · {{ $kin['relation'] }}@endif
                                    </label>
                                @endforeach
                            </div>
                            @error('dependents') <p class="mt-1 text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-hairline-subtle bg-white px-5 py-3.5">
                <x-pill-button x-on:click="close()">{{ __('benefits::dashboard.actions.cancel') }}</x-pill-button>
                <x-pill-button variant="primary" wire:click="{{ $panel === 'dependents' ? 'saveDependents' : 'enroll' }}" wire:loading.attr="disabled">{{ __('benefits::dashboard.actions.save') }}</x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
