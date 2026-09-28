@props([
    'valid' => false
])

<span @class([
    'hrm-badge',
    'bg-[#ecfdf5] text-[#047857]' => $valid,
    'bg-[#fff1f2] text-[#e11d48]' => ! $valid,
])>
    <span @class([
        'h-1.5 w-1.5 shrink-0 rounded-full',
        'bg-[#10b981]' => $valid,
        'bg-[#f43f5e]' => ! $valid,
    ])></span>
    <span>{{ $valid ? __('ui::common.status.active') : __('ui::common.status.inactive') }}</span>
</span>
