<div class="flex flex-col space-y-8" x-data="{}">
    <div class="sidemenu-title">
        <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="flex bg-slate-100 rounded-md py-1 px-1 w-max">
        @foreach ($steps as $key => $step)
            <button @class([
                'flex appearance-none px-3 py-1 rounded-md justify-center items-center text-sm cursor-pointer transition-all duration-300 hover:bg-slate-50 text-slate-600',
                'bg-white shadow-sm text-slate-900' => $key === $currentStep
            ])
                wire:click.prevent="setCurrentStep({{$key}})"
            >
                {{ __($step) }}
            </button>
        @endforeach
    </div>

    <div class="flex w-full bg-slate-50 rounded-md px-2 py-3">
        @foreach ($steps as $key => $step)
            <div x-show="$wire.currentStep === {{ $key }}"
                 class="flex w-full"
            >
                @include("includes.informations.".\Illuminate\Support\Str::slug($step))
            </div>
        @endforeach
    </div>
</div>
