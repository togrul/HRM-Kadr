<div
    class="relative flex items-center"
    x-data="{ isOpen: false, loadingRequest: false }"
    x-on:keydown.escape.window="isOpen = false"
    x-on:livewire:navigating.window="isOpen = false"
>
    <button
        type="button"
        @click="
            if (isOpen) {
                isOpen = false;
            } else {
                isOpen = true;
                if (! $wire.hasLoaded && !loadingRequest) {
                    loadingRequest = true;
                    Promise.resolve($wire.getNotifications())
                        .finally(() => { loadingRequest = false; });
                }
            }
        "
        class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-blue-500 transition duration-200 ease-in bg-transparent rounded-md hover:bg-white/80 focus:outline-none"
    >
        <svg  fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 font-normal text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
        </svg>
        @if ($notificationCount)
            <span class="absolute top-0 right-0 flex items-center justify-center w-4 h-4 font-medium text-rose-500 bg-rose-200 rounded-full border-1 text-[11px]">
                {{ $notificationCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        style="display:none;"
        x-show="isOpen"
        x-transition:enter="transition duration-200 transform ease-out"
        x-transition:enter-start="scale-75"
        x-transition:leave="transition duration-100 transform ease-in"
        x-transition:leave-end="opacity-0 scale-90"
        x-on:click.away="if (!loadingRequest) { isOpen = false }"
        x-on:click.stop
        class="absolute right-0 z-50 mt-3 origin-top-right overflow-hidden border border-zinc-200 bg-white text-left text-neutral-700 shadow-[0_30px_80px_rgba(15,23,42,0.16)] top-full w-[34rem] max-w-[calc(100vw-2rem)] rounded-[1.4rem]"
    >
        <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.module') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('notifications::common.helpers.dropdown_hint') }}</p>
                </div>
                @if ($notificationCount)
                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-zinc-600">{{ $notificationCount }}</span>
                @endif
            </div>
        </div>

        <ul class="max-h-[30rem] overflow-y-auto text-xs font-normal">
            @forelse($groupedNotifications as $group)
                <li class="sticky top-0 z-[1] border-y border-zinc-200 bg-white/95 px-5 py-2 backdrop-blur">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $group['label'] }}</p>
                </li>
                @foreach($group['items'] as $notification)
                    <x-notification.item :notification="$notification" />
                @endforeach
            @empty
                @if($isLoading)
                    @foreach(range(1,3) as $item)
                        <li class="flex items-center px-5 py-3 transition duration-150 ease-in animate-pulse">
                            <div class="w-10 h-10 bg-gray-200 rounded-xl"></div>
                            <div class="flex-1 ml-4 space-y-2">
                                <div class="w-full h-3 bg-gray-200 rounded"></div>
                                <div class="w-full h-3 bg-gray-200 rounded"></div>
                                <div class="w-1/2 h-3 bg-gray-200 rounded"></div>
                            </div>
                        </li>
                    @endforeach
                @else
                    <li class="py-6">
                        <div class="w-40 mx-auto">
                            <img class="mx-auto mix-blend-luminosity" src="{{ asset('/assets/images/chat.png') }}" alt="">
                            <div class="mt-6 text-sm font-medium text-center text-gray-400">{{ __('notifications::common.labels.no_new_notifications') }}</div>
                        </div>
                    </li>
                @endif
            @endforelse
        </ul>

        <div class="flex justify-between border-t border-zinc-200 bg-zinc-50/70 text-center">
            <a wire:navigate href="{{ route('notifications') }}" class="px-5 py-3 text-sm font-medium transition duration-300 text-neutral-500 hover:text-zinc-950">
                {{ __('notifications::common.labels.show_all_notifications') }}
            </a>
            <button
                wire:click="markAllAsRead"
                @click="isOpen = false"
                class="px-5 py-3 text-sm font-medium transition duration-150 ease-in appearance-none text-neutral-500 hover:text-zinc-950"
            >
                {{ __('notifications::common.labels.mark_all_as_read') }}
            </button>
        </div>
    </div>

</div>
