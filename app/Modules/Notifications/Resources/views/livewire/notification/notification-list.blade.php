<div
    class="flex flex-col"
    x-data
    x-init="
        const root = $el;
        const applyPaginatorTheme = (isUpdate = false) => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };

        applyPaginatorTheme(false);

        const currentComponentId = $wire.__instance?.id ?? $wire.$id ?? null;
        if (!window.__notificationPaginatorHooks) {
            window.__notificationPaginatorHooks = {};
        }

        if (typeof Livewire !== 'undefined' && currentComponentId && !window.__notificationPaginatorHooks[currentComponentId]) {
            window.__notificationPaginatorHooks[currentComponentId] = true;

            Livewire.hook('commit', ({ component, succeed }) => {
                if (!component || component.id !== currentComponentId) {
                    return;
                }

                succeed(() => queueMicrotask(() => applyPaginatorTheme(true)));
            });
        }
    "
>
    <div class="flex justify-between items-center px-8 py-4">
        <span class="font-medium text-slate-600">{{ __('notifications::common.labels.count') }}: {{$notifications->total()}}</span>
        <button wire:click.prevent="clearNotifications" class="appearance-none font-medium space-x-2 flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark-x w-6 h-6 text-rose-500"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/><path d="m14.5 7.5-5 5"/><path d="m9.5 7.5 5 5"/></svg>
            <span class="text-rose-500">{{ __('notifications::common.labels.clear_all_notifications') }}</span>
        </button>
    </div>

    <div class="flex flex-col gap-4">
        @forelse ($groupedNotifications as $group)
            <div class="rounded-[1.4rem] border border-zinc-200 bg-zinc-50/70">
                <div class="border-b border-zinc-200 px-6 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $group['label'] }}</p>
                </div>
                <div class="space-y-3 px-4 py-4">
                    @foreach ($group['items'] as $notification)
                        <x-notification.list-item :$notification />
                    @endforeach
                </div>
            </div>
        @empty
            <div class="px-8 py-10 text-center text-sm text-slate-400">
                {{ __('notifications::common.labels.no_notifications_found') }}
            </div>
        @endforelse
        <div>
            {{$notifications->links()}}
        </div>
    </div>
</div>
