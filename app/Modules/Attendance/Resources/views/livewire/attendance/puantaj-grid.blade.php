@php
    $headers = array_merge(
        [__('Tabel no / Name')],
        array_map(fn ($day) => (string) $day, $days),
        [__('Total hours'), __('Total days')]
    );
@endphp

<div class="space-y-4">
    <x-surface-card :title="__('Timesheet grid')" icon="icons.calendar-icon">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <div class="md:col-span-2">
                <x-label for="attendance-puantaj-search">{{ __('Search (name or tabel no)') }}</x-label>
                <x-livewire-input
                    id="attendance-puantaj-search"
                    mode="gray"
                    name="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('e.g. 12345 or Aliyev') }}"
                />
            </div>
        </div>
    </x-surface-card>

    <div class="relative min-h-[300px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="$headers" :title="__('Timesheet grid')" bordered>
                    @forelse($rows as $row)
                        @php
                            $personnel = $row['personnel'];
                        @endphp
                        <tr>
                            <x-table.td extraClasses="w-max">
                                <div class="font-medium text-zinc-800">
                                    {{ $personnel->surname }} {{ $personnel->name }} {{ $personnel->patronymic }}
                                </div>
                                <div class="text-xs font-mono font-normal text-zinc-500">{{ $personnel->tabel_no }}</div>
                            </x-table.td>

                            @foreach($days as $day)
                                @php
                                    $cell = $row['cells'][$day] ?? ['value' => '', 'status' => 'none', 'title' => ''];
                                    $cellClass = match($cell['status']) {
                                        'present', 'manual_present' => 'text-zinc-700',
                                        'holiday_worked', 'weekend_worked' => 'text-emerald-700 bg-emerald-50/70',
                                        'absent', 'manual_absence' => 'text-rose-600 bg-rose-50/70',
                                        'holiday', 'weekend' => 'text-zinc-400 bg-zinc-50/80',
                                        default => 'text-zinc-400',
                                    };

                                    $cellColor = match (true) {
                                        $cell['value'] === 1 => '!text-rose-500 bg-rose-50',
                                        $cell['value'] > 0 && $cell['value'] < 9 => '!text-amber-500 bg-amber-50',
                                        $cell['value'] > 9 => '!text-emerald-500 bg-emerald-50',
                                        default => 'text-zinc-700 bg-white',
                                    };
                                @endphp
                                <x-table.td extraClasses="text-center text-xs {{ $cellClass }} {{ $cellColor }}" title="{{ $cell['title'] }}">
                                    {{ $cell['value'] }}
                                </x-table.td>
                            @endforeach
                            <x-table.td extraClasses="text-center font-medium text-zinc-700 stats-cell">
                                {{ $row['total_hours'] }}
                            </x-table.td>
                            <x-table.td extraClasses="text-center font-medium text-zinc-700 stats-cell">
                                {{ $row['total_days'] }}
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="count($headers)" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $personnels->links() }}
    </div>
</div>
