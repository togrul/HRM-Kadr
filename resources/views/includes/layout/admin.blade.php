<nav x-data="{ open: false }" class="hidden bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 lg:px-0">
        <div class="flex justify-between h-24">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <x-application-logo size="sm" class="block w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>
            </div>

            <div class="flex">
                <div class="flex items-center justify-center">
                    <a href="{{ route('home') }}" class="group flex justify-center items-center w-10 h-10 transition-all duration-300 sm:flex sm:items-center hover:bg-slate-50 rounded-md">
                        <x-icons.layout-icon color="text-emerald-500" size="w-7 h-7"></x-icons.layout-icon>
                    </a>
                </div>
                <!-- Settings Dropdown -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <x-dropdown align="right">
                        <x-slot name="trigger">
                            <button class="bg-gray-100 border border-gray-200 inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                <div class="flex flex-col items-start">
                                    <span class="text-sm text-gray-900">{{ Auth::user()->name }}</span>
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
                <div class="-mr-2 flex items-center sm:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
</nav>

<!-- Page Content -->
<main x-data="" class="px-1 max-w-7xl mx-auto lg:px-0 overflow-inherit">
    <div class="grid grid-cols-1 md:grid-cols-5 lg:grid-cols-4 space-y-2 space-x-0 sm:space-y-0 sm:space-x-3 w-full">

        <div class="md:col-span-2 lg:col-span-1 bg-gray-900 shadow-sm px-6 py-4 text-white h-screen sticky top-0">
            <div class="flex flex-col justify-between h-full">
                <div class="flex justify-center">
                    <!-- Logo -->
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('admin') }}">
                            <x-application-logo size="sm" class="block w-auto fill-current text-gray-800 dark:text-gray-200" />
                        </a>
                    </div>
                </div>

                <div class="flex flex-col space-y-4 h-full max-h-[calc(100vh-300px)] overflow-y-auto">
                    @foreach(config('admin.menu_items') as $menuItem)
                        @php
                            $iconClass = $menuItem['icon'];
                            $name = "icons.{$iconClass}";
                            $route = $menuItem['route'] !== '#' ? route($menuItem['route']) : $menuItem['route'];
                            $active = request()->routeIs($menuItem['route']);
                        @endphp
                        <a href="{{ $route }}" wire:navigate class="flex space-x-3 items-center px-2 group">
                            <x-dynamic-component :component="$name" color="{{ $active ? 'text-yellow-500' : 'text-gray-300' }}" hover="text-yellow-500" />
                            <span @class([
                                'transition-all duration-300 group-hover:text-yellow-500 text-sm',
                                'text-yellow-500' => $active,
                                'text-gray-200' => ! $active
                            ])>{{ __($menuItem['label']) }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="flex flex-col space-y-2 py-4">
                    <div class="flex items-center justify-start">
                        <a href="{{ route('home') }}" class="group flex justify-center items-center space-x-3 text-sm transition-all duration-300 sm:flex sm:items-center">
                            <x-icons.shutdown-icon size="w-6 h-6" color="text-gray-100" hover="text-yellow-500"></x-icons.shutdown-icon>
                            <span>{{ __('Return to dashboard') }}</span>
                        </a>
                    </div>
                    <!-- Settings Dropdown -->
                    <div class="flex items-center">
                        <button class="bg-gray-800 w-full border border-gray-700 inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-100 hover:text-gray-200  focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col items-start">
                                <span class="text-sm text-gray-100">{{ Auth::user()->name }}</span>
                                <span class="text-xs text-gray-400">{{ Auth::user()->email }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div class="md:col-span-3 lg:col-span-3 bg-white shadow-md rounded-md px-3 py-4">{{ $slot }}</div>
    </div>

</main>
@push('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
@endpush
