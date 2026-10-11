@php
    $mode = $state['override'] === null ? 'auto' : ($state['override'] ? 'on' : 'off');
    $profileLabel = \Illuminate\Support\Facades\Lang::has('admin::references.diagnostics.profiles.'.$state['profile'])
        ? __('admin::references.diagnostics.profiles.'.$state['profile'])
        : $state['profile'];
@endphp

<section class="rounded-2xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-card lg:p-7">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1.5">
            <p class="text-[12px] font-medium text-zinc-400">{{ __('benefits::dashboard.module.kicker') }}</p>
            <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ __('benefits::dashboard.module.title') }}</h2>
            <p class="max-w-2xl text-sm leading-7 text-zinc-500">{{ __('benefits::dashboard.module.help') }}</p>
        </div>
        <span @class([
            'inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1 text-[12px] font-medium ring-1 ring-inset',
            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $state['enabled'],
            'bg-zinc-50 text-zinc-500 ring-zinc-200' => ! $state['enabled'],
        ])>
            <span @class(['h-1.5 w-1.5 rounded-full', 'bg-emerald-500' => $state['enabled'], 'bg-zinc-400' => ! $state['enabled']])></span>
            {{ $state['enabled'] ? __('benefits::dashboard.module.enabled') : __('benefits::dashboard.module.disabled') }}
        </span>
    </div>

    <p class="mt-4 text-[13px] text-zinc-600">
        {{ __('benefits::dashboard.module.profile_default', [
            'profile' => $profileLabel,
            'state' => $state['profile_default'] ? __('benefits::dashboard.module.enabled') : __('benefits::dashboard.module.disabled'),
        ]) }}
    </p>

    @unless ($state['installed'])
        <p class="mt-2 text-[13px] text-amber-700">{{ __('benefits::dashboard.module.not_installed') }}</p>
    @endunless

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach (['auto', 'on', 'off'] as $option)
            <x-pill-button
                wire:key="benefits-module-mode-{{ $option }}"
                :variant="$mode === $option ? 'primary' : 'secondary'"
                wire:click="setMode('{{ $option }}')"
                wire:loading.attr="disabled"
                wire:target="setMode"
            >{{ __('benefits::dashboard.module.modes.'.$option) }}</x-pill-button>
        @endforeach
    </div>
</section>
