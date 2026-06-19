@php $inp = 'block w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500'; @endphp

<div class="space-y-5"
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
    {{-- Header: identity + start-from + save --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm xl:flex-row xl:items-end">
        <div class="w-40">
            <x-label for="code">{{ __('orders::order_composer.designer.code') }}</x-label>
            <input type="text" wire:model="code" @disabled(! $isNew) placeholder="mukafat"
                class="{{ $inp }} {{ $isNew ? '' : 'opacity-60' }}">
            @error('code') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div class="flex-1">
            <x-label for="label">{{ __('orders::order_composer.designer.name') }}</x-label>
            <input type="text" wire:model="label" placeholder="Mükafat əmri" class="{{ $inp }}">
            @error('label') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        @if ($isNew)
            <div class="w-56">
                <x-label for="startFrom">{{ __('orders::order_composer.designer.start_from') }}</x-label>
                <select wire:model.live="startFrom" class="{{ $inp }}">
                    <option value="">— {{ __('orders::order_composer.designer.start_blank') }} —</option>
                    @foreach ($this->availableTemplates as $c => $name)
                        <option value="{{ $c }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-button mode="success" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            {{ __('orders::order_composer.designer.save') }}
        </x-button>
    </div>

    {{-- How-it-works hint --}}
    <div class="rounded-xl border border-teal-200 bg-teal-50/70 px-4 py-3 text-sm text-teal-900">
        {{ __('orders::order_composer.designer.help') }}
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        {{-- LEFT: blocks --}}
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.blocks') }}</h3>

            @foreach ($rows as $i => $row)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm" wire:key="block-{{ $i }}">
                    <div class="mb-2 flex items-center gap-2">
                        <select wire:model.live="rows.{{ $i }}.kind" class="rounded-lg border-zinc-300 bg-white text-xs">
                            @foreach ($this->blockKinds as $bk)
                                <option value="{{ $bk['kind'] }}">{{ $bk['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($row['kind'] === 'clauses')
                            <label class="flex items-center gap-1 text-xs text-zinc-600">
                                <input type="checkbox" wire:model.live="rows.{{ $i }}.numbered" class="rounded border-zinc-300">
                                {{ __('orders::order_composer.designer.numbered') }}
                            </label>
                        @endif
                        <div class="ml-auto flex gap-1">
                            <button type="button" wire:click="moveBlock({{ $i }}, -1)" class="rounded px-2 py-1 text-xs text-zinc-500 hover:bg-zinc-100">↑</button>
                            <button type="button" wire:click="moveBlock({{ $i }}, 1)" class="rounded px-2 py-1 text-xs text-zinc-500 hover:bg-zinc-100">↓</button>
                            <button type="button" wire:click="removeBlock({{ $i }})" class="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50">{{ __('orders::order_composer.designer.remove') }}</button>
                        </div>
                    </div>
                    @unless ($row['kind'] === 'spacer')
                        <textarea wire:model.blur="rows.{{ $i }}.content" rows="2"
                            x-on:focus="active = $el"
                            placeholder="{{ $row['kind'] === 'clauses' ? __('orders::order_composer.designer.clauses_hint') : __('orders::order_composer.designer.text_hint') }}"
                            class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900"></textarea>
                    @else
                        <p class="text-xs text-zinc-400">{{ __('orders::order_composer.designer.spacer_note') }}</p>
                    @endunless
                </div>
            @endforeach

            <button type="button" wire:click="addBlock"
                class="w-full rounded-lg border border-dashed border-zinc-300 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50">
                + {{ __('orders::order_composer.designer.add_block') }}
            </button>
        </div>

        {{-- RIGHT: variables (click to insert) + live preview --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.variables') }}</h3>
                <p class="mb-3 text-xs text-zinc-500" x-data="{ msg: '' }"
                    x-on:designer-toast.window="msg = @js(__('orders::order_composer.designer.click_text_first')); setTimeout(() => msg = '', 2500)">
                    <span x-show="!msg">{{ __('orders::order_composer.designer.insert_hint') }}</span>
                    <span x-show="msg" x-cloak class="font-medium text-amber-600" x-text="msg"></span>
                </p>
                @foreach ($this->variableGroups as $group => $vars)
                    <div class="mb-3">
                        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group }}</div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($vars as $v)
                                <button type="button"
                                    x-on:click="insert(@js($v['key']))"
                                    title="{{ __('orders::order_composer.designer.eg') }}: {{ $v['sample'] }}"
                                    class="rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs text-zinc-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                    {{ $v['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Live preview --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.live_preview') }}</h3>
                </div>
                <div class="prose max-w-none rounded-lg border border-dashed border-zinc-200 bg-zinc-50 p-4 text-[13px] leading-relaxed">
                    @if ($previewHtml !== '')
                        {!! $previewHtml !!}
                    @else
                        <p class="text-zinc-400">{{ __('orders::order_composer.designer.preview_empty') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
