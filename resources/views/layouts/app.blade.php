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

<body class="min-h-screen pb-1 font-sans antialiased bg-neutral-200/60 dark:bg-neutral-900/80" x-data>
    <div class="min-h-full">
        @includeWhen(!\request()->is('admin/*'), 'includes.layout.default')
        @includeWhen(\request()->is('admin/*'), 'includes.layout.admin')
    </div>

    <x-notification
        :initial-type="session()->has('error') || session()->has('error_message') ? 'error' : (session()->has('success') ? 'success' : null)"
        :initial-message="session('error_message') ?? session('error') ?? session('success')"
    />

    @livewireScripts
    @stack('js')
</body>

</html>
