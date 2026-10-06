<div class="contents">
    @if ($tabelNo)
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.history.title') }}</h2>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->history as $row)
                    <div wire:key="compensation-history-{{ $row->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="hrm-num truncate text-[13px] font-medium text-ink">{{ $row->maskedBaseAmount() }} {{ $row->currency }}</p>
                            <p class="hrm-num truncate text-[11.5px] text-ink-faint">
                                {{ $row->regime?->name }} <span class="px-0.5">·</span>
                                {{ optional($row->effective_from)->format('d.m.Y') }} — {{ $row->effective_to ? optional($row->effective_to)->format('d.m.Y') : __('compensation::dashboard.history.ongoing') }}
                            </p>
                        </div>
                        <x-small-badge :mode="$row->status === 'active' ? 'green' : 'secondary'" dot>
                            {{ __('compensation::dashboard.status.'.$row->status) }}
                        </x-small-badge>
                    </div>
                @empty
                    <div class="px-4 py-8">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.history.empty')" />
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>
