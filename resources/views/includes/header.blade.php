<header class="bg-white/80 dark:bg-gray-800 shadow-sm">
    <div class="max-w-7xl mx-auto py-3 px-4 lg:px-0 grid gap-4 grid-cols-2 sm:grid-cols-6">
        @foreach ($menus as $menuItem)
            @can($menuItem->permission?->name)
                <a href="{{ route($menuItem->url) }}" wire:navigate @class([
                    "bg-$menuItem->color-100 rounded-md shadow-sm px-4 py-1 flex items-center space-x-2 cursor-pointer transition-all duration-300 hover:bg-$menuItem->color-200",
                    "border border-$menuItem->color-400" => request()->routeIs($menuItem->url),
                ])>
                    {!! $menuItem->icon !!}
                    <span class="font-medium text-base text-gray-700">{{ __($menuItem->name) }}</span>
                </a>
            @endcan
        @endforeach
    </div>
</header>
