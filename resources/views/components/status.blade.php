@props(['statusId', 'label', 'type' => null, 'design' => 'default'])

@php
    // Design-system status palette: bg / text / dot, one row per status id.
    $map = [
        10 => 'bg-[#f4f4f5] text-[#52525b]',
        20 => 'bg-[#fff7ed] text-[#c2410c]',
        30 => 'bg-[#f0f9ff] text-[#0369a1]',
        40 => 'bg-[#f5f3ff] text-[#6d28d9]',
        70 => 'bg-[#ecfdf5] text-[#047857]',
        90 => 'bg-[#fff1f2] text-[#e11d48]',
    ];

    $dotMap = [
        10 => 'bg-[#a1a1aa]',
        20 => 'bg-[#f97316]',
        30 => 'bg-[#0ea5e9]',
        40 => 'bg-[#8b5cf6]',
        70 => 'bg-[#10b981]',
        90 => 'bg-[#f43f5e]',
    ];

    if ($type === 'order') {
        $statusId = match ($statusId) {
            10 => 10,
            20 => 70,
            30 => 90,
            default => $statusId,
        };
    }

    $color = $map[$statusId] ?? $map[10];
    $dot = $dotMap[$statusId] ?? $dotMap[10];
@endphp

<span class="hrm-badge {{ $color }}">
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
    <span>{{ $label }}</span>
</span>
