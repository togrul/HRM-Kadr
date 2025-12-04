@props(['notification'])

@php
    $data = $notification->data;
    $type = $data['type'];
    $action = $data['action'] ?? '';
    $isRead = !empty($notification->read_at);
    switch ($action) {
        case 'create':
            $color = 'emerald';
            $message = __('has created new personnel');
            $category = __('New ' . strtolower($type));
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
    }
@endphp
<li class="w-full">
    <button @click.prevent="isOpen = false" wire:click.prevent="markAsRead('{{ $notification->id }}')"
        @class([
            'flex w-full px-5 py-3 transition duration-150 ease-in hover:bg-neutral-100',
            'bg-neutral-50' => !empty($notification->read_at),
        ])>
        <div class="flex flex-col items-start w-full space-y-1">
            <div class="flex items-start justify-between w-full">
                <p class="flex items-start space-x-1 text-sm font-medium text-left text-neutral-500">
                    <span class="flex flex-wrap items-center gap-1">
                        @if ($action == 'birthday')
                            <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                            <span class="flex items-center flex-none text-base text-black">
                                <span class="text-sm text-neutral-500">{{ __('Age') }}:</span>
                                {{ \Carbon\Carbon::parse($data['added_by'])->age }}
                            </span>
                        @else
                            <span class="flex-none font-medium text-neutral-900">{{ $addedBy }}</span>
                        @endif
                        <span class="flex-none font-medium text-neutral-500">{{ $message ?? '' }} -</span>
                        <span class="flex-wrap flex-none font-medium text-black">{{ $data['name'] }}</span>
                    </span>
                    <span
                        class="bg-neutral-50 border border-neutral-200 text-xs rounded-lg uppercase text-{{ $color }}-500 font-medium px-2 py-0.5 w-max flex-none ml-1">
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
