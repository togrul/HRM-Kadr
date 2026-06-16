<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end">
        <label class="w-56">
            <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.designer.code') }}</span>
            <input type="text" wire:model="code" @disabled(! $isNew)
                class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 disabled:bg-zinc-100">
            @error('code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="flex-1">
            <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.designer.name') }}</span>
            <input type="text" wire:model="label"
                class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
            @error('label') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <button type="button" wire:click="save"
            class="h-10 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-500">
            {{ __('orders::order_composer.designer.save') }}
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        {{-- Block editor --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.blocks') }}</h3>
                <button type="button" wire:click="preview"
                    class="h-9 rounded-lg bg-zinc-900 px-4 text-xs font-semibold text-white hover:bg-zinc-700">
                    {{ __('orders::order_composer.labels.preview') }}
                </button>
            </div>

            @foreach ($rows as $i => $row)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm" wire:key="block-{{ $i }}">
                    <div class="mb-2 flex items-center gap-2">
                        <select wire:model="rows.{{ $i }}.kind" class="rounded-lg border-zinc-300 text-xs">
                            @foreach ($this->blockKinds as $bk)
                                <option value="{{ $bk['kind'] }}">{{ $bk['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($row['kind'] === 'clauses')
                            <label class="flex items-center gap-1 text-xs text-zinc-600">
                                <input type="checkbox" wire:model="rows.{{ $i }}.numbered" class="rounded border-zinc-300">
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
                        <textarea wire:model="rows.{{ $i }}.content" rows="3"
                            class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900"></textarea>
                    @endunless
                </div>
            @endforeach

            <button type="button" wire:click="addBlock"
                class="w-full rounded-lg border border-dashed border-zinc-300 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50">
                + {{ __('orders::order_composer.designer.add_block') }}
            </button>
        </div>

        {{-- Variable palette + preview --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.variables') }}</h3>
                <p class="mb-3 text-xs text-zinc-500">{{ __('orders::order_composer.designer.variables_hint') }}</p>
                @foreach ($this->variableGroups as $group => $vars)
                    <div class="mb-3">
                        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $group }}</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($vars as $v)
                                <code class="cursor-default rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] text-zinc-700" title="{{ $v['label'] }}">{{ '{{ '.$v['key'].' }'.'}' }}</code>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($previewHtml !== '')
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-2 text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.labels.preview') }}</h3>
                    <div class="prose max-w-none rounded-lg border border-dashed border-zinc-200 bg-zinc-50 p-4 text-xs leading-relaxed">
                        {!! $previewHtml !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
