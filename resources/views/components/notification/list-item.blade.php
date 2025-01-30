@props(['notification'])

@php
     $data = $notification->data;
     $type = $data['type'];
     $action = $data['action'] ?? '';
     $isRead = !empty($notification->read_at);
     switch ($action) {
            case 'create':
                $color = 'teal';
                $message = __('has created new personnel');
                $category = __('New '. strtolower($type));
                $addedBy = $data['added_by'];
                break;
            case 'delete':
                $color = 'rose';
                $message = __('has deleted personnel');
                $category = __($type . ' deleted');
                $addedBy = $data['added_by'];
                break;
            case 'birthday':
                $color = 'blue';
                $message = '';
                $category = $type;
                $addedBy = null;
                break;
            default:
                 $color = 'gray';
                 $message = __('has a notification');
        };
@endphp
<div class="border-t-8 border-gray-50 px-8 py-4 relative">
     <div class="flex items-center justify-between space-x-2">
          <div class="flex items-center space-x-3">
               <span class="rounded-tl-lg rounded-br-lg px-2 py-1 text-sm font-medium bg-{{$color}}-100 text-{{$color}}-500">
                  {{ __($category) }}
               </span>
               <p class="text-base flex items-center space-x-1">
                   @if($action == 'birthday')
                       <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                       <span class="text-black text-base flex items-center">
                           <span class="text-sm text-gray-500">{{ __('Age') }}:</span>{{ \Carbon\Carbon::parse($data['added_by'])->age }}
                       </span>
                   @else
                       <span class="font-medium text-slate-500">{{ $addedBy }}</span>
                   @endif
                   <span>{{ $message ?? '' }} -</span>
                   <span class="font-medium text-sky-500">{{ $data['name'] }}</span>
                </p>
          </div>
         <div class="flex items-center space-x-2">
             <span class="font-light text-sm text-gray-900">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
             <span class="font-light text-sm text-gray-500">({{ $notification->created_at->diffForHumans() }})</span>
         </div>
     </div>
</div>
