@include('layouts.navigation')

@include('includes.header')

<!-- Page Content -->
<main x-data="{collapsed: {{ isset($sidebar) ? 'false' : 'true' }}}" class="mt-4 max-w-7xl mx-auto lg:px-0 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg grid grid-cols-1 md:grid-cols-3 divide-y divide-x-0 md:divide-x md:divide-y-0 divide-gray-200">
    @if(isset($sidebar))
        <div x-show="!collapsed" class="left-panel bg-slate-100 z-20">
            {{ $sidebar }}
        </div>
    @endif

    <div class="relative" :class="collapsed ? 'md:col-span-3' : 'md:col-span-2'">
        {{ $slot }}
        @if(isset($sidebar))
            <button @click="collapsed=!collapsed" class="absolute top-0 left-0 rounded flex items-center p-1 shadow-sm z-10 bg-slate-200/60">
                @include('components.icons.left-icon',['show' => '!collapsed'])
                @include('components.icons.right-icon',['show' => 'collapsed'])
            </button>
        @endif
    </div>
</main>
