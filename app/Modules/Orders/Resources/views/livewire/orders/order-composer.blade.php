<div class="mx-auto max-w-5xl space-y-6 py-2">
    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">{{ __('orders::order_composer.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('orders::order_composer.labels.edit_hint') }}</p>
        </div>
        <a href="{{ route('orders') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">← {{ __('orders::order_list.filters.reset') }}</a>
    </div>

    {{-- Step 1: type + employee --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2 text-sm font-semibold text-zinc-900">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">1</span>
            {{ __('orders::order_composer.labels.type') }} &amp; {{ __('orders::order_composer.labels.employee') }}
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.type') }}</span>
                <select wire:model.live="presetCode"
                    class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
                    <option value="">—</option>
                    @foreach ($this->presets as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('presetCode') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            {{-- Employee picker --}}
            <div class="relative">
                <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.employee') }}</span>
                @if ($personnelLabel)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm">
                        <span class="font-medium text-zinc-900">{{ $personnelLabel }}</span>
                        <button type="button" wire:click="clearPersonnel" class="text-zinc-400 hover:text-red-600">✕</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="personnelQuery"
                        placeholder="{{ __('orders::order_composer.labels.employee_search') }}"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
                    @if (count($this->personnelResults) > 0)
                        <div class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-zinc-200 bg-white shadow-lg">
                            @foreach ($this->personnelResults as $r)
                                <button type="button" wire:click="selectPersonnel({{ $r['id'] }})"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-100">{{ $r['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
                @error('personnelId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <label>
                <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.number') }}</span>
                <input type="text" wire:model="orderNumber"
                    class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
            </label>
            <label>
                <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.date') }}</span>
                <input type="text" wire:model="orderDate"
                    class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
            </label>
        </div>
    </div>

    {{-- Step 2: details --}}
    @if (count($this->fieldDefs))
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2 text-sm font-semibold text-zinc-900">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">2</span>
                {{ __('orders::order_composer.labels.fields') }}
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->fieldDefs as $field)
                    <label>
                        <span class="mb-1 block text-sm font-medium text-zinc-700">{{ $field['label'] }}</span>
                        <input type="{{ $field['type'] }}" wire:model="fields.{{ $field['key'] }}"
                            class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
                    </label>
                @endforeach
            </div>
            <div class="mt-5">
                <button type="button" wire:click="generatePreview" wire:loading.attr="disabled"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-zinc-900 px-5 text-sm font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="generatePreview">{{ __('orders::order_composer.actions.generate') }}</span>
                    <span wire:loading wire:target="generatePreview">…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: preview + edit --}}
    @if ($previewHtml !== '')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">3</span>
                    {{ __('orders::order_composer.labels.preview') }}
                </div>
                <button type="button" wire:click="download"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                    {{ __('orders::order_composer.actions.download') }}
                </button>
            </div>

            <div
                x-data="{
                    sync() { $wire.set('editedHtml', $root.innerHTML, false) },
                    seed(html) { if (document.activeElement !== $root) { $root.innerHTML = html } },
                }"
                x-init="seed($wire.previewHtml)"
                x-effect="seed($wire.previewHtml)"
                @input="sync()"
                contenteditable="true"
                class="mx-auto min-h-[400px] max-w-3xl rounded-lg border border-zinc-200 bg-white p-10 text-[15px] leading-7 text-zinc-900 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-900">
            </div>
            @error('previewHtml') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
    @endif
</div>
