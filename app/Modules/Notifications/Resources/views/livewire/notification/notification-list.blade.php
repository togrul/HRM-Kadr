@php
    $groupDots = [
        'today' => 'bg-emerald-500',
        'yesterday' => 'bg-sky-500',
        'this_week' => 'bg-amber-500',
        'older' => 'bg-zinc-400',
    ];

    $unreadCount = $groupedNotifications
        ->flatMap(fn (array $group) => $group['items'])
        ->filter(fn ($notification): bool => empty($notification->read_at))
        ->count();

    $confirmClear = "\$dispatch('confirm-action', { tone: 'rose', message: "
        .\Illuminate\Support\Js::from(__('notifications::common.labels.clear_all_confirm')).", confirmText: "
        .\Illuminate\Support\Js::from(__('notifications::common.labels.clear_all_notifications'))
        .', run: () => $wire.clearNotifications() })';
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('notifications::common.titles.inbox')"
            :subtitle="__('notifications::common.titles.inbox_subtitle')"
        >
            <x-context-panel.section>
                @forelse ($groupedNotifications as $group)
                    <x-context-panel.item
                        wire:key="notifications-group-{{ $group['key'] }}"
                        :href="'#notifications-'.$group['key']"
                        :dot="$groupDots[$group['key']] ?? 'bg-zinc-400'"
                        :count="count($group['items'])"
                    >{{ $group['label'] }}</x-context-panel.item>
                @empty
                    <p class="px-2.5 py-2 text-[11.5px] text-ink-faint">{{ __('notifications::common.labels.no_notifications_found') }}</p>
                @endforelse

                <x-slot name="footer">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11.5px] text-ink-faint">{{ __('notifications::common.labels.count') }}</span>
                        <span class="hrm-num text-[12.5px] font-semibold text-ink">{{ $notifications->total() }}</span>
                    </div>
                    @if ($notifications->total() > 0)
                        <button type="button" @click="{{ $confirmClear }}" class="mt-2 text-[12px] font-medium text-[#be123c] transition hover:underline">
                            {{ __('notifications::common.labels.clear_all_notifications') }}
                        </button>
                    @endif
                </x-slot>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('notifications::common.titles.inbox')"
        :breadcrumb="__('notifications::common.titles.inbox')"
        :count="number_format($notifications->total(), 0, ',', ' ')"
        :count-label="__('notifications::common.labels.unit')"
    >
        <x-slot name="icon">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        </x-slot>

        <x-slot name="stats">
            <x-page-header.stat :value="number_format($notifications->total(), 0, ',', ' ')" :label="__('notifications::common.labels.unit')" />
            <x-page-header.stat :value="$unreadCount" :label="__('notifications::common.labels.unread')" tone="rose" />
        </x-slot>

        <x-slot name="actions">
            @if ($notifications->total() > 0)
                <x-pill-button variant="danger" @click="{{ $confirmClear }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    {{ __('notifications::common.labels.clear_all_notifications') }}
                </x-pill-button>
            @endif
        </x-slot>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        @forelse ($groupedNotifications as $group)
            <section id="notifications-{{ $group['key'] }}" class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-2.5 sm:px-5">
                    <p class="hrm-eyebrow">{{ $group['label'] }}</p>
                    <span class="hrm-num rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[11px] text-ink-muted">{{ count($group['items']) }}</span>
                </div>

                <div class="divide-y divide-hairline-subtle">
                    @foreach ($group['items'] as $notification)
                        <x-notification.list-item :$notification wire:key="notification-{{ $notification->id }}" />
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-hairline bg-white px-4 py-12 text-center text-[12.5px] text-ink-faint">
                {{ __('notifications::common.labels.no_notifications_found') }}
            </div>
        @endforelse
    </div>

    @if ($notifications->total() > 0)
        <x-pagination :paginator="$notifications" :unit="__('notifications::common.labels.unit')" />
    @endif
</div>
