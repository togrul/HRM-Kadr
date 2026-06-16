<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end">
        <label class="flex-1">
            <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.type') }}</span>
            <select wire:model="presetCode"
                class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
                <option value="">—</option>
                @foreach ($this->presets as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('presetCode') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="w-40">
            <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.number') }}</span>
            <input type="text" wire:model="orderNumber"
                class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
        </label>

        <label class="w-52">
            <span class="mb-1 block text-sm font-medium text-zinc-700">{{ __('orders::order_composer.labels.date') }}</span>
            <input type="text" wire:model="orderDate"
                class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900">
        </label>

        <button type="button" wire:click="generatePreview"
            class="h-10 rounded-lg bg-zinc-900 px-5 text-sm font-semibold text-white transition hover:bg-zinc-700">
            {{ __('orders::order_composer.actions.generate') }}
        </button>
    </div>

    @if ($previewHtml !== '')
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.labels.preview') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('orders::order_composer.labels.edit_hint') }}</p>
                </div>
                <button type="button" wire:click="download"
                    class="h-10 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                    {{ __('orders::order_composer.actions.download') }}
                </button>
            </div>

            {{-- Contenteditable preview: the HR user fixes the text inline; edits sync to $editedHtml. --}}
            <div
                x-data="{
                    sync() { $wire.set('editedHtml', $root.innerHTML, false) },
                    seed(html) { if (document.activeElement !== $root) { $root.innerHTML = html } },
                }"
                x-init="seed($wire.previewHtml)"
                x-effect="seed($wire.previewHtml)"
                @input="sync()"
                contenteditable="true"
                class="prose max-w-none rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-8 text-sm leading-relaxed text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900">
            </div>
            @error('previewHtml') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
    @endif
</div>
