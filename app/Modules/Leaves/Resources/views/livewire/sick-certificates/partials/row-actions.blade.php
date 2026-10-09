{{-- Row actions shared by the register and the employee card. Expects $certificate, $canUpdate, $canCreate. --}}
@php
    $button = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $cancelPrompt = [
        'title' => __('leaves::sick_certificates.confirm.cancel_title'),
        'message' => __('leaves::sick_certificates.confirm.cancel_message'),
        'confirmText' => __('leaves::sick_certificates.actions.cancel'),
        'tone' => 'rose',
        'reason' => [
            'label' => __('leaves::sick_certificates.confirm.cancel_reason'),
            'placeholder' => __('leaves::sick_certificates.confirm.cancel_reason_placeholder'),
            'min' => 3,
        ],
    ];
@endphp

@if ($certificate->status !== 'cancelled')
    <div class="flex items-center justify-end gap-1">
        @if ($canUpdate)
            <button type="button"
                x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'edit', certificateId: {{ (int) $certificate->id }} })"
                title="{{ __('leaves::sick_certificates.actions.edit') }}"
                class="{{ $button }} hover:bg-[#f4f4f5] hover:text-ink">
                <x-icons.document-icon color="text-current" hover="text-current" />
            </button>

            @if ($certificate->status === 'open')
                <button type="button"
                    x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'close', certificateId: {{ (int) $certificate->id }} })"
                    title="{{ __('leaves::sick_certificates.actions.close') }}"
                    class="{{ $button }} hover:bg-emerald-50 hover:text-emerald-600">
                    <x-icons.check-icon color="text-current" hover="text-current" size="w-5 h-5" />
                </button>
            @endif
        @endif

        @if ($canCreate)
            <button type="button"
                x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'extend', certificateId: {{ (int) $certificate->id }} })"
                title="{{ __('leaves::sick_certificates.actions.extend') }}"
                class="{{ $button }} hover:bg-sky-50 hover:text-sky-700">
                <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/><path d="M5 6v12"/></svg>
            </button>
        @endif

        @if ($canUpdate)
            <button type="button"
                x-on:click="$dispatch('confirm-action', { ...{{ \Illuminate\Support\Js::from($cancelPrompt) }}, run: (reason) => $wire.cancelCertificate({{ (int) $certificate->id }}, reason) })"
                title="{{ __('leaves::sick_certificates.actions.cancel') }}"
                class="{{ $button }} hover:bg-rose-50 hover:text-rose-600">
                <x-icons.x-circle-icon color="text-current" hover="text-current" size="w-5 h-5" />
            </button>
        @endif
    </div>
@else
    <span class="block text-right text-ink-faint">&mdash;</span>
@endif
