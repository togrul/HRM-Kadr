@props(['options' => [], 'placeholder' => '—'])
@php $model = $attributes->wire('model')->value(); @endphp

{{--
    Searchable, tree-indented picker for list-bound order fields. Filters client-side
    (handles long lists like the structure tree) and submits the chosen record id.
--}}
<div
    x-data="{
        open: false,
        search: '',
        value: @entangle($model),
        options: @js($options),
        get filtered() {
            const q = this.search.trim().toLocaleLowerCase('az');
            if (!q) return this.options;
            return this.options.filter(o => o.label.toLocaleLowerCase('az').includes(q));
        },
        get selectedLabel() {
            const o = this.options.find(o => String(o.id) === String(this.value));
            return o ? o.label : '';
        },
        choose(o) { this.value = String(o.id); this.open = false; this.search = ''; },
        clear() { this.value = ''; this.open = false; this.search = ''; },
    }"
    x-on:keydown.escape="open = false"
    @click.outside="open = false"
    class="relative mt-1"
>
    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
        class="flex w-full items-center justify-between gap-2 rounded-lg bg-neutral-100 px-3 py-2 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span class="truncate" :class="selectedLabel ? 'text-zinc-900' : 'text-zinc-400'"
            x-text="selectedLabel || @js($placeholder)"></span>
        <svg class="h-4 w-4 shrink-0 text-zinc-400 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg">
        <div class="border-b border-zinc-100 p-2">
            <input x-ref="search" x-model="search" type="text" placeholder="Axtar…"
                class="w-full rounded-lg border border-zinc-200 px-2.5 py-1.5 text-sm focus:border-zinc-400 focus:ring-0">
        </div>
        <ul class="max-h-64 overflow-auto py-1">
            <template x-if="value">
                <li><button type="button" @click="clear()"
                    class="block w-full px-3 py-1.5 text-left text-xs text-zinc-400 hover:bg-zinc-50">— Təmizlə —</button></li>
            </template>
            <template x-for="o in filtered" :key="o.id">
                <li>
                    <button type="button" @click="choose(o)"
                        :style="`padding-left: ${12 + o.depth * 16}px`"
                        :class="String(o.id) === String(value) ? 'bg-zinc-900 text-white' : 'text-zinc-700 hover:bg-zinc-100'"
                        class="block w-full py-1.5 pr-3 text-left text-sm">
                        <span x-text="o.label"></span>
                    </button>
                </li>
            </template>
            <template x-if="filtered.length === 0">
                <li class="px-3 py-3 text-center text-xs text-zinc-400">Tapılmadı</li>
            </template>
        </ul>
    </div>
</div>
