@php
    $aligns = [
        'left' => ['Sol', 'M3 5h18M3 10h12M3 15h18M3 20h12'],
        'center' => ['Mərkəz', 'M3 5h18M6 10h12M3 15h18M6 20h12'],
        'right' => ['Sağ', 'M3 5h18M9 10h12M3 15h18M9 20h12'],
        'justify' => ['Eninə', 'M3 5h18M3 10h18M3 15h18M3 20h18'],
    ];
    $kindIcons = [
        'heading' => 'M4 7V5h16v2M9 19h6M12 5v14',
        'paragraph' => 'M4 6h16M4 12h16M4 18h10',
        'clauses' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        'split' => 'M3 6h7M3 12h7M3 18h7M14 6h7M14 12h7M14 18h7',
        'signature' => 'M3 17c3-6 5-6 7 0s4 4 7-2',
        'spacer' => 'M3 12h18',
    ];
@endphp

<div
    class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
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
    <div class="sticky top-4 z-20 mb-5 rounded-2xl border border-zinc-200/70 bg-white/70 px-3 py-2.5 shadow-[0_1px_3px_rgba(0,0,0,0.04)] backdrop-blur-xl">
        <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-zinc-800 to-black text-white shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </div>
                <div class="leading-tight">
                    <h1 class="text-sm font-semibold tracking-tight text-zinc-900">{{ __('orders::order_composer.designer.title') }}</h1>
                    <p class="hidden text-[11px] text-zinc-400 sm:block">{{ __('orders::order_composer.designer.subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-1 flex-wrap items-center gap-2 lg:justify-end">
                <input type="text" wire:model="code" @disabled(! $isNew) placeholder="kod"
                    class="h-8 w-24 rounded-lg border border-zinc-200 bg-zinc-50/80 px-2.5 text-[13px] text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-400 focus:bg-white focus:ring-0 disabled:opacity-50">
                <input type="text" wire:model="label" placeholder="{{ __('orders::order_composer.designer.name') }}"
                    class="h-8 flex-1 min-w-[140px] rounded-lg border border-zinc-200 bg-zinc-50/80 px-2.5 text-[13px] text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-400 focus:bg-white focus:ring-0">
                @if ($isNew)
                    <select wire:model.live="startFrom"
                        class="h-8 rounded-lg border border-zinc-200 bg-zinc-50/80 px-2 text-[13px] text-zinc-500 focus:border-zinc-400 focus:ring-0">
                        <option value="">↘ {{ __('orders::order_composer.designer.start_from') }}</option>
                        @foreach ($this->availableTemplates as $c => $name)
                            <option value="{{ $c }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 text-[13px] font-medium text-white shadow-sm transition hover:bg-zinc-700 active:scale-[0.98] disabled:opacity-50">
                    <svg wire:loading.remove wire:target="save" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <svg wire:loading wire:target="save" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                    {{ __('orders::order_composer.designer.save') }}
                </button>
            </div>
        </div>
        @error('code') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
        @error('label') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
        {{-- ============ LEFT: block canvas ============ --}}
        <div class="min-w-0 flex-1 space-y-2.5">
            <div class="flex items-center justify-between px-1">
                <div class="flex items-center gap-2">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">{{ __('orders::order_composer.designer.blocks') }}</h3>
                    <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500">{{ count($rows) }}</span>
                </div>
                <p class="hidden text-[11px] text-zinc-400 sm:block">{{ __('orders::order_composer.designer.reorder_hint') }}</p>
            </div>

            @foreach ($rows as $i => $row)
                <div wire:key="block-{{ $i }}"
                    class="group relative flex gap-2 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-zinc-300 hover:shadow-md">
                    {{-- grip + kind icon --}}
                    <div class="flex w-7 shrink-0 flex-col items-center gap-1 pt-1.5">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $kindIcons[$row['kind']] ?? $kindIcons['paragraph'] }}"/></svg>
                        </div>
                        <span class="text-[10px] font-mono text-zinc-300">{{ $i + 1 }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- block toolbar --}}
                        <div class="flex flex-wrap items-center gap-1.5">
                            <select wire:model.live="rows.{{ $i }}.kind"
                                class="h-7 rounded-md border-0 bg-zinc-50 text-xs font-medium text-zinc-700 focus:ring-1 focus:ring-zinc-300">
                                @foreach ($this->blockKinds as $bk)
                                    <option value="{{ $bk['kind'] }}">{{ $bk['label'] }}</option>
                                @endforeach
                            </select>

                            @if (in_array($row['kind'], ['paragraph', 'heading']))
                                <button type="button" wire:click="$toggle('rows.{{ $i }}.bold')"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold transition {{ ($row['bold'] ?? false) ? 'bg-zinc-900 text-white' : 'bg-zinc-50 text-zinc-500 hover:text-zinc-900' }}">B</button>
                            @endif

                            @if ($row['kind'] === 'paragraph')
                                <div class="inline-flex items-center gap-0.5 rounded-md bg-zinc-50 p-0.5">
                                    @foreach ($aligns as $a => $meta)
                                        <button type="button" wire:click="$set('rows.{{ $i }}.align', '{{ $a }}')" title="{{ $meta[0] }}"
                                            class="flex h-6 w-6 items-center justify-center rounded transition {{ ($row['align'] ?? 'left') === $a ? 'bg-zinc-900 text-white' : 'text-zinc-400 hover:bg-white hover:text-zinc-700' }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="{{ $meta[1] }}"/></svg>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @if ($row['kind'] === 'clauses')
                                <label class="flex items-center gap-1.5 rounded-md bg-zinc-50 px-2 py-1 text-xs text-zinc-600">
                                    <input type="checkbox" wire:model.live="rows.{{ $i }}.numbered" class="h-3.5 w-3.5 rounded border-zinc-300 text-zinc-900 focus:ring-0">
                                    {{ __('orders::order_composer.designer.numbered') }}
                                </label>
                            @endif

                            <div class="ml-auto flex items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
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
                                class="mt-1.5 w-full resize-none rounded-lg border-0 bg-zinc-50/50 px-2.5 py-1.5 text-[13px] text-zinc-800 placeholder:text-zinc-300 focus:bg-white focus:ring-1 focus:ring-zinc-200"></textarea>
                        @else
                            <p class="mt-1.5 px-2.5 py-1 text-[11px] italic text-zinc-400">{{ __('orders::order_composer.designer.spacer_note') }}</p>
                        @endunless
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="addBlock"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 py-2.5 text-[13px] font-medium text-zinc-500 transition hover:border-zinc-900 hover:bg-zinc-50 hover:text-zinc-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('orders::order_composer.designer.add_block') }}
            </button>
        </div>

        {{-- ============ RIGHT: sticky variables + preview ============ --}}
        <div class="w-full space-y-3 lg:w-96 lg:shrink-0 lg:sticky lg:top-24">
            {{-- variables --}}
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-zinc-100 px-3.5 py-2.5">
                    <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                    <h3 class="text-[13px] font-semibold text-zinc-900">{{ __('orders::order_composer.designer.variables') }}</h3>
                    <span class="ml-auto text-[11px] text-zinc-400"
                        x-data="{ msg: '' }"
                        x-on:designer-toast.window="msg = @js(__('orders::order_composer.designer.click_text_first')); setTimeout(() => msg = '', 2500)">
                        <span x-show="!msg">{{ __('orders::order_composer.designer.insert_short') }}</span>
                        <span x-show="msg" x-cloak class="font-medium text-amber-600" x-text="msg"></span>
                    </span>
                </div>
                <div class="max-h-[230px] space-y-2.5 overflow-y-auto px-3.5 py-3">
                    @foreach ($this->variableGroups as $group => $vars)
                        <div>
                            <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $group }}</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($vars as $v)
                                    <button type="button"
                                        x-on:click="insert(@js($v['key']))"
                                        title="{{ __('orders::order_composer.designer.eg') }}: {{ $v['sample'] }}"
                                        class="rounded-md border border-zinc-200 bg-white px-2 py-0.5 text-[11px] font-medium text-zinc-600 transition hover:border-zinc-900 hover:bg-zinc-900 hover:text-white">
                                        {{ $v['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- live preview as paper --}}
            <div class="rounded-2xl border border-zinc-200 bg-zinc-100/70 p-2.5 shadow-sm">
                <div class="mb-2 flex items-center gap-2 px-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('orders::order_composer.designer.live_preview') }}</h3>
                </div>
                <div class="max-h-[60vh] overflow-y-auto rounded-lg bg-white px-5 py-5 shadow-[0_2px_8px_rgba(0,0,0,0.06)] ring-1 ring-zinc-200/70" style="font-family: 'Times New Roman', serif;">
                    @if ($previewHtml !== '')
                        <div class="text-[12px] leading-relaxed text-zinc-900">{!! $previewHtml !!}</div>
                    @else
                        <p class="py-12 text-center text-xs text-zinc-300">{{ __('orders::order_composer.designer.preview_empty') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
