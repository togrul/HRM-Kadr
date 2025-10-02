@props([
     'title',
     'selected',
     'mode' => 'default',
     'name' => '',
     'hasCheckbox' => false,
     'disabled' => false
])

@php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => 'bg-gray-100'
     };
     $isError = $errors->has($name) ? 'bg-rose-50' : '';
@endphp

<div x-data="{open : false}" class="w-full"
>
     @if($hasCheckbox)
      <div class="flex items-center space-x-2 justify-between">
        <x-label id="listbox-label" for="listbox-label">{{ $title }}</x-label>
        {{ $checkbox }}
      </div>
      @else
      <x-label id="listbox-label" for="listbox-label">{{ $title }}</x-label>
     @endif
     <div class="relative mt-1">
       <button type="button"
               class="relative w-full py-2 pl-3 pr-10 text-left {{ $extraClass }} rounded-lg shadow-sm cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isError }}"
               aria-haspopup="listbox"
               aria-expanded="true"
               aria-labelledby="listbox-label"
               @click="open = !open"
               @click.away="open = false"
               @keydown.escape.window="open = false"
          >
         <span class="flex items-center">
           <span class="block ml-3 font-normal text-gray-900 truncate"
           >
             {{ $selected }}
           </span>
         </span>
         <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
           <!-- Heroicon name: solid/selector -->
           <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
             <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
           </svg>
         </span>
       </button>

         @if(!$disabled)
           <ul
              {{ $attributes->merge(['class' => 'absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm']) }}
              tabindex="-1"
              role="listbox"
              aria-labelledby="listbox-label"
              aria-activedescendant="listbox-option-3"
              x-show="open"
              x-transition:enter="transition ease-in duration-100"
              x-transition:enter-start="opacity-0"
              x-transition:enter-end="opacity-100"
              x-transition:leave="transition ease-in duration-100"
              x-transition:leave-start="opacity-100"
              x-transition:leave-end="opacity-0"
              style="display: none;"
            >
                   {{ $slot }}
           </ul>
         @endif
     </div>
   </div>
