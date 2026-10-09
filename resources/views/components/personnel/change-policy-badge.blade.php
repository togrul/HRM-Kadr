@props(['policy' => null])

{{-- Dəyişiklik siyasəti: məhdud sahənin etiketindəki «(əmrlə)» / «(jurnal)» nişanı. --}}
@if (is_array($policy))
    <span @class([
        'ml-1 inline-flex items-center rounded-md px-1.5 py-px text-[10.5px] font-medium leading-4',
        'bg-amber-50 text-amber-700' => $policy['mode'] === 'order',
        'bg-sky-50 text-sky-700' => $policy['mode'] === 'journal',
    ]) title="{{ $policy['label'] }}">{{ __('personnel::change_policy.badges.'.$policy['mode']) }}</span>
@endif
