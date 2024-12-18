<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
      {{ $title ?? ''}}
    </h2>
</div>

<div class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white">
    <div class="grid grid-cols-8 gap-y-2 items-start">
        @foreach ($steps as $key => $st)
            <button wire:click="selectStep({{ $key }})" @class([
                'flex items-center relative flex-col space-y-2 transition-all duration-300 hover:text-green-500 before:content-0 before:rounded-xl before:absolute before:w-1/2 before:left-3/4 before:h-[3px] before:z-0 before:top-[22px] before:transition-all before:duration-300 last:before:w-0',
                'before:bg-gray-200' => $step <= $key,
                'before:bg-emerald-500' => $step > $key
            ])>
                <span @class([
                    'flex-none w-12 h-12 flex justify-center items-center rounded-full z-10 transition-all duration-300 border-[6px] border-white',
                    'border-gray-200 text-black bg-gray-200' =>  ($step != $key && $step < $key),
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

    @if($step >= 1 && $step <= 8)
        @include('includes.step' . $step)
    @endif

    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
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
