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
    @ui:sidebar-toggle.window="toggle()"
    :class="collapsed ? 'grid-cols-1' : 'grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)]'"
    x-cloak
    class="mx-auto my-4 grid w-full max-w-7xl gap-5 px-4 lg:px-0"
>
    @if ($hasSidebar)
        <aside
            x-show="!collapsed"
            @include('partials.transition')
            id="sidebar"
            class="z-1 min-h-[72vh]"
            role="complementary"
            aria-label="Sidebar"
        >
            {{ $sidebar }}
        </aside>
    @endif
    <section
            class="relative min-w-0 overflow-hidden rounded-[18px] border border-zinc-200 bg-white shadow-sm"
            aria-live="polite"
        >
            {{ $slot }}

            @if ($hasSidebar)
                <button
                    type="button"
                    @click="toggle()"
                    x-show="collapsed"
                    x-transition.opacity
                    class="sidebar-collapse-toggle absolute left-3 top-3 inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white p-1.5 text-zinc-600 shadow-sm hover:bg-zinc-50 hover:text-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-gray-600 dark:bg-gray-700/90 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-800"
                    :aria-expanded="(!collapsed).toString()"
                    aria-controls="sidebar"
                >
                    <x-icons.sidebar-toggle-icon size="w-5 h-5" color="text-zinc-700" hover="text-zinc-900" />
                    <span class="sr-only">Open sidebar</span>
                </button>
            @endif
    </section>
</main>
