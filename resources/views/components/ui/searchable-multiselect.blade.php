@props([
    'label',
    'model',
    'options' => [],
    'selectedOptions' => [],
    'searchModel' => null,
    'searchPlaceholder' => 'Axtar',
    'help' => null,
    'emptyLabel' => 'Nəticə tapılmadı',
    'selectedSuffix' => null,
])

<div
    x-data="{
        selected: @entangle($model).live,
        normalize(values) {
            return (Array.isArray(values) ? values : []).map((value) => String(value));
        },
        isSelected(id) {
            return this.normalize(this.selected).includes(String(id));
        },
        toggle(id) {
            const key = String(id);
            let values = this.normalize(this.selected);

            values = values.includes(key)
                ? values.filter((value) => value !== key)
                : [...values, key];

            this.selected = values.map((value) => (/^[0-9]+$/.test(value) ? Number(value) : value));
        },
    }"
    class="space-y-3"
>
    <div class="flex items-center justify-between gap-3">
        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $label }}</label>
        <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600" x-text="`${normalize(selected).length} {{ $selectedSuffix ?? __('notifications::common.labels.selected') }}`"></span>
    </div>

    @if ($searchModel)
        <div class="rounded-[1.3rem] border border-zinc-200 bg-white px-3 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
            <input
                type="text"
                wire:model.live.debounce.300ms="{{ $searchModel }}"
                class="w-full border-none bg-transparent px-1 py-0 text-sm text-zinc-800 outline-none ring-0 placeholder:text-zinc-400 focus:ring-0"
                placeholder="{{ $searchPlaceholder }}"
            >
        </div>
    @endif

    @if (! empty($selectedOptions))
        <div class="grid max-h-56 gap-2 overflow-y-auto rounded-[1.35rem] border border-zinc-200 bg-white p-2.5 shadow-[0_10px_24px_rgba(15,23,42,0.03)] lg:grid-cols-2">
            @foreach ($selectedOptions as $option)
                <button
                    type="button"
                    x-on:click="toggle(@js($option['id']))"
                    class="group relative flex min-h-[4.9rem] items-start gap-3 rounded-[1.2rem] border border-zinc-200 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(248,250,252,0.92))] px-3.5 py-3 text-left text-zinc-700 shadow-[0_8px_18px_rgba(15,23,42,0.04)] transition hover:border-zinc-300 hover:shadow-[0_12px_22px_rgba(15,23,42,0.06)]"
                >
                    <span class="min-w-0 flex-1 pr-7">
                        <span class="block whitespace-normal break-words text-[15px] font-semibold leading-6 text-zinc-900">{{ $option['label'] }}</span>
                        @if (! empty($option['meta']))
                            <span class="mt-1.5 block whitespace-normal break-words text-sm leading-5 text-zinc-500">{{ $option['meta'] }}</span>
                        @endif
                    </span>
                    <span class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-zinc-200 bg-white text-sm text-zinc-400 transition group-hover:border-zinc-300 group-hover:text-zinc-600">×</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="max-h-64 overflow-y-auto rounded-[1.35rem] border border-zinc-200 bg-white p-2 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
        @php $currentGroup = null; @endphp
        @forelse ($options as $option)
            @if (($option['group'] ?? null) !== $currentGroup)
                @php $currentGroup = $option['group'] ?? null; @endphp
                @if ($currentGroup)
                    <div class="sticky top-0 z-[1] px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400 backdrop-blur bg-white/95">
                        {{ $currentGroup }}
                    </div>
                @endif
            @endif
            <label class="grid cursor-pointer grid-cols-[auto_minmax(0,1fr)] gap-3 rounded-2xl border border-transparent px-3 py-3 transition hover:border-zinc-200 hover:bg-zinc-50">
                <input
                    type="checkbox"
                    class="mt-1 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                    :checked="isSelected(@js($option['id']))"
                    x-on:change="toggle(@js($option['id']))"
                >
                <span class="min-w-0 flex-1">
                    <span class="block break-words text-sm font-medium leading-6 text-zinc-800" style="{{ isset($option['indent']) ? 'padding-left: '.(((int) $option['indent']) * 16).'px' : '' }}">{{ $option['label'] }}</span>
                    @if (! empty($option['meta']))
                        <span class="mt-1 block break-words text-xs leading-5 text-zinc-500" style="{{ isset($option['indent']) ? 'padding-left: '.(((int) $option['indent']) * 16).'px' : '' }}">{{ $option['meta'] }}</span>
                    @endif
                </span>
            </label>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-6 text-sm text-zinc-500">
                {{ $emptyLabel }}
            </div>
        @endforelse
    </div>

    @if ($help)
        <p class="text-xs text-zinc-500">{{ $help }}</p>
    @endif
</div>
