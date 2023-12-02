<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HR Management system') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('css')
    </head>
    <body class="font-sans subpixel-antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
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
                    <button @click="collapsed=!collapsed" class="absolute bottom-0 left-0 rounded flex items-center p-1 shadow-sm z-10 bg-teal-100">
                        <svg x-show="!collapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>     
                        <svg x-show="collapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>                       
                    </button>
                    @endif
                </div>
            </main>
        </div>

        <x-notification />

        @if(session()->has('success'))
            <x-notification
                :redirect="true"
                :message-to-display="session('success')"
            />
        @endif

        @if(session()->has('error_message'))
            <x-notification
                type="error"
                :redirect="true"
                :message-to-display="session('error_message')"
            />
        @endif

        @livewireScripts
        @stack('js')
    </body>
</html>
