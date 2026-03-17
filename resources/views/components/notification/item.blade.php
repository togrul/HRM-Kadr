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
            'flex w-full px-5 py-4 transition duration-150 ease-in hover:bg-zinc-50',
            'bg-zinc-50/70' => !empty($notification->read_at),
        ])>
        <div class="flex w-full gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-{{ $color }}-200 bg-{{ $color }}-50 text-{{ $color }}-600">
                <span class="text-xs font-semibold uppercase tracking-[0.16em]">{{ strtoupper(substr($action ?: 'N', 0, 1)) }}</span>
            </div>

            <div class="flex flex-col items-start w-full space-y-2">
            <div class="flex items-start justify-between w-full">
                <p class="flex items-start gap-1 pr-2 text-sm font-medium text-left text-neutral-500">
                    <span class="flex flex-wrap items-center gap-1">
                        @if ($addedBy)
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
                        class="bg-{{ $color }}-50 border border-{{ $color }}-200 text-[11px] rounded-full uppercase tracking-tight text-{{ $color }}-600 font-semibold px-2.5 py-1 w-max flex-none ml-1">
                        {{ $category }}
                    </span>
                </p>
                @if (!$isRead)
                    <span class="flex-none w-2 h-2 mt-1 rounded-full bg-emerald-500"></span>
                @endif
            </div>
            <div class="flex items-center justify-between w-full">
                <div class="space-y-1 text-left">
                    @if ($action === 'birthday' && (!empty($data['position']) || !empty($data['structure']) || !empty($data['birthday_label'])))
                        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                            @if (!empty($data['position']))
                                <span>{{ $data['position'] }}</span>
                            @endif
                            @if (!empty($data['structure']))
                                <span>{{ $data['structure'] }}</span>
                            @endif
                            @if (!empty($data['birthday_label']))
                                <span>{{ __('notifications::common.labels.birthday_date') }}: {{ $data['birthday_label'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($action === 'position_change' && (!empty($data['old_position']) || !empty($data['new_position']) || !empty($data['effective_date'])))
                        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                            @if (!empty($data['old_position']) || !empty($data['new_position']))
                                <span>{{ $data['old_position'] ?: '—' }} → {{ $data['new_position'] ?: '—' }}</span>
                            @endif
                            @if (!empty($data['new_structure']))
                                <span>{{ $data['new_structure'] }}</span>
                            @endif
                            @if (!empty($data['effective_date']))
                                <span>{{ $data['effective_date'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($action === 'announcement' && (!empty($data['body']) || !empty($data['message'])))
                        <div class="max-w-xl text-xs leading-5 text-neutral-500">{{ $data['body'] ?? $data['message'] }}</div>
                    @endif
                    @if ($action === 'holiday' && (!empty($data['holiday_name']) || !empty($data['holiday_date']) || !empty($data['scope'])))
                        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                            @if (!empty($data['holiday_name']))
                                <span>{{ $data['holiday_name'] }}</span>
                            @endif
                            @if (!empty($data['holiday_date']))
                                <span>{{ __('notifications::common.labels.holiday_date') }}: {{ $data['holiday_date'] }}</span>
                            @endif
                            @if (!empty($data['scope']))
                                <span>{{ $data['scope'] }}</span>
                            @endif
                        </div>
                    @endif
                    <span class="text-xs font-normal text-neutral-500">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <span class="text-xs font-normal text-neutral-500">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
        </div>
        </div>
    </button>
</li>
