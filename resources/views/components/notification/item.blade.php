@props(['notification'])

@php
    $data = $notification->data;
    $type = $data['type'] ?? 'notification';
    $action = $data['action'] ?? '';
    $isRead = !empty($notification->read_at);
    switch ($action) {
        case 'create':
            $color = 'emerald';
            $message = $data['message'] ?? __('has created new personnel');
            $category = __($data['category'] ?? ('New ' . strtolower($type)));
            $addedBy = $data['added_by'] ?? null;
            break;
        case 'delete':
            $color = 'rose';
            $message = $data['message'] ?? __('has deleted personnel');
            $category = __($data['category'] ?? ($type . ' deleted'));
            $addedBy = $data['added_by'] ?? null;                                                                                                      
            break;
        case 'birthday':
            $color = 'blue';
            $message = '';
            $category = $type;
            $addedBy = null;
            break;
        case 'leave':
            $color = 'amber';
            $message = $data['message'] ?? __('New leave request has created');
            $category = __($data['category'] ?? $data['leave_type'] ?? 'Leave');
            $addedBy = $data['added_by'] ?? null;
            break;
        case 'leaveStatusChanged':
            $color = 'indigo';
            $message = $data['message'] ?? __('Leave request has been' . ' ' . strtolower($data['status'] ?? '') );
            $category = __($data['category'] ?? $data['leave_type'] ?? 'Leave');
            $addedBy = $data['added_by'] ?? null;
            break;
        default:
            $color = 'gray';
            $message = $data['message'] ?? __('has a notification');
            $category = __($data['category'] ?? 'Notification');
            $addedBy = $data['added_by'] ?? null;
    }
@endphp
<li class="w-full">
    <button @click.prevent="isOpen = false" wire:click.prevent="markAsRead('{{ $notification->id }}');"
        @class([
            'flex w-full px-5 py-3 transition duration-150 ease-in hover:bg-neutral-100',
            'bg-neutral-50' => !empty($notification->read_at),
        ])>
        <div class="flex flex-col items-start w-full space-y-1">
            <div class="flex items-start justify-between w-full">
                <p class="flex items-start gap-1 text-sm font-medium text-left text-neutral-500">
                    <span class="flex flex-wrap items-center gap-1">
                        @if ($action === 'birthday' && $addedBy)
                            <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                            <span class="flex items-center flex-none text-base text-black">
                                <span class="text-sm text-neutral-500">{{ __('Age') }}:</span>
                                {{ \Carbon\Carbon::parse($addedBy)->age }}
                            </span>
                        @elseif ($addedBy)
                            <span class="flex-none font-medium text-neutral-900">{{ $addedBy }}</span>
                        @endif

                        @if (!empty($message))
                            <span class="flex-none font-medium text-neutral-500">{{ $message }}</span>
                        @endif

                        @if (!empty($data['name']))
                            <span class="flex-wrap flex-none font-medium text-black">- {{ $data['name'] }}</span>
                        @endif
                    </span>
                   
                    <span
                        class="bg-{{ $color }}-50 border border-{{ $color }}-200 text-xs rounded-lg uppercase text-{{ $color }}-500 font-semibold px-2 py-0.5 w-max flex-none ml-1">
                        {{ __($category) }}
                    </span>
                </p>
                @if (!$isRead)
                    <span class="flex-none w-2 h-2 mt-1 rounded-full bg-emerald-500"></span>
                @endif
            </div>
            <div class="flex items-center justify-between w-full">
                <span
                    class="text-xs font-normal text-neutral-500">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
                <span class="text-xs font-normal text-neutral-500">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </button>
</li>
