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
            @includeWhen(!\request()->is('admin/*'),'includes.layout.default')
            @includeWhen(\request()->is('admin/*'),'includes.layout.admin')
        </div>

        <x-notification />

        @if(session()->has('success'))
            <x-notification
                :redirect="true"
                :message-to-display="session('success')"
            />
        @endif

        @if(session()->has('error'))
            <x-notification
                type="error"
                :redirect="false"
                :message-to-display="session('error')"
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
        <script>
            Livewire.on('refresh-page', () => {
                setTimeout(() => {
                    location.reload();
                }, 2000); // 2000 milliseconds = 2 seconds
            });
        </script>
    </body>
</html>
