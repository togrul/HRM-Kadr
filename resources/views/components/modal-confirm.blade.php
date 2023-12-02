@props([
'eventToOpenModal' => null,
'livewireEventToOpenModal' => null,
'event-to-close-modal',
'modal-title',
'modal-description',
'modal-confirm-button-text',
'wire-click'
])

<div
    x-init="
        Livewire.on('{{ $eventToCloseModal }}',() => {
            openDeleteModal = false
        })
        @if( $livewireEventToOpenModal)
        Livewire.on('{{ $livewireEventToOpenModal }}',() => {
            openDeleteModal = true
            $nextTick(() => $refs.confirmButton.focus())
          })
        @endif
        "
    x-data={openDeleteModal:false}
    x-show="openDeleteModal"
    @keydown.escape.window="openDeleteModal = false"
    @if( $eventToOpenModal)
    {{ '@'.$eventToOpenModal }}.window="
        openDeleteModal = true
        $nextTick(() => $refs.confirmButton.focus())
    "
    @endif
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;"
>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

        <div  x-show="openDeleteModal"
              x-transition:enter="transition origin-top ease-out duration-300"
              x-transition:enter-start="transform translate-y-full opacity-0"
              x-transition:enter-end="transform translate-y-0 opacity-100"
              x-transition:leave="transition origin-top ease-out duration-300"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
              aria-hidden="true"
        ></div>


        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>


        <div  x-show="openDeleteModal"
              x-transition:enter="transition origin-top ease-out duration-300"
              x-transition:enter-start="transform translate-y-full opacity-0"
              x-transition:enter-end="transform translate-y-0 opacity-100"
              x-transition:leave="transition origin-top ease-out duration-300"
              x-transition:leave-start="transform translate-y-0 opacity-100"
              x-transition:leave-end="transform translate-y-full opacity-0"
              class="inline-block  text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle w-full sm:max-w-xl md:max-w-2xl lg:max-w-4xl"
        >

            <div class="absolute top-0 right-0 py-2 pr-4">
                <button
                    @click="openDeleteModal = false"
                    class="text-gray-400 hover:text-gray-500"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="px-4 py-2 border-b border-gray-300 flex justify-center text-lg items-center font-medium">
                {{ $modalTitle }}
            </div>
            <div class="flex flex-col px-4 py-2 space-y-2">
                {{ $slot }}
            </div>
            <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                <button wire:click='{{ $wireClick }}' x-ref="confirmButton" type="button" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-rose-500 border border-transparent rounded-xl shadow-sm hover:bg-rose-600 sm:ml-3 sm:w-auto">
                    {{ $modalConfirmButtonText }}
                </button>
                <button @click="openDeleteModal = false" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50  sm:mt-0 sm:ml-3 sm:w-auto">
                    {{ __('Cancel') }}
                </button>
            </div>

        </div>
    </div>
</div>
