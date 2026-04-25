@props([
    'translationNs',
    'analytics',
])

<div class="grid gap-4 xl:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
        <x-ui.field-label as="div" class="tracking-tight">{{ __($translationNs.'.reports.versioned_families') }}</x-ui.field-label>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $analytics['versioned_families'] }}</p>
    </div>

    @foreach (['type_breakdown', 'status_breakdown', 'top_structures', 'top_positions'] as $reportKey)
        @php
            $maxCount = max(1, collect($analytics[$reportKey])->max('count') ?: 1);
        @endphp
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
            <x-ui.field-label as="div" class="tracking-tight">{{ __($translationNs.'.reports.'.$reportKey) }}</x-ui.field-label>
            <div class="mt-3 space-y-2">
                @forelse ($analytics[$reportKey] as $row)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-3 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-zinc-700">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold text-zinc-950">{{ $row['count'] }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-zinc-900/80" style="width: {{ max(8, (int) round(($row['count'] / $maxCount) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">—</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
