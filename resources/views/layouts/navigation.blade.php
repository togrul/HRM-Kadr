<nav x-data="{ open: false }" class="bg-transparent dark:bg-neutral-800/90">
    <!-- Primary Navigation Menu -->
    <div class="px-4 mx-auto max-w-7xl lg:px-0">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex items-center px-2 shrink-0">
                    <a href="{{ route('home') }}">
                        <x-application-logo size="xs"
                            class="block w-auto text-gray-800 fill-current dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="items-center hidden space-x-2 sm:-my-px sm:ml-10 sm:flex">
                   @module('candidates')
                    <x-nav-link class="space-x-2 text-xs uppercase" wire:navigate :href="route('candidates')" :active="request()->routeIs('candidates')">
                        <x-icons.candidate-icon size="w-5 h-5"
                            color="{{ request()->routeIs('candidates') ? 'text-gray-900' : 'text-gray-400' }}"></x-icons.candidate-icon>
                        <span>{{ __('Candidates') }}</span>
                    </x-nav-link>
                    @endmodule
                     @module('vacation')
                    <x-nav-link class="space-x-2 text-xs uppercase" wire:navigate :href="route('vacations.list')" :active="request()->routeIs('vacations.list')">
                        <x-icons.vacation-icon size="w-5 h-5"
                            color="{{ request()->routeIs('vacations.list') ? 'text-gray-900' : 'text-gray-400' }}"></x-icons.vacation-icon>
                        <span>{{ __('Vacations') }}</span>
                    </x-nav-link>
                    @endmodule
                    @module('business-trips')
                    <x-nav-link class="space-x-2 text-xs uppercase" wire:navigate :href="route('business-trips.list')" :active="request()->routeIs('business-trips.list')">
                        <x-icons.holiday-icon size="w-5 h-5"
                            color="{{ request()->routeIs('business-trips.list') ? 'text-gray-900' : 'text-gray-400' }}"></x-icons.holiday-icon>
                        <span>{{ __('Business trips') }}</span>
                    </x-nav-link>
                    @endmodule
                    @module('leaves')
                     <x-nav-link class="space-x-2 text-xs uppercase" wire:navigate :href="route('leaves')" :active="request()->routeIs('leaves')">
                        <x-icons.calendar-icon size="w-5 h-5" color="{{ request()->routeIs('leaves') ? 'text-gray-900' : 'text-gray-400' }}" size="w-7 h-7"></x-icons.calendar-icon>
                        <span>{{ __('Time off') }}</span>
                    </x-nav-link>
                    @endmodule
                </div>
            </div>

            <div class="flex">
                @can('access-admin')
                    <div class="flex items-center justify-center">
                        <a wire:navigate href="{{ route('admin') }}"
                            class="flex items-center justify-center w-10 h-10 transition-all duration-300 rounded-md group sm:flex sm:items-center hover:bg-slate-50">
                            <x-icons.admin-icon color="text-yellow-500" size="w-7 h-7"></x-icons.admin-icon>
                        </a>
                    </div>
                @endcan

                @module('notifications')
                  @can('get-notification')
                      @livewire('notification.notifications')
                  @endcan
                @endmodule
                <!-- Settings Dropdown -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <x-dropdown align="right">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 transition duration-150 ease-in-out border rounded-md bg-white/70 border-neutral-200 text-neutral-500 dark:text-neutral-400 dark:bg-neutral-800 hover:text-neutral-700 dark:hover:text-neutral-300 focus:outline-none">
                                <div class="flex flex-col items-start">
                                    <span class="text-sm text-neutral-900">{{ Auth::user()->name }}</span>
                                    <span class="text-xs">{{ Auth::user()->email }}</span>
                                </div>

                                <div class="ml-4">
                                    <x-icons.arrow-icon size="w-5 h-5"></x-icons.arrow-icon>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Hamburger -->
                <div class="flex items-center -mr-2 sm:hidden">
                    <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 text-gray-400 transition duration-150 ease-in-out rounded-md dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400">
                        <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
           @module('candidates')
            <x-responsive-nav-link wire:navigate :href="route('candidates')" :active="request()->routeIs('candidates')">
                {{ __('Candidates') }}
            </x-responsive-nav-link>
            @endmodule
             @module('vacation')
            <x-responsive-nav-link wire:navigate :href="route('vacations.list')" :active="request()->routeIs('vacations.list')">
                {{ __('Vacations') }}
            </x-responsive-nav-link>
            @endmodule
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
