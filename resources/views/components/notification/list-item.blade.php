@props(['notification'])

@php
     use App\Support\Translations\ModuleTranslation;

     $data = $notification->data;
     $action = $data['action'] ?? '';
     $isRead = !empty($notification->read_at);
     $resolveText = static fn ($value, $fallback = '') => is_string($value) && $value !== ''
         ? ModuleTranslation::resolveStoredText($value)
         : $fallback;
     $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.notification'));
     $addedBy = $data['added_by'] ?? null;
     $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.has_notification'));

     switch ($action) {
            case 'create':
                $color = 'teal';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.created_new_personnel'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.new_record'));
                break;
            case 'delete':
                $color = 'rose';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.deleted_personnel'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.deleted_record'));
                break;
            case 'birthday':
                $color = 'blue';
                $message = '';
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.birthday'));
                $addedBy = null;
                break;
            case 'leave':
                $color = 'amber';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.new_leave_request_created'));
                $category = $resolveText($data['category'] ?? ($data['leave_type'] ?? null), __('notifications::common.categories.leave'));
                $addedBy = $data['fullname'] ?? null;
                break;
            case 'leaveStatusChanged':
                $color = 'indigo';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.leave_request_status_changed'));
                $category = $resolveText($data['category'] ?? ($data['leave_type'] ?? null), __('notifications::common.categories.leave'));
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
                  {{ $category }}
               </span>
               <p class="flex items-center space-x-1 text-base">
                   @if($action == 'birthday')
                       <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                       <span class="flex items-center text-base text-black">
                           <span class="text-sm text-gray-500">{{ __('notifications::common.labels.age') }}:</span>{{ \Carbon\Carbon::parse($data['added_by'])->age }}
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
