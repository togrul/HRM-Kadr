<div x-data="{ isOpen: false }"
     class="fixed inset-0 z-50 overflow-hidden"
     aria-labelledby="slide-over-title"
     role="dialog"
     aria-modal="true"
     x-show="isOpen"
     @keydown.escape.window="isOpen = false;$wire.dispatch('closeSideMenu');document.body.classList.remove('overflow-hidden');"
     x-init="
      @php
        $arrEvents = ['personnelAdded','permissionSet','staffAdded','userAdded','menuAdded','fileAdded','candidateAdded','templateAdded','componentAdded','orderAdded','rankAdded'];
      @endphp
          $wire.on('openSideMenu',() => {
               isOpen = true
               document.body.classList.add('overflow-hidden')
          })
          @foreach ($arrEvents as $event)
          $wire.on('{{$event}}',() => {
            isOpen = false
            $wire.dispatch('closeSideMenu')
            document.body.classList.remove('overflow-hidden')
          })
        @endforeach
     "
     style="display: none;margin-top:0 !important"
>
     <div class="absolute inset-0 overflow-hidden">

       <div
          class="absolute inset-0 transition-opacity bg-gray-500 bg-opacity-75"
          aria-hidden="true"
          x-show="isOpen"
          x-transition:enter="transition ease-in-out duration-500"
          x-transition:enter-start="transform opacity-0"
          x-transition:enter-end="transform opacity-100"
          x-transition:leave="transition ease-in-out duration-500"
          x-transition:leave-start="transform opacity-100"
          x-transition:leave-end="transform opacity-0"
          style="display: none;"
     ></div>

       <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">

         <div class="relative w-screen md:max-w-3xl lg:max-w-4xl"
               x-show="isOpen"
               x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
               x-transition:enter-start="transform translate-x-full"
               x-transition:enter-end="transform translate-x-0"
               x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
               x-transition:leave-start="transform translate-x-0"
               x-transition:leave-end="transform translate-x-full"
               style="display: none;"
         >

           <div class="absolute top-0 right-0 flex pt-5 pr-2 sm:pr-4"
               x-show="isOpen"
               x-transition:enter="transition ease-in-out duration-500"
               x-transition:enter-start="transform opacity-0"
               x-transition:enter-end="transform opacity-100"
               x-transition:leave="transition ease-in-out duration-500"
               x-transition:leave-start="transform opacity-100"
               x-transition:leave-end="transform opacity-0"
               style="display: none;"
           >
             <button @click="isOpen=false;$wire.call('closeSideMenu');document.body.classList.remove('overflow-hidden')" class="z-20 p-1 text-white rounded-lg hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
               <span class="sr-only">{{ __('Close') }}</span>
                 <x-icons.remove-icon size="w-7 h-7" color="text-slate-500" hover="text-slate-900"></x-icons.remove-icon>
             </button>
           </div>

           <div class="flex flex-col h-full py-6 overflow-y-scroll bg-white shadow-xl rounded-tl-2xl rounded-bl-2xl">

             <div class="relative flex-1 px-4 sm:px-6" wire:loading.remove>
              {{ $slot }}
             </div>
             <div class="relative flex-1 px-4 sm:px-6" wire:loading>
                <div class="flex flex-col items-center justify-center w-full h-full">
                  <h1 class="text-2xl font-medium uppercase">{{ __('Loading') }}...</h1>
                  <x-modal-loading />
                </div>
             </div>

           </div>
         </div>
       </div>
     </div>
   </div>
