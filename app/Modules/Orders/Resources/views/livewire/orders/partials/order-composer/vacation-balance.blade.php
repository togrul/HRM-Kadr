{{-- Vacation balance: entitled / used / remaining for the selected employee. Expects: $vb. --}}
@php $over = $vb['requested'] > 0 && $vb['requested'] > $vb['remaining']; @endphp
<section @class([
    'rounded-2xl border p-5 transition-colors',
    'border-rose-200 bg-rose-50/50' => $over,
    'border-zinc-200 bg-white' => ! $over,
])>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
            <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            {{ __('orders::order_composer.vacation.balance') }}
            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500 tabular-nums">{{ $vb['year'] }}</span>
        </div>
        @if ($vb['requested'] > 0)
            <span @class([
                'inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[12px] font-semibold tabular-nums ring-1 ring-inset',
                'bg-rose-50 text-rose-700 ring-rose-100' => $over,
                'bg-emerald-50 text-emerald-700 ring-emerald-100' => ! $over,
            ])>{{ __('orders::order_composer.labels.fields') }}: {{ $vb['requested'] }} {{ __('orders::order_composer.vacation.days_suffix') }}</span>
        @endif
    </div>

    <div class="mt-4 grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
            <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.total') }}</p>
            <p class="mt-0.5 text-xl font-semibold tracking-tight text-zinc-900 tabular-nums">{{ $vb['total'] }}</p>
        </div>
        <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
            <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.used') }}</p>
            <p class="mt-0.5 text-xl font-semibold tracking-tight text-zinc-700 tabular-nums">{{ $vb['used'] }}</p>
        </div>
        <div @class([
            'rounded-xl px-3 py-2.5 ring-1 ring-inset',
            'bg-rose-50 ring-rose-100' => $vb['remaining'] <= 0 || $over,
            'bg-emerald-50 ring-emerald-100' => $vb['remaining'] > 0 && ! $over,
        ])>
            <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.remaining') }}</p>
            <p @class([
                'mt-0.5 text-xl font-semibold tracking-tight tabular-nums',
                'text-rose-700' => $vb['remaining'] <= 0 || $over,
                'text-emerald-700' => $vb['remaining'] > 0 && ! $over,
            ])>{{ $vb['remaining'] }}</p>
        </div>
    </div>

    @if ($over)
        <p class="mt-3 flex items-center gap-1.5 text-[12px] font-medium text-rose-600">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            {{ __('orders::order_composer.vacation.exceeded', ['year' => $vb['year'], 'total' => $vb['total'], 'used' => $vb['used'], 'remaining' => $vb['remaining'], 'requested' => $vb['requested']]) }}
        </p>
    @endif
</section>
