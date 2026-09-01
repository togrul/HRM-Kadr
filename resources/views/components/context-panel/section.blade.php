@props([
    'title' => null,
    'padded' => true,
])

{{-- An internal block of the panel card — never its own card. --}}
<div class="border-b border-hairline-subtle last:border-b-0">
    @if ($title)
        <p class="hrm-eyebrow px-3.5 pb-1 pt-3">{{ $title }}</p>
    @endif

    <div @class(['px-1.5 pb-1.5' => $padded, 'pt-1.5' => $padded && ! $title])>{{ $slot }}</div>

    @isset($footer)
        <div class="border-t border-hairline-subtle bg-[#fafafa] px-3.5 py-2.5">{{ $footer }}</div>
    @endisset
</div>
