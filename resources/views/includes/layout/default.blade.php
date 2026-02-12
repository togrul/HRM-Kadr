@include('layouts.navigation')

@include('includes.header')

@php
    $hasSidebar = isset($sidebar);
    // Default collapsed state: no sidebar => collapsed (content full-width)
    $defaultCollapsed = $hasSidebar ? 'false' : 'true';
@endphp

@once
    <style>
        body.side-modal-open .sidebar-collapse-toggle {
            display: none !important;
        }
    </style>
@endonce

<!-- Page Content -->
<main
    x-data="{
        collapsed: {{ $defaultCollapsed }},
        toggle() { this.collapsed = !this.collapsed }
    }"
    x-cloak
    class="grid grid-cols-1 mx-auto my-1 overflow-hidden bg-white divide-x-0 shadow-sm max-w-7xl lg:px-0 dark:bg-neutral-800 shadow-black/5 sm:rounded-3xl md:grid-cols-3 dark:divide-neutral-700/60"
>
    @if ($hasSidebar)
        <aside
            x-show="!collapsed"
            @include('partials.transition')
            id="sidebar"
            class="z-1 py-4 bg-white border-r border-dashed border-neutral-200 left-panel dark:bg-neutral-900/30 dark:border-neutral-700/60"
            role="complementary"
            aria-label="Sidebar"
        >
            {{ $sidebar }}
        </aside>
    @endif

    <section
            class="relative py-4"
            :class="collapsed ? 'md:col-span-3' : 'md:col-span-2'"
            aria-live="polite"
        >
            {{ $slot }}

            @if ($hasSidebar)
                <button
                    type="button"
                    @click="toggle()"
                    class="sidebar-collapse-toggle absolute flex items-center gap-1 px-2 py-1 text-sm border rounded-md shadow-sm top-1 left-1 bg-neutral-200/60 dark:bg-gray-700/60 border-neutral-200/60 dark:border-gray-600 hover:bg-neutral-200/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-800"
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
