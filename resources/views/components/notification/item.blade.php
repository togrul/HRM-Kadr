@props([
     'type',
     'data'
])

@php
     $color = match($type)
     {
          'birthday' => 'green',
          'stock','bar' => 'blue',
          'payment' => 'red'
     }
@endphp

<div class="flex flex-col w-full">
     <div class="leading-4 space-x-1 space-y-2">
          <div class="flex justify-between items-center w-full">
               <span class="bg-{{$color}}-100 text-xs rounded text-{{$color}}-500 font-medium px-2 py-1">
                    {{__($type)}}
                </span>
                <span class="text-xs font-medium text-gray-500">{{ $data['create_date']->diffForHumans() }}</span>
          </div>
        
         <p>
             <span class="font-medium">{{ $data['name'] }}</span>
             @if(!empty($data['category']))
               <span class="font-medium text-gray-500">( {{ __($data['category']) }} )</span>
             @endif
         </p> 
         @if($type == 'payment')
               <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                         {{$data['title'][0]}}:<span class="font-medium text-xs py-1 px-2 rounded text-{{$color}}-500">{{ $data['value'][0] }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                         {{$data['title'][1]}}:<span class="font-medium text-xs py-1 px-2 rounded text-{{$color}}-500">{{ $data['value'][1] }}</span>
                    </div>
               </div>
          @else
            <p>{{$data['title'][0]}}:<span class="font-medium text-xs py-1 px-2 rounded text-{{$color}}-500">{{ $data['value'][0] }}</span></p>
          @endif
          @if(!empty($data['text']))
               <span class="font-medium text-gray-500">{{$data['text']}}</span>
          @endif  
     </div>
 </div>
