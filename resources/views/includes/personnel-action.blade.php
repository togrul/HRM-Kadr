<div class="flex flex-col space-y-2 sidemenu-title">
    <h2 class="text-[18px] font-semibold tracking-[-0.02em] text-ink" id="slide-over-title">
      {{ $title ?? ''}}
    </h2>
    @if(auth()->user()->can('confirmation-general') && isset($personnelModel) && ($personnelIsPending ?? false))
    <div class="overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-rose-50/60 shadow-card">
        <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-amber-200 bg-white text-amber-600 shadow-sm">
                    <x-icons.pending-icon size="w-6 h-6" color="text-amber-500" />
                </div>
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-small-badge mode="amber">{{ __('personnel::common.states.waiting_for_approval') }}</x-small-badge>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                            {{ __('personnel::common.messages.confirm_title') }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-slate-800">{{ __('personnel::common.messages.confirm_message') }}</p>
                        <p class="text-sm leading-6 text-slate-500">{{ __('personnel::common.messages.confirm_description') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <p class="hidden max-w-48 text-right text-xs leading-5 text-slate-400 lg:block">
                    {{ __('personnel::common.messages.confirm_hint') }}
                </p>
                <button
                    wire:click="confirmPersonnel"
                    wire:loading.attr="disabled"
                    wire:target="confirmPersonnel"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg wire:loading wire:target="confirmPersonnel" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z" fill="currentColor"></path>
                    </svg>
                    <span>{{ __('personnel::common.actions.confirm') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<div
    id="personnel-wizard"
    class="flex flex-col w-full p-5 px-0 mx-auto my-1 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    x-data="{ ...window.personnelWizard(), currentStep: @entangle('step') }"
    x-on:personnel-crud:navigate-approved.window="clearPending()"
    x-on:personnel-crud:save-approved.window="clearPending()"
>
    @php
        $stepItems = $this->getSteps();
        // The personnel file renders its own vertical stepper in the context panel;
        // showing this one too would be two controls for one piece of state.
        $showStepper = ! ($chromeless ?? false);
    @endphp

    @if ($showStepper)
    <div class="space-y-4">
        <p class="text-[12.5px] text-ink-muted">
            <span class="hrm-num text-ink">{{ $step }}/{{ count($stepItems) }}</span>
            <span class="mx-1.5 text-ink-faint">·</span>
            <span class="font-medium text-ink">{{ $stepItems[$step] ?? '' }}</span>
        </p>

        <div class="rounded-2xl border border-hairline bg-white px-4 py-4">
            <div class="relative mx-auto max-w-full">
                <div class="absolute left-0 right-0 top-4 hidden h-px bg-hairline md:block"></div>
                <div
                    class="absolute left-0 top-4 hidden h-px bg-ink transition-all duration-300 ease-out md:block"
                    :style="{ width: progressWidth() }"
                ></div>

                <div class="relative grid grid-cols-4 gap-y-4 md:grid-cols-8 md:gap-x-3">
                @foreach ($stepItems as $key => $st)
                    <button
                        type="button"
                        x-bind:disabled="pendingAction !== null"
                        x-on:click.prevent="
                            if (pendingAction !== null) return;
                            setPending('select', {{ $key }});
                            @if($this->activeStepUsesChildComponent)
                                $dispatch('personnel-crud:request-select', { targetStep: {{ $key }} });
                            @endif
                        "
                        @if($this->activeStepUsesChildComponent)
                            
                        @else
                            wire:click.prevent="selectStep({{ $key }})"
                        @endif
                        class="group flex min-w-0 flex-col items-center gap-2 text-center transition-all duration-200 disabled:cursor-wait disabled:opacity-75"
                    >
                        <span @class([
                            'relative flex h-8 w-8 items-center justify-center rounded-full border text-[12.5px] font-semibold transition-all duration-200',
                            'border-ink bg-ink text-white' => $step > $key,
                            'border-ink bg-white text-ink ring-4 ring-[#f4f4f5]' => $step == $key,
                            'border-hairline bg-white text-ink-faint group-hover:border-zinc-300 group-hover:text-ink-muted' => $step < $key,
                        ])>
                            <span
                                x-cloak
                                x-show="pendingAction === 'select' && pendingStep === {{ $key }}"
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0 -5 5H4z" fill="currentColor"></path>
                                </svg>
                            </span>
                            <span
                                x-show="!(pendingAction === 'select' && pendingStep === {{ $key }})"
                                class="flex items-center justify-center"
                            >
                                @if($step > $key)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @else
                                    <span>{{ $key }}</span>
                                @endif
                            </span>
                        </span>


                        <span @class([
                            'max-w-[120px] text-[12px] leading-[1.3]',
                            'font-semibold text-ink' => $step == $key,
                            'font-medium text-ink-muted' => $step != $key,
                        ])>{{ $st }}</span>
                    </button>
                @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div
        wire:key="personnel-step-container-{{ (int) $step }}"
        x-data="{ visible: true }"
        x-init="$watch('$wire.step', () => { visible = false; requestAnimationFrame(() => requestAnimationFrame(() => visible = true)); })"
        x-show="visible"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="transform-gpu"
    >
        @if($this->activeStepUsesChildComponent)
            @livewire($this->activeStepChildComponent, ['state' => $this->activeStepChildState()], key('personnel-step-livewire-'.(int) $step.'-'.(isset($personnelModel) ? $personnelModel : 'new')))
        @else
            @php
                $activeStepComponent = $this->activeStepComponent;
                $stepPayload = $this->activeStepPayload();
                $stepSearchModels = $this->activeStepSearchModels();
                $stepSearchPlaceholders = $this->activeStepSearchPlaceholders();
            @endphp

            @include($activeStepComponent, $stepPayload)
        @endif
    </div>

    {{-- Sticky: the actions stay in reach however long the step is. --}}
    <div class="sticky bottom-0 z-20 -mb-4 flex w-full items-center justify-between gap-3 border-t border-hairline bg-white/95 py-3 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        @if(! auth()->user()->can('update-personnels') && isset($personnelModel))
            <div class="flex items-center space-x-2">
                <x-icons.lock-icon color="text-rose-500" hover="text-rose-600" size="w-7 h-7"></x-icons.lock-icon>
                <span class="text-sm text-slate-500">{{ __('personnel::common.messages.no_permission_to_edit') }}</span>
            </div>
        @else
            @if($this->activeStepUsesChildComponent)
                <x-button mode="black" class="items-center justify-center space-x-2" x-bind:disabled="pendingAction !== null" x-on:click.prevent="if (pendingAction !== null) return; setPending('save'); $dispatch('personnel-crud:request-save')">
                    <svg x-cloak x-show="pendingAction === 'save'" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0 -5 5H4z" fill="currentColor"></path>
                    </svg>
                    <span>{{ __('personnel::common.actions.save') }}</span>
                </x-button>
            @else
                <x-button mode="black" wire:click="store" wire:loading.attr="disabled" wire:target="store" x-on:click.capture="setPending('save')">
                    <svg wire:loading wire:target="store" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0 -5 5H4z" fill="currentColor"></path>
                    </svg>
                    <span>{{ __('personnel::common.actions.save') }}</span>
                </x-button>
            @endif
        @endif

        <div class="flex items-center space-x-2">
        @if($step > 1)
            @if($this->activeStepUsesChildComponent)
                <x-button mode="step-prev" class="space-x-2" x-bind:disabled="pendingAction !== null" x-on:click.prevent="if (pendingAction !== null) return; setPending('previous', {{ max(1, $step - 1) }}); $dispatch('personnel-crud:request-previous')">
                    <svg x-cloak x-show="pendingAction === 'previous'" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0 -5 5H4z" fill="currentColor"></path>
                    </svg>
                    <span>{{ __('personnel::common.actions.previous') }}</span>
                </x-button>
            @else
                <x-button mode="step-prev" wire:click.prevent="previousStep">{{ __('personnel::common.actions.previous') }}</x-button>
            @endif
        @endif
            @if(array_key_last($stepItems) != $step)
                @if($this->activeStepUsesChildComponent)
                    <x-button mode="step-next" class="space-x-2" x-bind:disabled="pendingAction !== null" x-on:click.prevent="if (pendingAction !== null) return; setPending('next', {{ min(array_key_last($stepItems), $step + 1) }}); $dispatch('personnel-crud:request-next')">
                        <svg x-cloak x-show="pendingAction === 'next'" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0 -5 5H4z" fill="currentColor"></path>
                        </svg>
                        <span>{{ __('personnel::common.actions.next') }}</span>
                    </x-button>
                @else
                    <x-button mode="step-next" wire:click.prevent="nextStep" x-on:click="setPending('next', {{ min(array_key_last($stepItems), $step + 1) }})">{{ __('personnel::common.actions.next') }}</x-button>
                @endif
            @endif
        </div>
    </div>
</div>
