@props(['policy' => null, 'linkLabel' => null])

{{-- «Yalnız əmrlə» rejimli sahənin altındakı izah və «Əmr yarat» keçidi. --}}
@if (is_array($policy) && $policy['mode'] === 'order')
    <p class="mt-1 text-[11.5px] leading-4 text-ink-faint">
        {{ $policy['hint'] }}
        @if (! empty($policy['order_url']))
            <a href="{{ $policy['order_url'] }}" wire:navigate class="font-medium text-ink-soft underline underline-offset-2 hover:text-ink">{{ $linkLabel ?? __('personnel::change_policy.hints.create_order') }}</a>
        @endif
    </p>
@endif
