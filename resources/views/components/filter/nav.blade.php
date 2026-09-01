@props([
     'mode' => 'default',
     'wrap' => false,
])

{{-- Horizontal section / filter chips (prototype toolbar spec).
     `wrap` trades the scroller for a second row: a section nav is a map of the module, so
     hiding half of it behind a horizontal scroll costs more than the extra row. --}}
<nav {{ $attributes->merge(['class' => $wrap
     ? '-mx-1 flex max-w-full px-1'
     : 'hrm-scroll -mx-1 flex max-w-full items-center overflow-x-auto px-1']) }}>
     <ul @class([
          'flex items-center gap-1.5',
          'flex-wrap' => $wrap,
          'flex-nowrap' => ! $wrap,
     ])>
          {{ $slot }}
     </ul>
</nav>
