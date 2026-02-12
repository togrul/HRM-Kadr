<div class="flex flex-col space-y-2 sidemenu-title">
    <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
      {{ $title ?? ''}}
    </h2>
    @if(auth()->user()->can('confirmation-general') && isset($personnelModel) && $personnelModelData->is_pending)
    <div class="flex items-center justify-start px-4 py-2 space-x-3 bg-white border border-gray-200 rounded-md shadow-lg">
        <span class="flex-none">
            <svg class="w-6 h-6 text-rose-500" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6l4 2"/><path d="M16 21.16a10 10 0 1 1 5-13.516"/><path d="M20 11.5v6"/><path d="M20 21.5h.01"/></svg>
        </span>
        <div class="flex items-center justify-between w-full">
            <p class="text-sm font-medium text-gray-900">{{ __('personnel.confirm-message') }}</p>
            <button wire:click="confirmPersonnel" class="px-4 py-2 text-sm font-semibold text-white transition-all duration-200 bg-gray-900 rounded-lg shadow-sm appearance-none hover:bg-gray-700">{{ __('Confirm') }}</button>
        </div>
    </div>
    @endif
</div>

<div
    class="flex flex-col w-full p-5 px-0 mx-auto my-1 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    @isset($personnelModel) wire:init="initStepData" @endisset
>
    <div class="grid items-start grid-cols-8 gap-y-2">
        @foreach ($steps as $key => $st)
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

@php
    $stepView = match((int) $step) {
        1 => 'includes.step1',
        2 => 'includes.step2',
        3 => 'includes.step3',
        4 => 'includes.step4',
        5 => 'includes.step5',
        6 => 'includes.step6',
        7 => 'includes.step7',
        8 => 'includes.step8',
        default => null,
    };
    @endphp

    @if(isset($personnelModel) && isset($stepDataInitialized) && ! $stepDataInitialized)
        <div class="space-y-3">
            <div class="h-10 rounded-lg bg-neutral-100 animate-pulse"></div>
            <div class="h-10 rounded-lg bg-neutral-100 animate-pulse"></div>
            <div class="h-10 rounded-lg bg-neutral-100 animate-pulse"></div>
            <div class="h-10 rounded-lg bg-neutral-100 animate-pulse"></div>
            <div class="h-10 rounded-lg bg-neutral-100 animate-pulse"></div>
        </div>
    @elseif ($stepView)
        @include($stepView)
    @endif

    <div class="flex items-end justify-between w-full">
        @if(! auth()->user()->can('update-personnels') && isset($personnelModel))
            <div class="flex items-center space-x-2">
                <x-icons.lock-icon color="text-rose-500" hover="text-rose-600" size="w-7 h-7"></x-icons.lock-icon>
                <span class="text-sm text-slate-500">{{ __('You have no permission to edit.') }}</span>
            </div>
        @else
            <x-modal-button>{{ __('Save') }}</x-modal-button>
        @endif

        <div class="flex items-center space-x-2">
            @if($step > 1)
                <x-button mode="warning" wire:click.prevent="previousStep">{{ __('Previous') }}</x-button>
            @endif
            @if(array_key_last($steps) != $step)
                <x-button mode="success" wire:click.prevent="nextStep">{{ __('Next') }}</x-button>
            @endif
        </div>
    </div>
</div>
