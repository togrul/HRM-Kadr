@props([
    'size' => 'large'
])

@php
    $sizeClass = match($size){
        'large' => 'md:max-w-3xl lg:max-w-4xl',
        'x-large' => 'md:max-w-4xl lg:max-w-5xl',
        'xx-large' => 'md:max-w-5xl lg:max-w-6xl'
    };
@endphp

<div x-data="{
        isOpen: @entangle('isSideModalOpen').live,
        toggleBody(open) {
            document.body.classList.toggle('overflow-hidden', open)
        }
    }"
     class="fixed inset-0 z-50 overflow-hidden"
     aria-labelledby="slide-over-title"
     role="dialog"
     aria-modal="true"
     x-show="isOpen"
     @keydown.escape.window="isOpen = false; $wire.dispatch('closeSideMenu'); toggleBody(false);"
     x-init="
      @php
        $arrEvents = ['personnelAdded','permissionSet','staffAdded','userAdded','menuAdded','fileAdded','candidateAdded','templateAdded','componentAdded','orderAdded','rankAdded', 'leaveAdded', 'leaveUpdated'];
      @endphp
          toggleBody(isOpen);
          $watch('isOpen', (value) => toggleBody(value));
          $wire.on('openSideMenu',() => {
               console.info('[SideModal] open event received');
               isOpen = true
          })
          @foreach ($arrEvents as $event)
          $wire.on('{{$event}}',() => {
            console.warn('[SideModal] closing because event `{{$event}}` fired');
            isOpen = false
            $wire.dispatch('closeSideMenu')
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

         <div class="relative w-screen {{$sizeClass}}"
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
             <button @click="isOpen=false;toggleBody(false);$wire.call('closeSideMenu')" class="z-20 p-1 text-white rounded-lg hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
               <span class="sr-only">{{ __('Close') }}</span>
                 <x-icons.remove-icon size="w-7 h-7" color="text-slate-500" hover="text-slate-900"></x-icons.remove-icon>
             </button>
           </div>

           <div class="flex flex-col h-full py-6 overflow-y-scroll bg-white shadow-xl rounded-tl-2xl rounded-bl-2xl">

             <div class="relative flex-1 px-4 sm:px-6" wire:loading.remove>
              {{ $slot }}
             </div>
             <div class="relative flex-1 px-4 sm:px-6" wire:loading>
                <div class="w-full space-y-4 animate-pulse">
                    <div class="h-6 rounded-md w-52 bg-slate-200"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                    </div>
                    <div class="h-24 rounded-lg bg-slate-200"></div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                        <div class="h-10 rounded-lg bg-slate-200"></div>
                    </div>
                    <div class="h-12 rounded-lg bg-slate-200"></div>
                </div>
             </div>

           </div>
         </div>
       </div>
     </div>
   </div>
