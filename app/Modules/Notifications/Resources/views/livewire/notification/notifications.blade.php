<div
    wire:poll.10000ms='getNotificationCount'
    class="relative my-auto"
    x-data={isOpen:false}
>
    <button @click="
        isOpen=!isOpen
        if(isOpen){
            Livewire.dispatch('getNotifications')
        }
    "
            class="inline-flex justify-center w-full px-3 py-2 text-sm font-medium text-blue-500 transition duration-300 ease-in bg-transparent rounded-lg hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:bg-white focus:ring-white"
    >
        <svg  fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 font-normal text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
        </svg>
        @if($notificationCount)
            <div class="absolute flex items-center justify-center w-4 h-4 font-medium text-rose-500 bg-rose-200 rounded-full border-1 text-[11px] top-0 right-0">
                {{ $notificationCount }}
            </div>
        @endif
    </button>
    <div class="absolute z-40 text-left text-gray-700 bg-white border shadow-2xl shadow-slate-200 border-slate-200 -right-24 md:-right-8 w-72 md:w-96 rounded-xl"
         style="display:none;"
         x-show="isOpen"
         x-transition:enter="transition duration-200 transform ease-out"
         x-transition:enter-start="scale-75"
         x-transition:leave="transition duration-100 transform ease-in"
         x-transition:leave-end="opacity-0 scale-90"
         @click.away="isOpen = false"
         @keydown.escape.window="isOpen = false">
        <ul class="overflow-y-auto text-xs font-normal divide-y max-h-96 rounded-tl-xl rounded-tr-xl"
        >
            @forelse($notifications as $notification)
                <x-notification.item :notification="$notification" />
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
                    <div class="w-40 py-6 mx-auto">
                        <img class="mx-auto mix-blend-luminosity" src="{{ asset('/assets/images/chat.png') }}" alt="">
                        <div class="mt-6 text-sm font-medium text-center text-gray-400">{{ __('No new notifications') }}</div>
                    </div>
                @endif
            @endforelse
        </ul>
        <div class="flex justify-between text-center border-t border-gray-300">
            <a wire:navigate href="{{ route('notifications') }}" class="px-5 py-3 text-sm font-medium transition duration-300 text-slate-600 hover:text-green-400">
                {{__('Show all notifications')}}
            </a>
            <button
                wire:click="markAllAsRead"
                @click="isOpen = false"
                class="px-5 py-3 text-sm font-medium transition duration-150 ease-in appearance-none hover:text-blue-500"
            >
                {{ __('Mark all as read') }}
            </button>
        </div>
    </div >

</div>
