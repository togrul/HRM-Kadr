@props(['notification'])

@php
    use App\Support\Translations\ModuleTranslation;

    $data = $notification->data;
    $action = $data['action'] ?? '';
    $isRead = ! empty($notification->read_at);
    $resolveText = static fn ($value, $fallback = '') => is_string($value) && $value !== ''
        ? ModuleTranslation::resolveStoredText($value)
        : $fallback;
    $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.notification'));
    $addedBy = $data['added_by'] ?? null;
    $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.has_notification'));

    switch ($action) {
        case 'create':
            $color = 'emerald';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.created_new_personnel'));
            $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.new_record'));
            break;
        case 'delete':
            $color = 'rose';
            $message = $resolveText($data['message'] ?? null, __('notifications::common.messages.deleted_personnel'));
            $category = $resolveText($data['category'] ?? null, __('notifications::common.categories.deleted_record'));
            break;
        case 'birthday':
            $color = 'sky';
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
            $color = 'emerald';
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
            $color = 'neutral';
    }

    // Interpolated `border-{$color}-200` classes never reached the compiled stylesheet —
    // Tailwind only keeps what it can read literally, so the tones are spelled out.
    $chip = match ($color) {
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-600',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        default => 'border-hairline bg-[#f4f4f5] text-ink-muted',
    };

    $avatarTone = match ($color) {
        'emerald' => 'green',
        'rose' => 'rose',
        'sky' => 'blue',
        'amber' => 'amber',
        'violet', 'indigo' => 'violet',
        default => 'neutral',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 px-4 py-3 transition hover:bg-[#fafafa] sm:px-5']) }}>
    <x-avatar :name="$addedBy ?: $category" size="sm" :tone="$avatarTone" />

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-[0.04em] {{ $chip }}">
                {{ $category }}
            </span>

            @unless ($isRead)
                <span class="h-1.5 w-1.5 rounded-full bg-ink" title="{{ __('notifications::common.labels.unread') }}"></span>
            @endunless

            <p class="flex flex-wrap items-center gap-1 text-[12.5px] leading-snug text-ink-muted">
                @if ($action === 'birthday')
                    <x-icons.cake-icon color="text-amber-600"></x-icons.cake-icon>
                    <span class="font-semibold text-ink">{{ $data['name'] ?? '' }}</span>
                @else
                    @if ($addedBy)
                        <span class="font-semibold text-ink">{{ $addedBy }}</span>
                    @endif
                    @if ($message !== '')
                        <span>{{ $message }}</span>
                    @endif
                    @if (! empty($data['name']))
                        <span class="font-semibold text-ink">{{ $data['name'] }}</span>
                    @endif
                @endif
            </p>
        </div>

        @if (! empty($data['body']))
            <p class="mt-1 line-clamp-2 text-[12px] leading-relaxed text-ink-faint">{{ $data['body'] }}</p>
        @endif

        @if (in_array($action, ['leave', 'leaveStatusChanged'], true) && (! empty($data['duration_summary']) || ! empty($data['duration_window']) || ! empty($data['leave_period'])))
            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11.5px] text-ink-faint">
                @if (! empty($data['leave_period']))
                    <span>{{ $data['leave_period'] }}</span>
                @endif
                @if (! empty($data['duration_summary']))
                    <span>{{ $data['duration_summary'] }}</span>
                @endif
                @if (! empty($data['duration_window']))
                    <span>{{ $data['duration_window'] }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="shrink-0 text-right leading-tight">
        <p class="hrm-num text-[11.5px] text-ink-muted">{{ $notification->created_at->format('d.m.Y H:i') }}</p>
        <p class="mt-0.5 text-[11px] text-ink-faint">{{ $notification->created_at->diffForHumans() }}</p>
    </div>
</div>
