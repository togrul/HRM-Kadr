{{-- Məzuniyyət normaları → Staja görə: «Düz 15 il düzəlişi» (ƏM m.116.1; docs/vacation-legal-basis.md, (b-3)). --}}
<section class="flex flex-col gap-3 rounded-2xl border border-hairline bg-white px-4 py-4" aria-labelledby="seniority-fix-title">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex max-w-3xl flex-col gap-1">
            <h2 id="seniority-fix-title" class="text-[15px] font-semibold text-ink">{{ __('vacation::seniority_fix.title') }}</h2>
            <p class="text-[12px] leading-5 text-ink-muted">{{ __('vacation::seniority_fix.subtitle', ['law' => $lawDays]) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button mode="default" wire:click="scan">{{ __('vacation::seniority_fix.actions.scan') }}</x-button>
            @if ($preview !== null && collect($preview['active'])->where('applied', false)->isNotEmpty())
                <x-button mode="black" wire:click="confirmApply">{{ __('vacation::seniority_fix.actions.apply') }}</x-button>
            @endif
        </div>
    </div>

    @if ($preview !== null)
        <h3 class="text-[13px] font-semibold text-ink">{{ __('vacation::seniority_fix.sections.active') }}</h3>
        <div class="overflow-x-auto">
            <x-table.tbl :headers="[__('vacation::seniority_fix.table.employee'), __('vacation::seniority_fix.table.work_year'), __('vacation::seniority_fix.table.seniority_days'), __('vacation::seniority_fix.table.missing'), __('vacation::seniority_fix.table.status')]">
                @forelse ($preview['active'] as $row)
                    <tr wire:key="fix-active-{{ $row['tabel_no'] }}-{{ $row['sequence'] }}">
                        <x-table.td style="white-space: normal !important;">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-ink">{{ $row['fullname'] }}</span>
                                <span class="text-[12px] text-ink-faint">{{ $row['tabel_no'] }}{{ filled($row['structure']) ? ' · '.$row['structure'] : '' }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-[13px] text-ink-muted">{{ $row['sequence'] }} · {{ \Illuminate\Support\Carbon::parse($row['start'])->format('d.m.Y') }}–{{ \Illuminate\Support\Carbon::parse($row['end'])->format('d.m.Y') }}</span>
                        </x-table.td>
                        <x-table.td><span class="hrm-num text-sm text-ink">{{ $row['seniority_days'] }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-sm font-semibold text-ink">+{{ $row['applied'] ? $row['applied_days'] : $row['missing'] }}</span></x-table.td>
                        <x-table.td>
                            @if ($row['applied'])
                                <x-small-badge mode="secondary">{{ __('vacation::seniority_fix.status.applied') }}</x-small-badge>
                            @else
                                <x-small-badge mode="blue">{{ __('vacation::seniority_fix.status.pending') }}</x-small-badge>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state :title="__('vacation::seniority_fix.empty.title')" :message="__('vacation::seniority_fix.empty.active')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>

        <h3 class="text-[13px] font-semibold text-ink">{{ __('vacation::seniority_fix.sections.terminated') }}</h3>
        <p class="text-[12px] leading-5 text-ink-muted">{{ __('vacation::seniority_fix.hints.terminated') }}</p>
        <div class="overflow-x-auto">
            <x-table.tbl :headers="[__('vacation::seniority_fix.table.employee'), __('vacation::seniority_fix.table.work_year'), __('vacation::seniority_fix.table.leave_date'), __('vacation::seniority_fix.table.compensation_days'), __('vacation::seniority_fix.table.note')]">
                @forelse ($preview['terminated'] as $row)
                    <tr wire:key="fix-terminated-{{ $row['tabel_no'] }}-{{ $row['sequence'] }}">
                        <x-table.td style="white-space: normal !important;">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-ink">{{ $row['fullname'] }}</span>
                                <span class="text-[12px] text-ink-faint">{{ $row['tabel_no'] }}{{ filled($row['structure']) ? ' · '.$row['structure'] : '' }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-[13px] text-ink-muted">{{ $row['sequence'] }} · {{ \Illuminate\Support\Carbon::parse($row['start'])->format('d.m.Y') }}–{{ \Illuminate\Support\Carbon::parse($row['end'])->format('d.m.Y') }}</span>
                        </x-table.td>
                        <x-table.td><span class="text-[13px] text-ink-muted">{{ \Illuminate\Support\Carbon::parse($row['leave_date'])->format('d.m.Y') }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-sm font-semibold text-ink">{{ $row['missing'] }}</span></x-table.td>
                        <x-table.td style="white-space: normal !important;">
                            <span class="text-[12px] text-ink-muted">{{ $row['before_kmq16'] ? __('vacation::seniority_fix.notes.before_kmq16') : __('vacation::seniority_fix.notes.compensate') }}</span>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state :title="__('vacation::seniority_fix.empty.title')" :message="__('vacation::seniority_fix.empty.terminated')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>
    @endif
</section>
