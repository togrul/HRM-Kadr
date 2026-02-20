
@props(['headers','divide' => true])

<table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-neutral-200 rounded-xl overflow-hidden shadow-md shadow-black/5']) }}>
     <thead class="bg-neutral-100">
         <tr>
             @foreach ($headers as $header)
               @if($header != 'action')
               <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-neutral-500 uppercase">
                    {{ $header }}
               </th>
               @else
               <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">{{ __('Edit') }}</span>
               </th>
               @endif
             @endforeach
         </tr>
     </thead>

     <tbody @class([
        'bg-white',
        'divide-y divide-neutral-200' => $divide
     ])>
         {{ $slot }}
     </tbody>
 </table>
