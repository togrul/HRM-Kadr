@props([
    'type' => 'text',
    'label' => null,
    'id' => null,
])

<div>
    @if (filled($label))
        <label for="{{ $id }}" class="mb-1.5 block text-[12.5px] font-medium text-ink-soft">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->merge(['class' => 'h-11 w-full rounded-xl border border-hairline bg-white px-3.5 text-[13.5px] text-ink outline-none placeholder:text-ink-faint focus:border-ink focus:ring-2 focus:ring-ink/10']) }}
    >
</div>
