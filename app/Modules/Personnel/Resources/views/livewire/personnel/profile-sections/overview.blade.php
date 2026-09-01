@php
    $timeline = $reader->careerTimeline($personnel);
@endphp

<div class="grid gap-4 xl:grid-cols-2">
    @include('personnel::livewire.personnel.profile-sections.personal')

    <section class="rounded-2xl border border-hairline bg-white shadow-card">
        <div class="flex items-baseline justify-between border-b border-hairline-subtle px-4 py-2.5">
            <p class="hrm-eyebrow">{{ __('personnel::profile.sections.career') }}</p>
            <span class="hrm-num text-[11.5px] text-ink-faint">{{ count($timeline) }}</span>
        </div>

        @if (empty($timeline))
            <p class="px-4 py-8 text-center text-[12.5px] text-ink-faint">{{ __('ui::common.labels.no_information_added') }}</p>
        @else
            <ol class="px-4 py-3">
                @foreach ($timeline as $entry)
                    <li class="relative flex gap-3 pb-4 last:pb-0">
                        {{-- connector, stopping at the last entry --}}
                        @unless ($loop->last)
                            <span class="absolute left-[3.5px] top-3 h-full w-px bg-hairline" aria-hidden="true"></span>
                        @endunless

                        <span @class([
                            'relative mt-1.5 h-2 w-2 shrink-0 rounded-full',
                            'bg-ink' => $entry['is_current'],
                            'border border-hairline bg-white' => ! $entry['is_current'],
                        ])></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $entry['title'] }}</p>
                                <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint">{{ $entry['from'] }} — {{ $entry['to'] }}</span>
                            </div>
                            @if ($entry['organisation'] !== '')
                                <p class="mt-0.5 truncate text-[11.5px] text-ink-faint">{{ $entry['organisation'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>
