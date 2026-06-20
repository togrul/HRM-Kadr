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
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.birthday_today'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.birthday'));
                $addedBy = null;
                break;
            case 'position_change':
                $color = 'amber';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.position_changed'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.position_change'));
                $addedBy = null;
                break;
            case 'announcement':
                $color = 'teal';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.manual_announcement'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.announcement'));
                $addedBy = null;
                break;
            case 'holiday':
                $color = 'violet';
                $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.holiday_due'));
                $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.holiday'));
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
<div class="rounded-[1.4rem] border border-zinc-200 bg-white px-5 py-4 shadow-[0_16px_34px_rgba(15,23,42,0.04)]">
     <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 space-y-2">
               <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-{{$color}}-200 bg-{{$color}}-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight text-{{$color}}-600">
                        {{ $category }}
                    </span>
                    <p class="flex flex-wrap items-center gap-1 text-sm text-zinc-600">
                        @if($action == 'birthday')
                            <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                            <span class="font-semibold text-zinc-950">{{ $data['name'] ?? '' }}</span>
                        @else
                            @if($addedBy)
                                <span class="font-semibold text-zinc-900">{{ $addedBy }}</span>
                            @endif
                            @if($message !== '')
                                <span>{{ $message }}</span>
                            @endif
                            @if(!empty($data['name']))
                                <span class="font-semibold text-zinc-950">{{ $data['name'] }}</span>
                            @endif
                        @endif
                    </p>
               </div>
               @if (!empty($data['body']))
                    <p class="line-clamp-2 text-sm leading-6 text-zinc-500">{{ $data['body'] }}</p>
               @endif
               @if (in_array($action, ['leave', 'leaveStatusChanged'], true) && (!empty($data['duration_summary']) || !empty($data['duration_window']) || !empty($data['leave_period'])))
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        @if (!empty($data['leave_period']))
                            <span>{{ $data['leave_period'] }}</span>
                        @endif
                        @if (!empty($data['duration_summary']))
                            <span>{{ $data['duration_summary'] }}</span>
                        @endif
                        @if (!empty($data['duration_window']))
                            <span>{{ $data['duration_window'] }}</span>
                        @endif
                    </div>
               @endif
          </div>
          <div class="text-right text-xs text-zinc-500">
               <p>{{ $notification->created_at->format('d.m.Y H:i') }}</p>
               <p class="mt-1">{{ $notification->created_at->diffForHumans() }}</p>
          </div>
     </div>
</div>
