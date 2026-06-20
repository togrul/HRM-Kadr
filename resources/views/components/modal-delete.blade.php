@props([
    'eventToOpenModal' => null,
    'livewireEventToOpenModal' => null,
    'eventToCloseModal' => null,
    'modalTitle' => null,
    'modalDescription' => null,
    'modalConfirmButtonText' => null,
    'wireClick' => null,
])

{{--
    Adapter — keeps the original x-modal-delete API but routes every call site into the
    single global <x-confirm-modal>, so all confirmations across the app render ONE
    consistent modal. No call-site changes required: when the component's open event
    fires, we dispatch `confirm-action` with this delete's title/message/button and a
    closure that calls the owning component's confirm method.
--}}
<div
    x-data="{
        fire() {
            window.dispatchEvent(new CustomEvent('confirm-action', { detail: {
                title: @js($modalTitle),
                message: @js($modalDescription),
                confirmText: @js($modalConfirmButtonText ?? __('ui::common.actions.delete')),
                tone: 'rose',
                run: () => $wire.{{ $wireClick }}(),
            } }));
        },
    }"
    @if ($livewireEventToOpenModal)
        x-init="$wire.on('{{ $livewireEventToOpenModal }}', () => fire())"
    @endif
    @if ($eventToOpenModal)
        x-on:{{ $eventToOpenModal }}.window="fire()"
    @endif
></div>
