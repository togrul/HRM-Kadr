@props(['notification'])

@php
     $data = $notification->data;
     $type = $data['type'] ?? 'notification';
     $action = $data['action'] ?? '';
     $isRead = !empty($notification->read_at);
     $category = $data['category'] ?? ucfirst($type);
     $addedBy = $data['added_by'] ?? null;
     $message = $data['message'] ?? __('has a notification');

     switch ($action) {
            case 'create':
                $color = 'teal';
                $message = $data['message'] ?? __('has created new personnel');
                $category = $data['category'] ?? __('New '. strtolower($type));
                break;
            case 'delete':
                $color = 'rose';
                $message = $data['message'] ?? __('has deleted personnel');
                $category = $data['category'] ?? __($type . ' deleted');
                break;
            case 'birthday':
                $color = 'blue';
                $message = '';
                $category = $data['category'] ?? $type;
                $addedBy = null;
                break;
            case 'leave':
                $color = 'amber';
                $message = $data['message'] ?? __('New leave request has created');
                $category = $data['category'] ?? $data['leave_type'] ??'Leave';
                $addedBy = $data['fullname'] ?? null;
                break;
            case 'leaveStatusChanged':
                $color = 'indigo';
                $message = $data['message'] ?? __('Leave request has been' . ' ' . strtolower($data['status'] ?? '') );
                $category = __($data['category'] ?? $data['leave_type'] ?? 'Leave');
                $addedBy = $data['fullname'] ?? null;
                break;
            default:
                 $color = 'gray';
        };
@endphp
<div class="relative px-8 py-4 border-t-8 border-gray-50">
     <div class="flex items-center justify-between space-x-2">
          <div class="flex items-center space-x-3">
               <span class="rounded-tl-lg rounded-br-lg px-2 py-1 text-sm font-medium bg-{{$color}}-100 text-{{$color}}-500">
                  {{ __($category) }}
               </span>
               <p class="flex items-center space-x-1 text-base">
                   @if($action == 'birthday')
                       <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                       <span class="flex items-center text-base text-black">
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
             <span class="text-sm font-light text-gray-900">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
             <span class="text-sm font-light text-gray-500">({{ $notification->created_at->diffForHumans() }})</span>
         </div>
     </div>
</div>
