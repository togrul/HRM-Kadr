@props(['notification'])

@php
    use App\Support\Translations\ModuleTranslation;

    $data = $notification->data;
    $action = $data['action'] ?? '';
    $isRead = !empty($notification->read_at);
    $resolveText = static fn ($value, $fallback = '') => is_string($value) && $value !== ''
        ? ModuleTranslation::resolveStoredText($value)
        : $fallback;

    switch ($action) {
        case 'create':
            $color = 'emerald';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.created_new_personnel'));
            $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.new_record'));
            $addedBy = $data['added_by'] ?? null;
            break;
        case 'delete':
            $color = 'rose';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.deleted_personnel'));
            $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.deleted_record'));
            $addedBy = $data['added_by'] ?? null;
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
            $addedBy = $data['added_by'] ?? null;
            break;
        case 'leaveStatusChanged':
            $color = 'indigo';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.leave_request_status_changed'));
            $category = $resolveText($data['category'] ?? ($data['leave_type'] ?? null), __('notifications::common.categories.leave'));
            $addedBy = $data['added_by'] ?? null;
            break;
        default:
            $color = 'gray';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.has_notification'));
            $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.notification'));
            $addedBy = $data['added_by'] ?? null;
    }
@endphp
<li class="w-full">
    <button x-on:click.prevent="isOpen = false" wire:click.prevent="markAsRead('{{ $notification->id }}');"
        @class([
            'flex w-full px-5 py-3 transition duration-150 ease-in hover:bg-neutral-100',
            'bg-neutral-50' => !empty($notification->read_at),
        ])>
        <div class="flex flex-col items-start w-full space-y-1">
            <div class="flex items-start justify-between w-full">
                <p class="flex items-start gap-1 pr-2 text-sm font-medium text-left text-neutral-500">
                    <span class="flex flex-wrap items-center gap-1">
                        @if ($action === 'birthday' && $addedBy)
                            <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                            <span class="flex items-center flex-none text-base text-black">
                                <span class="text-sm text-neutral-500">{{ __('notifications::common.labels.age') }}:</span>
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
                        {{ $category }}
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
