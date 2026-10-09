<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'HR Management system') }}</title>

    <!-- Scripts -->
    <link rel="stylesheet" href="{{ asset('assets/css/pikaday.min.css') }}">
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/pikaday.min.js') }}"></script>
    @php
        $dateLocale = app()->getLocale();
        $hasDateNames = \Illuminate\Support\Facades\Lang::hasForLocale('ui::date.months', $dateLocale);
    @endphp
    {{-- every date picker reads its calendar language from here (resources/js/date-picker.js);
         a locale without its own catalogue gets month/day names from the browser's Intl --}}
    <script>window.hrmDateLocale = @js([
        'locale' => $dateLocale,
        'previousMonth' => __('ui::date.previous_month'),
        'nextMonth' => __('ui::date.next_month'),
        'names' => $hasDateNames ? [
            'months' => __('ui::date.months'),
            'weekdays' => __('ui::date.weekdays'),
            'weekdaysShort' => __('ui::date.weekdays_short'),
        ] : null,
    ]);</script>
    {{-- read before first paint, so a collapsed context panel never flashes open and shifts the page --}}
    <script>try { if (localStorage.getItem('hrm.panelCollapsed') === '1') document.documentElement.setAttribute('data-panel-collapsed', ''); } catch (e) {}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('css')
</head>
<body class="min-h-screen font-sans text-ink bg-[#fafafa] dark:bg-neutral-900/80" x-data>
    <div class="min-h-full">
        @includeWhen(!\request()->is('admin/*'), 'includes.layout.default')
        @includeWhen(\request()->is('admin/*'), 'includes.layout.admin')
    </div>

    @persist('app-toast-shell')
        <x-notification
            :initial-type="session()->has('error') || session()->has('error_message') ? 'error' : (session()->has('success') ? 'success' : null)"
            :initial-message="session('error_message') ?? session('error') ?? session('success')"
        />
    @endpersist

    @persist('app-confirm-modal')
        <x-confirm-modal />
    @endpersist

    @livewireScripts
    @stack('js')
</body>

</html>
