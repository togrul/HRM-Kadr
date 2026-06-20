@props([
    'label' => null,
    'upload' => null,
    'existingName' => null,
    'error' => null,
    'hint' => null,
])

@php
    $wireModel = (string) ($attributes->get('wire:model') ?? $attributes->get('wire:model.live') ?? $attributes->get('wire:model.defer') ?? 'file-upload');
    $inputId = 'file-upload-'.md5($wireModel.'-'.($label ?? 'upload'));
    $uploadName = null;

    if (is_object($upload) && method_exists($upload, 'getClientOriginalName')) {
        $uploadName = $upload->getClientOriginalName();
    } elseif (filled($existingName)) {
        $uploadName = $existingName;
    }

    $isSelected = filled($uploadName);
@endphp

<x-ui.input-shell :label="$label" :error="$error" :hint="$hint" labelClass="tracking-tight text-zinc-500">
    <label for="{{ $inputId }}" class="group block cursor-pointer">
        <div class="rounded-[24px] bg-[#f0f0f3] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.04)] transition group-hover:bg-[#f5f5f7]">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $isSelected ? 'bg-emerald-50 text-emerald-600' : 'bg-white text-zinc-500 shadow-sm' }}">
                    <x-icons.document-icon class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-2xl bg-zinc-950 px-3 py-2 text-xs font-semibold tracking-tight text-white shadow-sm">
                            {{ __('personnel::portfolio.actions.choose_file') }}
                        </span>
                        <span class="text-sm font-medium text-zinc-600">
                            {{ $isSelected ? __('personnel::portfolio.messages.file_selected') : __('personnel::portfolio.messages.file_not_selected') }}
                        </span>
                    </div>
                    <p class="mt-2 truncate text-sm {{ $isSelected ? 'font-medium text-zinc-900' : 'text-zinc-500' }}">
                        {{ $uploadName ?: __('personnel::portfolio.messages.file_upload_placeholder') }}
                    </p>
                </div>
            </div>
        </div>
        <input id="{{ $inputId }}" type="file" {{ $attributes->class('sr-only') }}>
    </label>
</x-ui.input-shell>
