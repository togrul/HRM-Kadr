@include('layouts.navigation')

@include('includes.header')

@php
    $hasSidebar = isset($sidebar);
    // Default collapsed state: no sidebar => collapsed (content full-width)
    $defaultCollapsed = $hasSidebar ? 'false' : 'true';
@endphp

<!-- Page Content -->
<main
    x-data="{
        collapsed: {{ $defaultCollapsed }},
        toggle() { this.collapsed = !this.collapsed }
    }"
    x-cloak
    class="my-4 max-w-7xl mx-auto lg:px-0 bg-white dark:bg-neutral-800 overflow-hidden shadow-md shadow-black/5 sm:rounded-lg grid grid-cols-1 md:grid-cols-3 divide-y divide-x-0 md:divide-x md:divide-y-0 divide-gray-200 dark:divide-neutral-700/60"
>
    @if ($hasSidebar)
        <aside
            x-show="!collapsed"
            @include('partials.transition')
            id="sidebar"
            class="left-panel bg-neutral-50 dark:bg-neutral-900/30 border-neutral-200/70 dark:border-neutral-700/60 z-20"
            role="complementary"
            aria-label="Sidebar"
        >
            {{ $sidebar }}
        </aside>
    @endif

    <section
            class="relative"
            :class="collapsed ? 'md:col-span-3' : 'md:col-span-2'"
            aria-live="polite"
        >
            {{ $slot }}

            @if ($hasSidebar)
                <button
                    type="button"
                    @click="toggle()"
                    class="absolute top-2 left-2 rounded-md flex items-center gap-1 px-2 py-1 text-sm
                       bg-neutral-200/60 dark:bg-gray-700/60 border border-neutral-200/60 dark:border-gray-600
                       shadow-sm hover:bg-neutral-200/80 focus:outline-none focus-visible:ring-2
                       focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white
                       dark:focus-visible:ring-offset-gray-800"
                    :aria-expanded="(!collapsed).toString()"
                    aria-controls="sidebar"
                >
                    {{-- Small hit target + clear state indication --}}
                    <span x-show="collapsed" class="inline-flex items-center">
                        @include('components.icons.right-icon')
                        <span class="sr-only">Open sidebar</span>
                    </span>
                    <span x-show="!collapsed" class="inline-flex items-center">
                        @include('components.icons.left-icon')
                        <span class="sr-only">Close sidebar</span>
                    </span>
                </button>
            @endif
    </section>
</main>
