@php
    $aligns = [
        'left' => ['Sol', 'M3 5h18M3 10h12M3 15h18M3 20h12'],
        'center' => ['Mərkəz', 'M3 5h18M6 10h12M3 15h18M6 20h12'],
        'right' => ['Sağ', 'M3 5h18M9 10h12M3 15h18M9 20h12'],
        'justify' => ['Eninə', 'M3 5h18M3 10h18M3 15h18M3 20h18'],
    ];
@endphp

<div
    class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8"
    x-data="{
        active: null,
        insert(key) {
            const el = this.active;
            const token = '{' + '{ ' + key + ' }' + '}';
            if (!el) { window.dispatchEvent(new CustomEvent('designer-toast')); return; }
            const s = el.selectionStart ?? el.value.length;
            const e = el.selectionEnd ?? el.value.length;
            el.value = el.value.slice(0, s) + token + el.value.slice(e);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
            const p = s + token.length;
            el.setSelectionRange(p, p);
        }
    }"
>
    {{-- ============ Sticky toolbar ============ --}}
    <div class="sticky top-4 z-20 rounded-2xl border border-zinc-200/80 bg-white/80 px-4 py-3 shadow-sm backdrop-blur-md">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </div>
                <div class="leading-tight">
                    <h1 class="text-base font-semibold tracking-tight text-zinc-900">{{ __('orders::order_composer.designer.title') }}</h1>
                    <p class="text-xs text-zinc-400">{{ __('orders::order_composer.designer.subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-1 flex-wrap items-center gap-2 lg:justify-end">
                <input type="text" wire:model="code" @disabled(! $isNew) placeholder="kod (mukafat)"
                    class="h-9 w-32 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:bg-white focus:ring-0 disabled:opacity-60">
                <input type="text" wire:model="label" placeholder="{{ __('orders::order_composer.designer.name') }}"
                    class="h-9 flex-1 min-w-[150px] rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:bg-white focus:ring-0">
                @if ($isNew)
                    <select wire:model.live="startFrom"
                        class="h-9 rounded-lg border border-zinc-200 bg-zinc-50 px-2 text-sm text-zinc-600 focus:border-zinc-900 focus:ring-0">
                        <option value="">↘ {{ __('orders::order_composer.designer.start_from') }}</option>
                        @foreach ($this->availableTemplates as $c => $name)
                            <option value="{{ $c }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-700 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="save" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                    {{ __('orders::order_composer.designer.save') }}
                </button>
            </div>
        </div>
        @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- ============ Help ============ --}}
    <div class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-gradient-to-br from-zinc-50 to-white px-4 py-3">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <p class="text-sm leading-relaxed text-zinc-600">{{ __('orders::order_composer.designer.help') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        {{-- ============ LEFT: block canvas ============ --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('orders::order_composer.designer.blocks') }}</h3>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500">{{ count($rows) }}</span>
            </div>

            @foreach ($rows as $i => $row)
                <div wire:key="block-{{ $i }}"
                    class="group rounded-2xl border border-zinc-200 bg-white p-1.5 shadow-sm transition hover:border-zinc-300 hover:shadow">
                    {{-- block toolbar --}}
                    <div class="flex flex-wrap items-center gap-1.5 rounded-xl bg-zinc-50/80 px-2 py-1.5">
                        <select wire:model.live="rows.{{ $i }}.kind"
                            class="h-7 rounded-md border-0 bg-white text-xs font-medium text-zinc-700 shadow-sm focus:ring-1 focus:ring-zinc-300">
                            @foreach ($this->blockKinds as $bk)
                                <option value="{{ $bk['kind'] }}">{{ $bk['label'] }}</option>
                            @endforeach
                        </select>

                        @if (in_array($row['kind'], ['paragraph', 'heading']))
                            {{-- bold toggle --}}
                            <button type="button" wire:click="$toggle('rows.{{ $i }}.bold')"
                                class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold transition {{ ($row['bold'] ?? false) ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-500 shadow-sm hover:text-zinc-900' }}">B</button>
                        @endif

                        @if ($row['kind'] === 'paragraph')
                            {{-- alignment segmented control --}}
                            <div class="inline-flex items-center gap-0.5 rounded-md bg-white p-0.5 shadow-sm">
                                @foreach ($aligns as $a => $meta)
                                    <button type="button" wire:click="$set('rows.{{ $i }}.align', '{{ $a }}')" title="{{ $meta[0] }}"
                                        class="flex h-6 w-6 items-center justify-center rounded transition {{ ($row['align'] ?? 'left') === $a ? 'bg-zinc-900 text-white' : 'text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="{{ $meta[1] }}"/></svg>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if ($row['kind'] === 'clauses')
                            <label class="flex items-center gap-1.5 rounded-md bg-white px-2 py-1 text-xs text-zinc-600 shadow-sm">
                                <input type="checkbox" wire:model.live="rows.{{ $i }}.numbered" class="h-3.5 w-3.5 rounded border-zinc-300 text-zinc-900 focus:ring-0">
                                {{ __('orders::order_composer.designer.numbered') }}
                            </label>
                        @endif

                        <div class="ml-auto flex items-center gap-0.5 opacity-60 transition group-hover:opacity-100">
                            <button type="button" wire:click="moveBlock({{ $i }}, -1)" class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m18 15-6-6-6 6"/></svg>
                            </button>
                            <button type="button" wire:click="moveBlock({{ $i }}, 1)" class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <button type="button" wire:click="removeBlock({{ $i }})" class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-red-50 hover:text-red-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>

                    @unless ($row['kind'] === 'spacer')
                        <textarea wire:model.blur="rows.{{ $i }}.content" rows="2"
                            x-on:focus="active = $el"
                            placeholder="{{ $row['kind'] === 'clauses' ? __('orders::order_composer.designer.clauses_hint') : __('orders::order_composer.designer.text_hint') }}"
                            class="w-full resize-none border-0 bg-transparent px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-300 focus:ring-0"></textarea>
                    @else
                        <p class="px-3 py-2 text-xs italic text-zinc-400">{{ __('orders::order_composer.designer.spacer_note') }}</p>
                    @endunless
                </div>
            @endforeach

            <button type="button" wire:click="addBlock"
                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-200 py-3 text-sm font-medium text-zinc-500 transition hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('orders::order_composer.designer.add_block') }}
            </button>
        </div>

        {{-- ============ RIGHT: variables + live preview ============ --}}
        <div class="space-y-4">
            {{-- variable palette --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="mb-1 flex items-center gap-2">
                    <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.variables') }}</h3>
                </div>
                <p class="mb-3 text-xs text-zinc-500" x-data="{ msg: '' }"
                    x-on:designer-toast.window="msg = @js(__('orders::order_composer.designer.click_text_first')); setTimeout(() => msg = '', 2500)">
                    <span x-show="!msg">{{ __('orders::order_composer.designer.insert_hint') }}</span>
                    <span x-show="msg" x-cloak class="font-medium text-amber-600" x-text="msg"></span>
                </p>
                @foreach ($this->variableGroups as $group => $vars)
                    <div class="mb-3">
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $group }}</div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($vars as $v)
                                <button type="button"
                                    x-on:click="insert(@js($v['key']))"
                                    title="{{ __('orders::order_composer.designer.eg') }}: {{ $v['sample'] }}"
                                    class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 shadow-sm transition hover:border-zinc-900 hover:bg-zinc-900 hover:text-white">
                                    {{ $v['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- live preview as paper --}}
            <div class="rounded-2xl border border-zinc-200 bg-zinc-100 p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-2 px-1">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('orders::order_composer.designer.live_preview') }}</h3>
                </div>
                <div class="mx-auto min-h-[380px] rounded-lg bg-white p-6 shadow-md ring-1 ring-zinc-200" style="font-family: 'Times New Roman', serif;">
                    @if ($previewHtml !== '')
                        <div class="text-[13px] leading-relaxed text-zinc-900">{!! $previewHtml !!}</div>
                    @else
                        <p class="py-10 text-center text-sm text-zinc-300">{{ __('orders::order_composer.designer.preview_empty') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
