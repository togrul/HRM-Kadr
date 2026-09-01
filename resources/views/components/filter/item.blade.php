@props([
     'active' => false,
     'mode' => 'default'
])

@php
    $href = $attributes->get('href', '#');
@endphp

<li @class([
     'flex h-[30px] shrink-0 items-center justify-center whitespace-nowrap rounded-[9px] border px-2.5 text-[12px] transition',
     'border-ink bg-ink font-semibold text-white' => $active && $mode == 'default',
     'border-hairline bg-[#f4f4f5] font-medium text-[#3f3f46] hover:bg-[#e4e4e7] hover:text-ink' => ! $active && $mode == 'default',
     'border-white/20 bg-white/15 font-semibold text-white' => $active && $mode == 'dark',
     'border-white/10 bg-transparent font-medium text-white/60 hover:text-white' => ! $active && $mode == 'dark',
 ])>
     <a href="{{ $href }}" @if ($active) aria-current="true" data-active="true" @endif {{ $attributes->except('href') }}>
         {{ $slot }}
     </a>
 </li>
