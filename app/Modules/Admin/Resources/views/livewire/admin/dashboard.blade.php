<div class="flex flex-col space-y-4">
    <h1 class="text-2xl text-gray-500">{{ __('Settings') }}</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach(config('admin.menu_items') as $menuItem)
            @php
                $iconClass = $menuItem['icon'];
                $name = "icons.{$iconClass}";
                $route = $menuItem['route'] !== '#' ? route($menuItem['route']) : $menuItem['route'];
            @endphp
            <a href="{{ $route }}" wire:navigate class="flex flex-col justify-center group items-center space-y-3 rounded-lg shadow-sm bg-gray-100 px-4 py-6 transition-all duration-300 hover:shadow-lg">
                <div class="flex justify-center items-center">
                    <x-dynamic-component :component="$name" size="w-8 h-8" color="text-slate-700" hover="text-yellow-500" />
                </div>
                <span class="transition-all text-gray-600 duration-300 group-hover:text-yellow-500 text-sm">{{ __($menuItem['label']) }}</span>
            </a>
        @endforeach
    </div>
</div>

