@props([
    'attachment' => null,
    'label' => null,
    'actionLabel' => null,
])

@php
    $url = $attachment?->fileUrl();
    $name = $attachment?->original_name ?: $attachment?->display_name;
@endphp

<div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
    @if (filled($label))
        <x-ui.field-label as="div">{{ $label }}</x-ui.field-label>
    @endif

    <div class="mt-1 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-zinc-800">{{ $name ?: '—' }}</p>
        </div>
        @if ($url)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-xs font-semibold text-zinc-700 underline decoration-zinc-300 underline-offset-4">
                {{ $actionLabel ?: __('personnel::portfolio.actions.open_file') }}
            </a>
        @endif
    </div>
</div>
