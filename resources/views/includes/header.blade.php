<header class="bg-neutral dark:bg-gray-800">
    <div class="grid grid-cols-2 gap-4 px-4 py-2 mx-auto max-w-7xl lg:px-0 sm:grid-cols-6">
        @foreach ($menus as $menuItem)
            @can($menuItem->permission?->name)
                <a href="{{ route($menuItem->url) }}" wire:navigate @class([
                    "bg-$menuItem->color-100 rounded-lg shadow-sm px-4 py-1 flex items-center space-x-2 cursor-pointer transition-all duration-300 hover:bg-$menuItem->color-200",
                    "border border-$menuItem->color-400" => request()->routeIs($menuItem->url),
                ])>
                    {!! $menuItem->icon !!}
                    <span class="text-sm font-medium text-gray-700 uppercase">{{ __($menuItem->name) }}</span>
                </a>
            @endcan
        @endforeach
    </div>
</header>
