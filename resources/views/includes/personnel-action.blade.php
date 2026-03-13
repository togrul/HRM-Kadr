<div class="flex flex-col space-y-2 sidemenu-title">
    <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
      {{ $title ?? ''}}
    </h2>
    @if(auth()->user()->can('confirmation-general') && isset($personnelModel) && $personnelModelData->is_pending)
    <div class="overflow-hidden rounded-[22px] border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-rose-50/60 shadow-[0_16px_36px_rgba(15,23,42,0.08)]">
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
    class="flex flex-col w-full p-5 px-0 mx-auto my-1 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
>
    @php
        $stepItems = $this->getSteps();
    @endphp
    <div class="grid items-start grid-cols-8 gap-y-2">
        @foreach ($stepItems as $key => $st)
            <button type="button" wire:click.prevent="selectStep({{ $key }})" @class([
                'flex items-center relative flex-col space-y-2 transition-all duration-300 hover:text-green-500 before:content-0 before:rounded-xl before:absolute before:w-1/2 before:left-3/4 before:h-[3px] before:z-0 before:top-[22px] before:transition-all before:duration-300 last:before:w-0',
                'before:bg-gray-200' => $step <= $key,
                'before:bg-emerald-500' => $step > $key
            ])>
                <span @class([
                    'flex-none w-12 h-12 flex justify-center items-center rounded-full z-10 transition-all duration-300 border-[6px] border-white',
                    'border-gray-200 text-black bg-gray-200' => $step < $key,
                    'text-emerald-50 bg-emerald-500' => $step > $key,
                    'bg-blue-600 text-white' => $step == $key
                ])>
                    @if($step <= $key)
                        <span class="text-sm">{{ $key }}</span>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    @endif
                </span>
                <span class="text-sm"> {{ $st }}</span>
            </button>
        @endforeach

    </div>
    <hr class="py-2" />

    <div wire:key="personnel-step-container-{{ (int) $step }}">
        @php
            $activeStepComponent = $this->activeStepComponent;
            $stepPayload = $this->activeStepPayload();
            $stepSearchModels = data_get($stepPayload, 'stepSearchModels', []);
            $stepSearchPlaceholders = data_get($stepPayload, 'stepSearchPlaceholders', []);
        @endphp

        @include($activeStepComponent, $stepPayload)
    </div>

    <div class="flex items-end justify-between w-full">
        @if(! auth()->user()->can('update-personnels') && isset($personnelModel))
            <div class="flex items-center space-x-2">
                <x-icons.lock-icon color="text-rose-500" hover="text-rose-600" size="w-7 h-7"></x-icons.lock-icon>
                <span class="text-sm text-slate-500">{{ __('personnel::common.messages.no_permission_to_edit') }}</span>
            </div>
        @else
            <x-modal-button>{{ __('personnel::common.actions.save') }}</x-modal-button>
        @endif

        <div class="flex items-center space-x-2">
        @if($step > 1)
            <x-button mode="step-prev" wire:click.prevent="previousStep">{{ __('personnel::common.actions.previous') }}</x-button>
        @endif
            @if(array_key_last($stepItems) != $step)
                <x-button mode="step-next" wire:click.prevent="nextStep">{{ __('personnel::common.actions.next') }}</x-button>
            @endif
        </div>
    </div>
</div>
