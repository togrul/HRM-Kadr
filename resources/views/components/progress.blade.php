@props([
    'startDate',
    'endDate',
    'color'
])

@php
    $duration = $startDate->diffInDays($endDate);
    $currentProgress = $startDate->diffInDays(\Carbon\Carbon::now()->addDay());
    $percentage = ($duration > 0) ? ($currentProgress / $duration) * 100 : 0;
@endphp

<div class="flex flex-col py-1 px-1">
    <div class="flex items-center justify-between space-x-2">
        <span class="text-xs text-gray-400">
            {{ $startDate->format('d.m.Y') }}
        </span>
        <span class="text-xs text-{{ $color }}-500">
            {{ $slot }}
        </span>
        <span class="text-xs text-gray-800">
            {{ $endDate->format('d.m.Y') }}
        </span>
    </div>
    <div class="w-full h-1.5 bg-{{ $color }}-100 rounded-lg relative overflow-hidden">
        <span class="bg-{{ $color }}-500 absolute left-0 top-0 h-full" style="width:{{ number_format($percentage, 2) }}%;"></span>
    </div>
</div>
