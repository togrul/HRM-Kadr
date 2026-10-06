@props(['disabled' => false])

{{-- Native select with the design system's trigger skin (shadcn geometry + chevron). --}}
<div class="relative">
    <select @disabled($disabled) {{ $attributes->merge(['class' => \App\Support\Ui\FieldStyles::select()]) }}>
        {{ $slot }}
    </select>
    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
    </svg>
</div>
