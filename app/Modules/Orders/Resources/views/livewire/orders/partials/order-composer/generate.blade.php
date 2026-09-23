{{-- Step 3: preview / download / issue actions and the inline PDF preview.
     Expects: $isEditing, $previewPdf. --}}
<section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
        <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">3</span>
        {{ __('orders::order_composer.labels.step_preview') }}
    </div>
    <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.labels.docx_generate_hint') }}</p>

    <div class="flex flex-wrap items-center justify-end gap-3 pt-1">
        <x-button mode="secondary" wire:click="preview" wire:loading.attr="disabled" wire:target="preview">
            <span wire:loading.remove wire:target="preview">{{ __('orders::order_composer.actions.preview_word') }}</span>
            <span wire:loading wire:target="preview">…</span>
        </x-button>
        <x-button mode="default" wire:click="downloadWord" wire:loading.attr="disabled" wire:target="downloadWord">
            <span wire:loading.remove wire:target="downloadWord">{{ __('orders::order_composer.actions.download_word') }}</span>
            <span wire:loading wire:target="downloadWord">…</span>
        </x-button>
        <x-button mode="black" wire:click="issue" wire:loading.attr="disabled" wire:target="issue">
            <span wire:loading.remove wire:target="issue">{{ $isEditing ? __('orders::order_composer.actions.save') : __('orders::order_composer.actions.issue') }}</span>
            <span wire:loading wire:target="issue">…</span>
        </x-button>
    </div>
    @error('previewPdf') <x-validation>{{ $message }}</x-validation> @enderror

    {{-- Faithful inline PDF preview of the generated document --}}
    <div wire:loading.flex wire:target="preview" class="items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 py-10 text-sm text-zinc-400">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
        {{ __('orders::order_composer.actions.preview_word') }}…
    </div>
    @if ($previewPdf !== '')
        <div wire:loading.remove wire:target="preview" class="overflow-hidden rounded-xl border border-zinc-200 shadow-inner">
            <iframe src="data:application/pdf;base64,{{ $previewPdf }}" class="h-[70vh] w-full" title="preview"></iframe>
        </div>
    @endif
</section>
