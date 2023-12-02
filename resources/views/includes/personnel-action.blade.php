<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
      {{ $title ?? ''}}
    </h2>
</div>

<div class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white">
    <div class="grid grid-cols-4 gap-y-4 items-center">
        @foreach ($steps as $key => $st)
            <button wire:click="selectStep({{ $key }})" @class([
                'flex items-center relative flex-col space-y-2 transition-all duration-300 hover:text-green-500 before:content-0 before:absolute before:w-full before:h-[5px] before:z-0 before:top-[14px] before:transition-all before:duration-300',
                'before:bg-gray-200' => $step <= $key,
                'before:bg-green-100' => $step > $key
            ])>
                <span @class([
                    'flex-none w-8 h-8 flex justify-center items-center rounded-full border-2 z-10 transition-all duration-300',
                    'border-gray-200 text-black bg-gray-200' =>  ($step != $key && $step < $key),
                    'border-green-100 text-green-500 bg-green-100' => $step > $key,
                    'border-blue-500 bg-blue-500 text-white' => $step == $key
                ])>
                @if($step <= $key)
                <span class="text-sm">{{ $key }}</span>
                @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                @endif
                </span>
                <span class="text-sm"> {{ $st }}</span>
            </button>
        @endforeach
       
    </div>
    <hr class="py-2" />

    @if($step == 1)
    @include('includes.step1')
    @endif

    @if ($step == 2)
    @include('includes.step2')
    @endif

    @if($step == 3)
    @include('includes.step3')
    @endif

    @if($step == 4)
    @include('includes.step4')
    @endif

    @if($step == 5)
    @include('includes.step5')
    @endif

    @if($step == 6)
    @include('includes.step6')
    @endif

    @if($step == 7)
    @include('includes.step7')
    @endif

    @if($step == 8)
    @include('includes.step8')
    @endif

    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
        <div class="flex items-center space-x-2">
            @if($step > 1)
            <x-button  mode="warning" wire:click.prevent="previousStep">{{ __('Previous') }}</x-button>
            @endif
            @if(array_key_last($steps) != $step)
            <x-button  mode="success" wire:click.prevent="nextStep">{{ __('Next') }}</x-button>
            @endif
        </div>
    </div>
</div>