<header class="bg-neutral dark:bg-gray-800">
    <div class="grid grid-cols-2 gap-4 px-4 py-2 mx-auto max-w-7xl lg:px-0 sm:grid-cols-5">
        @foreach ($menus as $menuItem)
            @php
              $moduleName = match($menuItem->url)
              {
                'home' => 'personnel',
                'staffs' => 'staff',
                default => $menuItem->url
              };
              $routeBase = (string) $menuItem->url;
              $isActive = request()->routeIs($routeBase) || request()->routeIs($routeBase . '.*');
            @endphp
            @module($moduleName)
            @can($menuItem->permission?->name)
                <a href="{{ route($menuItem->url) }}" wire:navigate @class([
                    "bg-$menuItem->color-100 text-$menuItem->color-700 border rounded-xl px-4 py-1 flex items-center space-x-2 cursor-pointer transition-all duration-300 hover:bg-$menuItem->color-200",
                    "border-$menuItem->color-400" => $isActive,
                    "border-transparent" => ! $isActive
                ])>
                    {!! $menuItem->icon !!}
                    <span class="text-sm font-mono uppercase font-medium">{{ __($menuItem->name) }}</span>
                </a>
            @endcan
            @endmodule
        @endforeach
    </div>
</header>
