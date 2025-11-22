<div class="flex flex-col space-y-8" x-data="{}">
    <div class="sidemenu-title">
        <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="flex px-1 py-1 rounded-md bg-neutral-100 w-max">
        @foreach ($steps as $key => $step)
            <button @class([
                'flex appearance-none px-3 py-1 rounded-md justify-center items-center text-sm cursor-pointer transition-all duration-300 hover:bg-neutral-50 text-neutral-600',
                'bg-white shadow-sm text-neutral-900' => $key === $currentStep
            ])
                wire:click.prevent="setCurrentStep({{$key}})"
            >
                {{ __($step) }}
            </button>
        @endforeach
    </div>

    <div class="flex w-full px-2 py-3 rounded-md bg-neutral-50">
        @foreach ($steps as $key => $step)
            <div x-show="$wire.currentStep === {{ $key }}"
                 class="flex w-full"
            >
                @include("includes.informations.".\Illuminate\Support\Str::slug($step))
            </div>
        @endforeach
    </div>
</div>
