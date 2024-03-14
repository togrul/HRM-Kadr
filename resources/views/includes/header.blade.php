<header class="bg-white dark:bg-gray-800 shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 lg:px-0 grid gap-4 grid-cols-2 sm:grid-cols-6">
        @foreach ($menus as $menuItem)
        <a
            href="{{ route($menuItem->url) }}"
            wire:navigate
            @class([
                "bg-$menuItem->color-100 rounded-lg shadow-sm px-4 py-3 flex items-center space-x-2 cursor-pointer transition-all duration-300 hover:bg-$menuItem->color-200",
                "border-2 border-$menuItem->color-400" => request()->routeIs($menuItem->url)
            ])
        >
            {!! $menuItem->icon !!}
            <span class="font-medium">{{ __($menuItem->name) }}</span>
        </a>
        @endforeach

    </div>
</header>
