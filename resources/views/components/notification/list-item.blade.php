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
<div class="hidden border-red-200 border-blue-200 border-green-200"></div>
<div class="bg-white border-2 border-{{$color}}-200 shadow-sm rounded-lg px-8 pl-20 py-4 relative">
     <span class="absolute top-0 left-[-1px] rounded-tl-lg rounded-br-lg px-2 py-1 text-xs font-medium bg-{{$color}}-100 text-{{$color}}-500">
          {{__($type)}}
     </span>

     <div class="flex items-center justify-between space-x-2">
          <div class="flex items-center space-x-3">
               <p class="text-sm">
                    <span class="font-medium">{{ $data['name'] }}</span>
                    @if(!empty($data['category']))
                      <span class="font-medium text-gray-500">( {{ __($data['category']) }} )</span>
                    @endif
                </p> 
                @if($type == 'payment')
               <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2 text-xs">
                         <span>{{$data['title'][0]}}</span>:<span class="font-medium text-xs text-{{$color}}-500">{{ $data['value'][0] }}</span>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                        <span>{{$data['title'][1]}}</span>:<span class="font-medium text-xs text-{{$color}}-500">{{ $data['value'][1] }}</span>
                    </div>
               </div>
               @else
                    <p class="text-sm">{{$data['title'][0]}}:<span class="font-medium text-xs py-1 px-2 rounded text-{{$color}}-500">{{ $data['value'][0] }}</span></p>
               @endif
               @if(!empty($data['text']))
                    <span class="font-normal text-sm text-gray-500">{{$data['text']}}</span>
               @endif  
          </div>
          <span class="text-xs font-medium text-gray-500">{{ $data['create_date']->diffForHumans() }}</span>
     </div>
</div> 