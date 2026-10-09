@props([
    'model',             // wire:model path of the selected status keys (array)
    'options' => [],     // [{id, label, tone}]
    'label' => '',
    'placeholder' => '',
])

{{-- Checkbox dropdown for a multi-value list filter; same trigger skin as x-ui.select-dropdown. --}}
<div
    x-data="{
        open: false,
        selected: @entangle($model).live,
        labels: @js(collect($options)->pluck('label', 'id')->all()),
        list() { return Array.isArray(this.selected) ? this.selected : []; },
        has(id) { return this.list().includes(id); },
        toggle(id) { this.selected = this.has(id) ? this.list().filter((value) => value !== id) : [...this.list(), id]; },
        summary() {
            const values = this.list();
            if (values.length === 0) return @js($placeholder);
            if (values.length === 1) return this.labels[values[0]] ?? values[0];
            return @js(__('personnel::common.presence.filter_selected', ['count' => '__COUNT__'])).replace('__COUNT__', values.length);
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.stop="open = false"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-label="{{ $label }}"
        class="{{ \App\Support\Ui\FieldStyles::select('relative flex w-full items-center bg-neutral-100 text-left') }}"
    >
        <span class="block truncate text-ink" x-text="summary()">{{ $placeholder }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.100ms
        role="listbox"
        aria-multiselectable="true"
        class="absolute left-0 z-[60] mt-1 w-full min-w-[220px] space-y-0.5 rounded-xl border border-hairline bg-white p-1 text-[13.5px] shadow-overlay"
    >
        @foreach ($options as $option)
            <label wire:key="presence-option-{{ $option['id'] }}" class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 transition hover:bg-[#f4f4f5]">
                <input
                    type="checkbox"
                    class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                    value="{{ $option['id'] }}"
                    x-bind:checked="has(@js($option['id']))"
                    x-on:change="toggle(@js($option['id']))"
                >
                <x-small-badge :mode="$option['tone']" dot>{{ $option['label'] }}</x-small-badge>
            </label>
        @endforeach

        <div class="border-t border-hairline-subtle px-1 pt-1" x-show="list().length > 0">
            <button type="button" x-on:click="selected = []" class="w-full rounded-lg px-2 py-1.5 text-left text-[12px] font-medium text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink">
                {{ __('personnel::common.presence.filter_clear') }}
            </button>
        </div>
    </div>
</div>
