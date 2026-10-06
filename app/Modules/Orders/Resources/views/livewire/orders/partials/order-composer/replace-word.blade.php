{{-- Replace the generated Word with a corrected upload (edit mode only).
     Expects: $hasUploadedDocx, $uploadedDocx. --}}
<section class="space-y-3 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/60 p-5">
    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
        <x-icons.print-file color="text-zinc-500" hover="text-zinc-600"></x-icons.print-file>
        {{ __('orders::order_composer.labels.replace_word') }}
    </div>
    <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.labels.replace_word_hint') }}</p>

    @if ($hasUploadedDocx)
        <div class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
            ✓ {{ __('orders::order_composer.labels.uploaded_word_exists') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="file" wire:model="uploadedDocx" accept=".docx,.doc"
            class="text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-700">
        <x-button mode="black" wire:click="uploadDocx" wire:loading.attr="disabled" wire:target="uploadDocx,uploadedDocx"
            :disabled="! $uploadedDocx">
            <span wire:loading.remove wire:target="uploadDocx,uploadedDocx">{{ __('orders::order_composer.actions.upload_replace') }}</span>
            <span wire:loading wire:target="uploadDocx,uploadedDocx">…</span>
        </x-button>
    </div>
    @error('uploadedDocx') <x-validation>{{ $message }}</x-validation> @enderror
</section>
