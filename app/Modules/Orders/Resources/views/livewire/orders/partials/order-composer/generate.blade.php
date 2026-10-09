{{-- Step 3: preview / download / issue actions and the inline PDF preview.
     Expects: $isEditing, $previewPdf, $previewHtml. --}}
<section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
        <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">3</span>
        {{ __('orders::order_composer.labels.step_preview') }}
    </div>
    <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.labels.docx_generate_hint', ['action' => $isEditing ? __('orders::order_composer.actions.save') : __('orders::order_composer.actions.publish')]) }}</p>

    {{-- one black primary (Nəşr et) on the right; preview secondary; download as an icon --}}
    <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
        <x-pill-button :icon="true" wire:click="downloadWord" wire:loading.attr="disabled" wire:target="downloadWord"
            title="{{ __('orders::order_composer.actions.download_word') }}" aria-label="{{ __('orders::order_composer.actions.download_word') }}">
            <svg wire:loading.remove wire:target="downloadWord" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span wire:loading.flex wire:target="downloadWord"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg></span>
        </x-pill-button>
        <x-pill-button wire:click="preview" wire:loading.attr="disabled" wire:target="preview">
            <span wire:loading.remove wire:target="preview">{{ __('orders::order_composer.actions.preview_word') }}</span>
            <span wire:loading.flex wire:target="preview" class="items-center gap-1.5"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>{{ __('orders::order_composer.actions.preparing') }}</span>
        </x-pill-button>
        <x-pill-button variant="primary" wire:click="issue" wire:loading.attr="disabled" wire:target="issue">
            <span wire:loading.remove wire:target="issue">{{ $isEditing ? __('orders::order_composer.actions.save') : __('orders::order_composer.actions.publish') }}</span>
            <span wire:loading.flex wire:target="issue" class="items-center gap-1.5"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>{{ __('orders::order_composer.actions.preparing') }}</span>
        </x-pill-button>
    </div>
    @error('previewPdf') <x-validation>{{ $message }}</x-validation> @enderror

    {{-- Faithful inline PDF preview of the generated document --}}
    <div wire:loading.flex wire:target="preview" class="items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 py-10 text-sm text-zinc-400">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
        {{ __('orders::order_composer.actions.preparing') }}
    </div>
    @if ($previewPdf !== '')
        <div wire:loading.remove wire:target="preview" class="overflow-hidden rounded-xl border border-zinc-200 shadow-inner">
            <iframe src="data:application/pdf;base64,{{ $previewPdf }}" class="h-[70vh] w-full" title="preview"></iframe>
        </div>
    @elseif ($previewHtml !== '')
        {{-- No LibreOffice on this host: the same document as HTML, in a script-less sandbox. --}}
        <div wire:loading.remove wire:target="preview" class="space-y-2">
            <p class="text-xs text-zinc-500">{{ __('orders::order_list.preview.html_fallback') }}</p>
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-inner">
                <iframe sandbox srcdoc="{{ $previewHtml }}" class="h-[70vh] w-full" title="preview"></iframe>
            </div>
        </div>
    @endif
</section>
