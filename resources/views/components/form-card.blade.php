@props([
    'title',
    'checkbox' => null,
    'checkboxTitle' => null,
    'type' => 'simple'
])

{{-- <div class="flex flex-col bg-white border-2 border-gray-200 rounded-md space-y-2 shadow-sm overflow-hidden">
    <div class="border-b border-slate-300 bg-zinc-100 px-3 py-2 flex items-center space-x-3">
        <h1 class="text-lg font-medium">{{ __($title) }}</h1>
        @if($checkbox)
            <x-checkbox name="{{ $checkbox }}" model="{{ $checkbox }}">{{ __($checkboxTitle) }}</x-checkbox>
        @endif
    </div>
    <div class="px-3 py-2 flex flex-col space-y-3">
        {{ $slot }}
    </div>
</div> --}}

<div
    data-slot="card-container"
    class="w-full rounded-xl border border-neutral-200/70 bg-neutral-50 p-1.5 dark:border-white/5 dark:bg-white/3 flex flex-col gap-2 h-max">
    <div class="flex items-center justify-between p-2 pb-1.5">
        <div class="flex items-center gap-2.5">
            {{-- <span class="flex size-6 items-center justify-center rounded-md border border-neutral-300 bg-white dark:border-white/10 dark:bg-neutral-700/75">
                {{-- {{ $slot }} --}}
            {{-- </span>  --}}
            <h3 class="text-base font-medium">{{ __($title) ?? '' }}</h3>
             @if($checkbox)
                <x-checkbox name="{{ $checkbox }}" model="{{ $checkbox }}">{{ __($checkboxTitle) }}</x-checkbox>
            @endif
        </div>
    </div>
    <div data-slot="card"
        @class([
            'border-neutral-200 bg-white shadow-md shadow-black/5 dark:border-white/5 dark:bg-white/2 dark:shadow-black/20 flex h-full shrink-0 snap-center flex-col justify-between gap-6 rounded-lg border p-4 w-full md:p-6' => $type == 'simple',
            'divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white shadow-md shadow-black/5 dark:divide-white/8 dark:border-white/8 dark:bg-white/3' => $type == 'divided',
        ])
    >
        {{ $slot }}
    </div>
</div>
