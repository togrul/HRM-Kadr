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
    x-cloak
    x-init="
        $wire.on('{{ $eventToCloseModal }}',() => {
            openDeleteModal = false
        })
        $wire.on('closeModalEvent',() => {
            openDeleteModal = false
        })

        @if( $livewireEventToOpenModal)
          $wire.on('{{ $livewireEventToOpenModal }}',() => {
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
  >
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

      <div  x-show.transition.opacity.duration.300ms="openDeleteModal"
            class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
            aria-hidden="true"
      ></div>


       <!-- This element is to trick the browser into centering the modal contents. -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>


      <div  x-show.transition.opacity.duration.300ms="openDeleteModal"
            class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
      >

        <div class="absolute top-0 right-0 pt-4 pr-4">
            <button
                @click="openDeleteModal = false"
                class="text-gray-400 hover:text-gray-500"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-rose-50 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                <!-- Heroicon name: outline/exclamation -->
                  @include('components.icons.info-icon')
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg font-normal leading-6 text-gray-900" id="modal-title">
                  {{ $modalTitle }}
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                   {{ $modalDescription }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
            <button wire:click='{{ $wireClick }}' x-ref="confirmButton" type="button" class="inline-flex justify-center w-full px-4 py-2 text-base font-normal text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
              {{ $modalConfirmButtonText }}
            </button>
            <button @click="openDeleteModal = false" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-normal text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
               {{ __('Cancel') }}
            </button>
          </div>

      </div>
    </div>
  </div>
