@props([
    'fieldLabel',
    'summaryTitle',
    'summaryHint',
    'definitions' => [],
    'selected' => [],
    'emptyLabel',
    'actionMethod' => 'toggleAudienceTarget',
    'selectedLabel' => null,
])

<x-ui.input-shell :label="$fieldLabel">
    <div class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200/80 pb-4">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-zinc-950">{{ $summaryTitle }}</p>
                <p class="mt-1 text-sm leading-6 text-zinc-500">{{ $summaryHint }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @forelse ($selected as $target)
                    <x-notification.chip mode="emerald">{{ data_get($definitions, $target.'.label', $target) }}</x-notification.chip>
                @empty
                    <x-notification.chip mode="amber">{{ $emptyLabel }}</x-notification.chip>
                @endforelse
            </div>
        </div>

        <div class="mt-4 space-y-3">
            @foreach ($definitions as $targetKey => $targetDefinition)
                @php
                    $isSelected = in_array($targetKey, $selected, true);
                @endphp
                <button
                    type="button"
                    wire:click="{{ $actionMethod }}('{{ $targetKey }}')"
                    @class([
                        'relative w-full overflow-hidden rounded-[1.35rem] border px-5 py-5 text-left transition',
                        'border-emerald-200 bg-emerald-50/80 shadow-[0_14px_28px_rgba(16,185,129,0.08)]' => $isSelected,
                        'border-zinc-200 bg-zinc-50/70 hover:border-zinc-300 hover:bg-white' => ! $isSelected,
                    ])
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-semibold tracking-tight text-zinc-950">{{ $targetDefinition['label'] }}</p>
                                @if ($isSelected && filled($selectedLabel))
                                    <x-notification.chip mode="emerald" size="sm">{{ $selectedLabel }}</x-notification.chip>
                                @endif
                            </div>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500">{{ $targetDefinition['description'] }}</p>
                        </div>
                        <span @class([
                            'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-bold',
                            'border-emerald-300 bg-white text-emerald-700' => $isSelected,
                            'border-zinc-200 bg-white text-zinc-400' => ! $isSelected,
                        ])>
                            {{ $isSelected ? '✓' : '+' }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</x-ui.input-shell>
